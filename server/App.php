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
use yii\base\Widget;
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
     * @throws Exception
     */
    public static function init(): void
    {
        self::$config = require APP_PATH . '/src/config/web.php';
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

    public static function send(TcpConnection $connection, Request $request): void
    {
        $_SERVER['SCRIPT_FILENAME'] = APP_PATH . '/web/index.php';
        $response = self::$middleware->call($request, [self::class, 'run']);
        if ($response->rawBody() !== '__ASYNC_RESPONSE__') {
            $connection->send($response);
        }
        self::gcConnection($connection);
    }

    /**
     * @throws InvalidConfigException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public static function run(): Response
    {
        ob_start();
        $app = new Application(self::$config);
        try {
            self::setupApp($app);
            $app->run();
        } catch (ExitException $e) {
            echo $e->getMessage();
        } catch (\yii\db\Exception $e) {
            self::handleDatabaseException($e, $app);
        } catch (Exception|Throwable $e) {
            self::handleException($app, $e);
        }
        Http::$response->withBody(ob_get_clean());
        self::gcApp($app);
        return Http::$response;
    }

    public static function handleException(Application $app, $e): void
    {
        try {
            $app->errorHandler->handleException($e);
        } catch (Exception|Throwable) {
        }
    }

    /**
     * @param Exception $e
     * @param Application $app
     * @return void
     */
    public static function handleDatabaseException(Exception $e, Application $app): void
    {
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
    }

    /**
     * @param Application $app
     * @return void
     * @throws InvalidConfigException
     * @noinspection PhpInternalEntityUsedInspection
     */
    public static function setupApp(Application $app): void
    {
        $app->set('connection', $GLOBALS['WORKERMAN_CONNECTION']);
        $app->set('db', self::getDb());
        if (self::getRedis()) {
            $app->set('redis', self::getRedis());
        }
        UploadedFile::reset();
        Widget::$counter = 0;
        Widget::$stack = [];
    }

    /**
     * @param TcpConnection $connection
     * @noinspection PhpFieldImmediatelyRewrittenInspection
     * @return void
     */
    public static function gcConnection(TcpConnection $connection): void
    {
        if (isset($connection->__request)) {
            $connection->__request->session = null;
            $connection->__request->connection = null;
            $connection->__request = null;
        }
        if (isset($connection->__header)) {
            $connection->__header = null;
        }
    }

    /**
     * @param Application $app
     * @return void
     */
    public static function gcApp(Application $app): void
    {
        // Prevent exceptions from causing transactions that are not rolled back
        $transaction = $app->getDb()->getTransaction();
        if ($transaction) {
            while($transaction->getIsActive()) {
                try {
                    $transaction->rollBack();
                } catch (Exception) {
                }
            }
        }
        Yii::getLogger()->flush(true);
    }

    /**
     * @throws InvalidConfigException
     */
    public static function heartbeat(): void
    {
        try {
            self::getDb()->createCommand("SELECT 1 LIMIT 1")->execute();
            gc_collect_cycles();
            gc_mem_caches();
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

    /**
     * @return Connection|null
     * @throws InvalidConfigException
     * @throws Exception
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
     * @throws Exception
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

    public static function stop(): void
    {
        Timer::delAll();
    }

}
