<?php

global $monitor_worker;

use server\Monitor;
use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

$monitor_worker = new Worker();
$monitor_worker->name = 'Monitor';
$monitor_worker->reloadable = false;
$monitor_worker->onWorkerStart = static function (): void {
    $monitor = new Monitor(
        monitorDir: [
            realpath(__DIR__ . '/../src'),
            realpath(__DIR__ . '/../server'),
            realpath(__DIR__ . '/../.env'),
        ],
        freeMemoryReload: 128 * 1024 * 1024,
        extensions: ['php'],
    );
    if (DIRECTORY_SEPARATOR === '/') {
        $monitor->processMonitor();
        $monitor->fileMonitor();
    }
};

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
