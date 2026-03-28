<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Reqxide\Exception\ReqxideException;
use Reqxide\Exception\RetryException;

it('extends ReqxideException', function (): void {
    $exception = new RetryException('retry exhausted');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements ClientExceptionInterface', function (): void {
    $exception = new RetryException('retry exhausted');

    expect($exception)->toBeInstanceOf(ClientExceptionInterface::class);
});

it('stores message and code', function (): void {
    $exception = new RetryException('max retries exceeded', 429);

    expect($exception->getMessage())->toBe('max retries exceeded')
        ->and($exception->getCode())->toBe(429);
});

it('stores previous exception', function (): void {
    $previous = new RuntimeException('server error');
    $exception = new RetryException('retry failed', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
