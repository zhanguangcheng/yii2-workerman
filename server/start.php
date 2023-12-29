<?php /** @noinspection PhpUnused */

use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

const GLOBAL_START = true;

foreach (glob(__DIR__ . '/start_*.php') as $startFile) {
    require_once $startFile;
}

Worker::runAll();
