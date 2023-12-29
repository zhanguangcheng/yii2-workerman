<?php

namespace server\middlewares;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}
