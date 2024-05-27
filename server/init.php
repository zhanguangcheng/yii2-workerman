<?php

use server\DotEnv;
use Workerman\Protocols\Http\Response;

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_PATH', realpath(__DIR__ . '/../'));

try {
    DotEnv::$instances['server'] = new DotEnv();
    DotEnv::$instances['server']->load(__DIR__ . '/.env');
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

function _env(string $key, $default = null): ?string
{
    return DotEnv::$instances['server']->get($key, $default);
}

function _json(int $statusCode, array $data): Response
{
    return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
