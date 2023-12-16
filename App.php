<?php

use Linkerman\ExitException;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Protocols\Http\Request;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\redis\Connection as RedisConnection;
use yii\web\Application;

class App
{
    public static array $config;
    public static string $headerDate;
    public static int $requestTime;
    public static float $requestTimeFloat;
    public static Connection $db;
    public static RedisConnection $redis;

    public static function init(): void
    {
        self::timer();
        Timer::add(1, [self::class, 'timer']);
        Timer::add(55, [self::class, 'heartbeat']);
        App::$config = require __DIR__ . '/config/web.php';
    }

    public static function timer(): void
    {
        self::$headerDate = 'Date: ' . gmdate('D, d M Y H:i:s') . ' GMT';
        self::$requestTime = time();
        self::$requestTimeFloat = microtime(true);
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
                } catch (Exception|Throwable $e) {
                }
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public static function send(TcpConnection $connection, Request $request): void
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = self::$requestTimeFloat;
        $_SERVER['REQUEST_TIME'] = self::$requestTime;
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/web/index.php';
        if (isset($_SERVER['HTTP_HTTPS'])) {
            $_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];
        }

        // Static file support
        if (null !== ($response = StaticFile::process($request))) {
            $connection->send($response);
            return;
        }

        ob_start();
        $app = new Application(self::$config);
        try {
            $app->errorHandler->silentExitOnException = true;
            $app->errorHandler->discardExistingOutput = false;
            $app->get('request')->setRawBody($request->rawBody());
            $app->set('db', self::getDb());
            $app->set('redis', self::getRedis());
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
        $response = (string)ob_get_clean();

        header(self::$headerDate);
        $connection->send($response);
        // Flush log messages from memory to disk to prevent memory leaks.
        Yii::getLogger()->flush(true);
        unset($app);
        unset($response);
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
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public static function getDb(): Connection
    {
        if (!isset(self::$db)) {
            self::$db = Yii::createObject(self::$config['components']['db']);
            self::$db->open();
        }
        return self::$db;
    }

    /**
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public static function getRedis(): RedisConnection
    {
        if (!isset(self::$redis)) {
            self::$redis = Yii::createObject(self::$config['components']['redis']);
            self::$redis->open();
        }
        return self::$redis;
    }

}
