<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @param  callable(RequestInterface): ResponseInterface  $next
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
}
