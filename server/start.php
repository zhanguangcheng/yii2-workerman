<?php /** @noinspection PhpUnused */

use Workerman\Worker;

require_once __DIR__ . '/init.php';

Worker::$stdoutFile = __DIR__ . '/runtime/workerman.stdout.log';
Worker::$logFile = __DIR__ . '/runtime/workerman.log';
Worker::$pidFile = __DIR__ . '/runtime/workerman.pid';

const GLOBAL_START = true;

foreach (glob(__DIR__ . '/start_*.php') as $startFile) {
    require_once $startFile;
}

Worker::runAll();
