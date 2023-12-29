<?php /** @noinspection PhpUnused */

use Linkerman\Linkerman;
use server\App;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\RedisSessionHandler;
use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

Linkerman::init();

define('APP_PATH', realpath(__DIR__ . '/../'));
_parse_env();
define("YII_DEBUG", $_ENV['YII_DEBUG'] ?? true);
define("YII_ENV", $_ENV['YII_ENV'] ?? 'dev');
const YII_ENABLE_ERROR_HANDLER = false;
const ENV_WORKERMAN = true;

require_once APP_PATH . '/vendor/yiisoft/yii2/Yii.php';

Worker::$stdoutFile = APP_PATH . '/src/runtime/workerman.stdout.log';
Worker::$logFile = APP_PATH . '/src/runtime/workerman.log';
Worker::$pidFile = APP_PATH . '/src/runtime/workerman.pid';

global $worker;
$worker = new Worker($_ENV['WORKER_ADDRESS']);
$worker->count = $_ENV['WORKER_COUNT'] ?? 4;
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

function _parse_env(): void
{
    $envFile = APP_PATH . '/.env';
    if (!file_exists($envFile)) {
        copy("$envFile.example", $envFile);
    }
    foreach (parse_ini_file($envFile) as $key => $value) {
        $_ENV[$key] = $value;
    }
}

function _json(int $statusCode, array $data): Response
{
    return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
