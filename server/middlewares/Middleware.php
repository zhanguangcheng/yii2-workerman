<?php

namespace server\middlewares;

use InvalidArgumentException;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

class Middleware
{
    /**
     * @var MiddlewareInterface[]|callable[]
     */
    public array $middlewares = [];

    /**
     * @throws InvalidArgumentException
     */
    public function load(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $this->middlewares[] = $middleware;
            } elseif (is_callable($middleware)) {
                $this->middlewares[] = $middleware;
            } else {
                throw new InvalidArgumentException('Invalid middleware configure');
            }
        }
    }

    public function call(Request $request, callable $finalCallback): Response
    {
        $next = $finalCallback;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function (Request $request) use ($middleware, $next) {
                if ($middleware instanceof MiddlewareInterface) {
                    return $middleware->process($request, $next);
                } else {
                    return $middleware($request, $next);
                }
            };
        }
        return $next($request);
    }
}
