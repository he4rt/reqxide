<?php

declare(strict_types=1);

namespace Reqxide\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RetryPolicyInterface
{
    public function shouldRetry(
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Throwable $exception,
        int $attempt,
    ): bool;

    public function delayMs(int $attempt): int;
}
