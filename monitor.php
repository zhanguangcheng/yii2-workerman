<?php

use Workerman\Lib\Timer;
use Workerman\Worker;

global $monitor_worker;
$monitor_worker = new Worker();
$monitor_worker->name = 'Monitor';
$monitor_worker->reloadable = false;
$monitor_worker->onWorkerStart = static function (): void {
    new Monitor(
        monitorDir: [
            __DIR__ . '/commands',
            __DIR__ . '/config',
            __DIR__ . '/controllers',
            __DIR__ . '/views',
            __DIR__ . '/App.php',
        ],
        freeMemoryReload: 128 * 1024 * 1024,
        extensions: ['php'],
    );
};

class Monitor
{
    public function __construct(public array $monitorDir, public int $freeMemoryReload, public array $extensions = [])
    {
        if (!Worker::getAllWorkers()) {
            return;
        }
        $this->processMonitor();
        $this->fileMonitor();
    }

    public function processMonitor(): void
    {
        $memoryLimit = self::getMemoryLimit();
        Timer::add(60, [$this, 'checkMemory'], [$memoryLimit]);
    }

    public function fileMonitor(): void
    {
        $disableFunctions = explode(',', ini_get('disable_functions'));
        if (in_array('exec', $disableFunctions, true)) {
            echo "\nMonitor file change turned off because exec() has been disabled by disable_functions setting in php.ini\n";
        } else {
            Timer::add(1, function () {
                foreach ($this->monitorDir as $dir) {
                    $this->checkFilesChange($dir);
                }
            });
        }
    }

    /**
     * @param $monitorDir
     * @return bool
     */
    public function checkFilesChange($monitorDir): bool
    {
        static $lastMtime, $tooManyFilesCheck;
        if (!$lastMtime) {
            $lastMtime = time();
        }
        clearstatcache();
        if (!is_dir($monitorDir)) {
            if (!is_file($monitorDir)) {
                return false;
            }
            $iterator = [new SplFileInfo($monitorDir)];
        } else {
            // recursive traversal directory
            $dirIterator = new RecursiveDirectoryIterator($monitorDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS);
            $iterator = new RecursiveIteratorIterator($dirIterator);
        }
        $count = 0;
        foreach ($iterator as $file) {
            $count++;
            /** var SplFileInfo $file */
            if (is_dir($file->getRealPath())) {
                continue;
            }
            // check mtime
            if (in_array($file->getExtension(), $this->extensions, true) && $lastMtime < $file->getMTime()) {
                $var = 0;
                exec('"' . PHP_BINARY . '" -l ' . $file, $out, $var);
                $lastMtime = $file->getMTime();
                if ($var) {
                    continue;
                }
                echo $file . " update and reload\n";
                // send SIGUSR1 signal to master process for reload
                if (DIRECTORY_SEPARATOR === '/') {
                    posix_kill(posix_getppid(), SIGUSR1);
                } else {
                    return true;
                }
                break;
            }
        }
        if (!$tooManyFilesCheck && $count > 1000) {
            echo "Monitor: There are too many files ($count files) in $monitorDir which makes file monitoring very slow\n";
            $tooManyFilesCheck = 1;
        }
        return false;
    }

    public function checkMemory($memoryLimit): void
    {
        $freeMemory = self::getFreeMemory();
        $reload = $freeMemory < $this->freeMemoryReload;
        if ($reload) {
            echo "free memory is too low(" . self::formatBytes($freeMemory) . "), reload\n";
            posix_kill(posix_getppid(), SIGUSR1);
            return;
        }

        $ppid = posix_getppid();
        $childrenFile = "/proc/$ppid/task/$ppid/children";
        if (!is_file($childrenFile) || !($children = file_get_contents($childrenFile))) {
            return;
        }
        foreach (explode(' ', $children) as $pid) {
            $pid = (int)$pid;
            $statusFile = "/proc/$pid/status";
            if (!is_file($statusFile) || !($status = file_get_contents($statusFile))) {
                continue;
            }
            $mem = 0;
            if (preg_match('/VmRSS\s*?:\s*?(\d+?)\s*?kB/', $status, $match)) {
                $mem = $match[1];
            }
            $mem = (int)($mem * 1024);
            if ($mem >= $memoryLimit * 0.8) {
                echo "child process $pid memory usage is too big(" . self::formatBytes($mem) . "), reload\n";
                posix_kill($pid, SIGINT);
            }
        }
    }

    public static function getFreeMemory(): int
    {
        $meminfo = file_get_contents("/proc/meminfo");
        if (!$meminfo) {
            return -1;
        }
        if (!preg_match('/MemTotal\s*:+\s*([\d.]+).+?MemFree\s*:+\s*([\d.]+).+?Cached\s*:+\s*([\d.]+).+?/s', $meminfo, $matches)) {
            return -1;
        }
        $free = $matches[2] * 1024;
        $cached = $matches[3] * 1024;
        return $free + $cached;
    }

    public static function getMemoryLimit(): float|int
    {
        $memoryLimit = ini_get('memory_limit');
        $unit = strtoupper(substr($memoryLimit, -1));
        $limit = (int)$memoryLimit;
        return match ($unit) {
            'G' => $limit * 1024 * 1024 * 1024,
            'M' => $limit * 1024 * 1024,
            'K' => $limit * 1024,
            default => $limit,
        };
    }

    public static function formatBytes($size, $delimiter = ''): string
    {
        static $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'NB', 'DB');
        for ($i = 0; $size >= 1024 && $i < 10; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}
