<?php

use server\Monitor;

require_once __DIR__ . '/vendor/autoload.php';

$files = getProcessFiles();
$resource = popen_processes($files);

$monitor = new Monitor([
    realpath(__DIR__ . '/src'),
    realpath(__DIR__ . '/server'),
], 128 * 1024 * 1024, ['php', 'env']);

echo "\r\n";
while (true) {
    sleep(1);
    if ($monitor->checkAllFilesChange($monitor->monitorDir)) {
        $status = proc_get_status($resource);
        $pid = $status['pid'];
        shell_exec("taskkill /F /T /PID $pid");
        proc_close($resource);
        $resource = popen_processes($files);
    }
}

function popen_processes(array $files)
{
    $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $files);
    $descriptorspec = [STDIN, STDOUT, STDOUT];
    $resource = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
    if (!$resource) {
        exit("Can not execute $cmd\r\n");
    }
    return $resource;
}

/**
 * @return array
 * @noinspection PhpMissingReturnTypeInspection
 */
function getProcessFiles()
{
    $files = [];
    foreach (glob(__DIR__ . '/server/start_*.php') as $startFile) {
        $files[] = $startFile;
    }
    return $files;
}
