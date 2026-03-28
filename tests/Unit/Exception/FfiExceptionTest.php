<?php

declare(strict_types=1);

use Psr\Http\Client\ClientExceptionInterface;
use Reqxide\Exception\FfiException;
use Reqxide\Exception\ReqxideException;
use Reqxide\Exception\TransportException;

it('extends TransportException', function (): void {
    $exception = new FfiException('ffi failed');

    expect($exception)->toBeInstanceOf(TransportException::class);
});

it('extends ReqxideException', function (): void {
    $exception = new FfiException('ffi error');

    expect($exception)->toBeInstanceOf(ReqxideException::class);
});

it('implements ClientExceptionInterface', function (): void {
    $exception = new FfiException('ffi error');

    expect($exception)->toBeInstanceOf(ClientExceptionInterface::class);
});

it('stores message and code', function (): void {
    $exception = new FfiException('FFI extension not loaded', 42);

    expect($exception->getMessage())->toBe('FFI extension not loaded')
        ->and($exception->getCode())->toBe(42);
});

it('stores previous exception', function (): void {
    $previous = new RuntimeException('underlying error');
    $exception = new FfiException('wrapper', 0, $previous);

    expect($exception->getPrevious())->toBe($previous);
});
