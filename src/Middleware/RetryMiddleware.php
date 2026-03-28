<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\RetryPolicyInterface;

final readonly class RetryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RetryPolicyInterface $policy,
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $next($request);

                if ($this->policy->shouldRetry($request, $response, null, $attempt)) {
                    $attempt++;
                    $delayMs = $this->policy->delayMs($attempt - 1);

                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }

                    continue;
                }

                return $response;
            } catch (\Throwable $exception) {
                if ($this->policy->shouldRetry($request, null, $exception, $attempt)) {
                    $attempt++;
                    $delayMs = $this->policy->delayMs($attempt - 1);

                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }

                    continue;
                }

                throw $exception;
            }
        }
    }
}
