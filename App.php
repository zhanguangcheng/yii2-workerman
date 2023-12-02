<?php

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
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_ENV') or define('YII_ENV', 'dev');
        defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
        require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
        self::timer();
        Timer::add(1, [self::class, 'timer']);
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
    public static function send(TcpConnection $connection, Request $request): void
    {
        // $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_TIME_FLOAT'] = self::$requestTimeFloat;
        $_SERVER['REQUEST_TIME'] = self::$requestTime;

        ob_start();
        $app = new Application(self::$config);
        try {
            $app->errorHandler->silentExitOnException = true;
            $app->errorHandler->discardExistingOutput = false;
            $app->get('request')->setRawBody($request->rawBody());
            $app->set('db', self::getDb());
            $app->set('redis', self::getRedis());
            $app->run();
        } catch (\yii\db\Exception $e) {
            if ($e->getCode() === "HY000" && strpos($e->getMessage(), "2006")) {
                try {
                    self::getDb()->close();
                    self::getDb()->open();
                    $app->run();
                } catch (Exception $e) {
                    $app->errorHandler->handleException($e);
                } catch (Throwable $th) {
                    $app->errorHandler->handleException($th);
                }
            } else {
                $app->errorHandler->handleException($e);
            }
        } catch (Exception $e) {
            $app->errorHandler->handleException($e);
        } catch (Throwable $th) {
            $app->errorHandler->handleException($th);
        }
        $response = (string)ob_get_clean();

        header(self::$headerDate);
        $connection->send($response);
        unset($app);
        unset($response);
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
