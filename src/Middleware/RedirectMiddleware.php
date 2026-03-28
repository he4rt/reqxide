<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

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

            if (! $this->policy->shouldFollow($currentRequest, $response, $redirectCount)) {
                if ($redirectCount >= $this->policy->maxRedirects() && $this->policy->maxRedirects() > 0) {
                    throw new RedirectException(
                        $currentRequest,
                        "Too many redirects ({$redirectCount}). Max: {$this->policy->maxRedirects()}.",
                    );
                }

                return $response;
            }

            $redirectCount++;
            $location = $response->getHeaderLine('Location');

            $newUri = $this->resolveUri($currentRequest->getUri(), $location);

            $newMethod = $this->determineMethod($statusCode, $currentRequest->getMethod());
            $currentRequest = $currentRequest
                ->withUri($newUri)
                ->withMethod($newMethod);

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

        return $uri->withPath($newPath);
    }
}
