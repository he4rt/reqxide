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
            if ($this->isHttp2OrHigher($request)) {
                foreach ($cookies as $cookie) {
                    $request = $request->withAddedHeader('Cookie', $cookie);
                }
            } else {
                $request = $request->withHeader('Cookie', implode('; ', $cookies));
            }
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

    private function isHttp2OrHigher(RequestInterface $request): bool
    {
        return in_array($request->getProtocolVersion(), ['2', '2.0', '3'], true);
    }
}
