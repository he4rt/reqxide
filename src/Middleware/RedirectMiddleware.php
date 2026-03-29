<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Reqxide\Contract\RedirectPolicyInterface;
use Reqxide\Exception\RedirectException;

final readonly class RedirectMiddleware implements MiddlewareInterface
{
    private const array SENSITIVE_HEADERS = [
        'Authorization',
        'Cookie',
        'Cookie2',
        'Proxy-Authorization',
        'WWW-Authenticate',
    ];

    public function __construct(
        private RedirectPolicyInterface $policy,
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $redirectCount = 0;
        $currentRequest = $request;
        $originalUri = $request->getUri();

        while (true) {
            $response = $next($currentRequest);

            $statusCode = $response->getStatusCode();

            if ($statusCode < 300 || $statusCode >= 400) {
                return $response;
            }

            if (! $response->hasHeader('Location')) {
                return $response;
            }

            $redirectCount++;

            if (! $this->policy->shouldFollow($currentRequest, $response, $redirectCount)) {
                if ($redirectCount > $this->policy->maxRedirects() && $this->policy->maxRedirects() > 0) {
                    throw new RedirectException(
                        $currentRequest,
                        "Too many redirects ({$redirectCount}). Max: {$this->policy->maxRedirects()}.",
                    );
                }

                return $response;
            }
            $location = $response->getHeaderLine('Location');

            $newUri = $this->resolveUri($currentRequest->getUri(), $location);

            $oldMethod = $currentRequest->getMethod();
            $newMethod = $this->determineMethod($statusCode, $oldMethod);
            $currentRequest = $currentRequest
                ->withUri($newUri)
                ->withMethod($newMethod);

            // Strip body when method changes to GET (303, or 301/302 POST→GET)
            // RFC 7231 §6.4.4: the original body MUST NOT be sent on the redirected
            // request when the method changes — prevents leaking POST data.
            if ($newMethod !== $oldMethod) {
                $currentRequest = $currentRequest
                    ->withBody(Stream::create(''))
                    ->withoutHeader('Content-Type')
                    ->withoutHeader('Content-Length');
            }

            if ($this->isCrossOrigin($originalUri, $newUri)) {
                foreach (self::SENSITIVE_HEADERS as $header) {
                    $currentRequest = $currentRequest->withoutHeader($header);
                }
            }
        }
    }

    private function determineMethod(int $statusCode, string $currentMethod): string
    {
        if ($statusCode === 303) {
            return 'GET';
        }

        if (in_array($statusCode, [301, 302], true) && $currentMethod === 'POST') {
            return 'GET';
        }

        return $currentMethod;
    }

    private function isCrossOrigin(UriInterface $original, UriInterface $redirect): bool
    {
        if ($original->getHost() !== $redirect->getHost()) {
            return true;
        }

        if ($original->getPort() !== $redirect->getPort()) {
            return true;
        }

        return $original->getScheme() !== $redirect->getScheme();
    }

    private function resolveUri(UriInterface $base, string $location): UriInterface
    {
        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            return new Uri($location);
        }

        $uri = $base->withQuery('')->withFragment('');

        if (str_starts_with($location, '/')) {
            return $uri->withPath($location);
        }

        $basePath = $base->getPath();
        $lastSlash = strrpos($basePath, '/');
        $newPath = ($lastSlash !== false ? substr($basePath, 0, $lastSlash + 1) : '/').$location;

        return $uri->withPath(self::normalizePath($newPath));
    }

    /**
     * Remove dot segments from a path per RFC 3986 §5.2.4.
     */
    private static function normalizePath(string $path): string
    {
        $segments = explode('/', $path);
        $result = [];

        foreach ($segments as $segment) {
            if ($segment === '..') {
                if ($result !== [] && end($result) !== '') {
                    array_pop($result);
                }
            } elseif ($segment !== '.') {
                $result[] = $segment;
            }
        }

        $normalized = implode('/', $result);

        return str_starts_with($normalized, '/') ? $normalized : '/'.$normalized;
    }
}
