<?php

namespace server\middlewares;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use yii\redis\Connection;

/**
 * Rate limiter middleware
 * By default, IP is used as the key
 */
class RateLimiter implements MiddlewareInterface
{
    public function __construct(
        private readonly int        $limit,
        private readonly int        $window,
        private readonly Connection $redis
    )
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!$this->check($request)) {
            return _json(429, ['code' => 429, 'message' => 'Too Many Requests']);
        }
        return $next($request);
    }

    public function check(Request $request): bool
    {
        $key = 'limit:ip:' . $request->connection->getRemoteIp();
        [$allowance, $timestamp] = $this->loadAllowance($key);

        $current = time();

        $allowance += (int)(($current - $timestamp) * $this->limit / $this->window);
        if ($allowance > $this->limit) {
            $allowance = $this->limit;
        }

        if ($allowance < 1) {
            return false;
        }

        $this->saveAllowance($key, $allowance - 1, $current);
        return true;
    }

    private function loadAllowance($key): array
    {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : [$this->limit, time()];
    }

    private function saveAllowance($key, $allowance, $timestamp): void
    {
        $data = json_encode([$allowance, $timestamp]);
        $this->redis->setex($key, $this->window, $data);
    }
}
