<?php

use Workerman\Events\EventInterface;
use Workerman\Worker;

$monitor_dir = __DIR__;
global $monitor_worker;
$monitor_worker = new Worker();
$monitor_worker->name = 'FileMonitor';
$monitor_worker->reloadable = false;
$monitor_files = [];

$monitor_worker->onWorkerStart = static function ($worker): void {
    if (!extension_loaded('inotify')) {
        echo "FileMonitor : Please install inotify extension.\n";
        return;
    }
    global $monitor_dir, $monitor_files;
    $worker->inotifyFd = inotify_init();
    stream_set_blocking($worker->inotifyFd, 0);
    $dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator);
    foreach ($iterator as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
            continue;
        }
        $wd = inotify_add_watch($worker->inotifyFd, $file, IN_MODIFY);
        $monitor_files[$wd] = $file;
    }
    Worker::$globalEvent->add($worker->inotifyFd, EventInterface::EV_READ, static function($inotify_fd) use($monitor_files): void {
        $events = inotify_read($inotify_fd);
        if ($events) {
            foreach ($events as $ev) {
                $file = $monitor_files[$ev['wd']];
                // echo $file . " update and reload\n";
                unset($monitor_files[$ev['wd']]);
                $wd = inotify_add_watch($inotify_fd, $file, IN_MODIFY);
                $monitor_files[$wd] = $file;
            }
            posix_kill(posix_getppid(), SIGUSR1);
        }
    });
};
