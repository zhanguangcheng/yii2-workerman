<?php

namespace server;

use Exception;
use Linkerman\ExitException;
use Linkerman\Http;
use ReflectionException;
use server\middlewares\Guard;
use server\middlewares\Middleware;
use server\middlewares\RateLimiter;
use server\middlewares\StaticFile;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\redis\Connection as RedisConnection;
use yii\web\Application;
use yii\web\UploadedFile;

class App
{
    public static array $config = [];
    public static ?Connection $db = null;
    public static ?RedisConnection $redis = null;
    public static Middleware $middleware;

    /**
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    public static function init(): void
    {
        App::$config = require APP_PATH . '/src/config/web.php';
        self::heartbeat();
        Timer::add(55, [self::class, 'heartbeat']);
        self::$middleware = new Middleware();
        if (self::getRedis()) {
            self::$middleware->load([
                new RateLimiter(600, 60, self::getRedis()),
            ]);
        }
        self::$middleware->load([
            new Guard(),
            new StaticFile(APP_PATH . '/web'),
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    public static function heartbeat(): void
    {
        try {
            self::getDb()->createCommand("SELECT 1 LIMIT 1")->execute();
        } catch (\yii\db\Exception $e) {
            if ($e->getCode() === "HY000" && strpos($e->getMessage(), "2006")) {
                try {
                    self::getDb()->close();
                    self::getDb()->open();
                } catch (Exception|Throwable) {
                }
            }
        }
    }

    public static function send(TcpConnection $connection, Request $request): void
    {
        $_SERVER['SCRIPT_FILENAME'] = APP_PATH . '/web/index.php';
        UploadedFile::reset();
        $response = self::$middleware->call($request, [self::class, 'run']);
        $isSse = str_contains($response->getHeader('Content-Type')??'', 'text/event-stream');
        if (!$isSse) {
            $connection->send($response);
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public static function run(): Response
    {
        ob_start();
        $app = new Application(self::$config);
        try {
            $app->errorHandler->silentExitOnException = true;
            $app->errorHandler->discardExistingOutput = false;
            if (self::getDb()) {
                $app->set('db', self::getDb());
            }
            if (self::getRedis()) {
                $app->set('redis', self::getRedis());
            }
            $app->run();
        } catch (ExitException $e) {
            echo $e->getMessage();
        } catch (\yii\db\Exception $e) {
            if ($e->getCode() === "HY000" && strpos($e->getMessage(), "2006")) {
                try {
                    self::getDb()->close();
                    self::getDb()->open();
                    $app->run();
                } catch (Exception|Throwable $e) {
                    self::handleException($app, $e);
                }
            } else {
                self::handleException($app, $e);
            }
        } catch (Exception|Throwable $e) {
            self::handleException($app, $e);
        }
        $responseText = (string)ob_get_clean();
        Yii::getLogger()->flush(true);
        Http::$response->withBody($responseText);
        return Http::$response;
    }

    public static function handleException(Application $app, $e): void
    {
        try {
            $app->errorHandler->handleException($e);
        } catch (Exception|Throwable) {
        }
    }

    public static function stop(): void
    {
        Timer::delAll();
    }

    /**
     * @return Connection|null
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    public static function getDb(): Connection|null
    {
        if (!isset(self::$db) && isset(self::$config['components']['db'])) {
            $component = Yii::createObject(self::$config['components']['db']);
            if ($component instanceof Connection) {
                self::$db = $component;
                self::$db->open();
            }
        }
        return self::$db;
    }

    /**
     * @return RedisConnection|null
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    public static function getRedis(): RedisConnection|null
    {
        if (!isset(self::$redis) && isset(self::$config['components']['redis'])) {
            $component = Yii::createObject(self::$config['components']['redis']);
            if ($component instanceof RedisConnection) {
                self::$redis = $component;
                self::$redis->open();
            }
        }
        return self::$redis;
    }

}
