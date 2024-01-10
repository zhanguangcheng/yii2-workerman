<?php

/**
 * Web Server
 *
 * @noinspection PhpObjectFieldsAreOnlyWrittenInspection
 */

use Linkerman\Linkerman;
use server\App;
use server\DotEnv;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\RedisSessionHandler;
use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

Linkerman::init();

define('APP_PATH', realpath(__DIR__ . '/../'));
DotEnv::$instances['server'] = new DotEnv();
DotEnv::$instances['server']->load(__DIR__ . '/.env');

defined('YII_DEBUG') or define("YII_DEBUG", (bool)_env('YII_DEBUG', true));
defined('YII_ENV') or define("YII_ENV", _env('YII_ENV', 'dev'));
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

require_once APP_PATH . '/vendor/yiisoft/yii2/Yii.php';

Worker::$stdoutFile = APP_PATH . '/src/runtime/workerman.stdout.log';
Worker::$logFile = APP_PATH . '/src/runtime/workerman.log';
Worker::$pidFile = APP_PATH . '/src/runtime/workerman.pid';

$worker = new Worker(_env('WORKER_ADDRESS'));
$worker->count = (int)_env('WORKER_COUNT', 4);
$worker->name = 'yii2-workerman';
$worker->onWorkerStart = [App::class, 'init'];
$worker->onMessage = [App::class, 'send'];
$worker->onWorkerStop = [App::class, 'stop'];

/*
// Save session to Redis
$redis = require APP_PATH . '/src/config/redis.php';
$config = [
    'host' => $redis['hostname'],
    'port' => $redis['port'],
    'database' => $redis['database'],
    'timeout' => 2,
    // 'auth'     => '',
    'prefix' => 'sess:'
];
Session::handlerClass(RedisSessionHandler::class, $config);
*/

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}

function _env(string $key, $default = null): ?string
{
    return DotEnv::$instances['server']->get($key, $default);
}

function _json(int $statusCode, array $data): Response
{
    return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
