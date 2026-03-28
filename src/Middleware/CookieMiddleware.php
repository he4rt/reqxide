<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\CookieStoreInterface;

final readonly class CookieMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CookieStoreInterface $jar,
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $cookies = $this->jar->getCookies($request->getUri());

        if ($cookies !== []) {
            $cookieString = implode('; ', $cookies);
            $request = $request->withHeader('Cookie', $cookieString);
        }

        $response = $next($request);

        if ($response->hasHeader('Set-Cookie')) {
            $this->jar->setCookies(
                array_values($response->getHeader('Set-Cookie')),
                $request->getUri(),
            );
        }

        return $response;
    }
}
