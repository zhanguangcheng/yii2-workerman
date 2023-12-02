<?php

use Linkerman\Linkerman;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

Linkerman::init();

Worker::$logFile = __DIR__ . '/runtime/workerman.log';

global $worker;
$worker = new Worker('http://0.0.0.0:8080');
$worker->count = 12;
$worker->name = 'yii2-workerman';
$worker->onWorkerStart = [App::class, 'init'];
$worker->onMessage = [App::class, 'send'];
$worker->onWorkerStop = [App::class, 'stop'];

Worker::runAll();
