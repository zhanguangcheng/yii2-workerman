<?php

use app\App;
use Linkerman\Linkerman;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\RedisSessionHandler;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

Linkerman::init();
_parse_env();
define("YII_DEBUG", $_ENV['YII_DEBUG'] ?? true);
define("YII_ENV", $_ENV['YII_ENV'] ?? 'dev');
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

Worker::$pidFile = __DIR__ . '/runtime/workerman.pid';
Worker::$logFile = __DIR__ . '/runtime/workerman.log';
Worker::$stdoutFile = __DIR__ . '/runtime/workerman.stdout.log';

global $worker;
$worker = new Worker($_ENV['WORKER_ADDRESS']);
$worker->count = $_ENV['WORKER_COUNT'] ?? 4;
$worker->name = 'yii2-workerman';
$worker->onWorkerStart = [App::class, 'init'];
$worker->onMessage = [App::class, 'send'];
$worker->onWorkerStop = [App::class, 'stop'];

// Save session to Redis
/*
$config = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 0,
    'timeout' => 2,
    // 'auth'     => '',
    'prefix' => 'sess_'
];
Session::handlerClass(RedisSessionHandler::class, $config);
*/

// Automatically reload after file changes or process memory usage is too large
if (DIRECTORY_SEPARATOR === '/') {
    require_once __DIR__ . '/monitor.php';
}

Worker::runAll();

function _parse_env(): void
{
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        copy("$envFile.example", $envFile);
    }
    foreach (parse_ini_file($envFile) as $key => $value) {
        $_ENV[$key] = $value;
    }
}
