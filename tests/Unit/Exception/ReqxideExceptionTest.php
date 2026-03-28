<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Reqxide\Exception\ReqxideException;

it('extends RuntimeException', function (): void {
    $exception = new ReqxideException('test');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});

it('implements ClientExceptionInterface', function (): void {
    $exception = new ReqxideException('test');

    expect($exception)->toBeInstanceOf(ClientExceptionInterface::class);
});

it('stores message and code', function (): void {
    $exception = new ReqxideException('Something went wrong', 42);

    expect($exception->getMessage())->toBe('Something went wrong')
        ->and($exception->getCode())->toBe(42);
});

it('stores previous exception', function (): void {
    $previous = new RuntimeException('root cause');
    $exception = new ReqxideException('wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
