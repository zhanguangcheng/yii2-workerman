<?php

/**
 * Web Server
 *
 * @noinspection PhpObjectFieldsAreOnlyWrittenInspection
 */

use Linkerman\Linkerman;
use server\App;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\RedisSessionHandler;
use Workerman\Worker;

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/init_yii.php';

Linkerman::init();

$worker = new Worker(_env('WORKER_ADDRESS'));
$worker->count = (int)_env('WORKER_COUNT', 4);
$worker->name = 'yii2-workerman-web';
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
