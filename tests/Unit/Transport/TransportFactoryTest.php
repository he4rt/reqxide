<?php

declare(strict_types=1);

use Reqxide\Contract\TransportInterface;
use Reqxide\Transport\CurlTransport;
use Reqxide\Transport\FfiTransport;
use Reqxide\Transport\TransportFactory;

it('is a final class', function (): void {
    $reflection = new ReflectionClass(TransportFactory::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has a static create method', function (): void {
    $reflection = new ReflectionClass(TransportFactory::class);

    expect($reflection->hasMethod('create'))->toBeTrue()
        ->and($reflection->getMethod('create')->isStatic())->toBeTrue();
});

it('returns a TransportInterface implementation', function (): void {
    $transport = TransportFactory::create();

    expect($transport)->toBeInstanceOf(TransportInterface::class);
});

it('returns FfiTransport when FFI and libcurl-impersonate are available', function (): void {
    $transport = TransportFactory::create();

    expect($transport)->toBeInstanceOf(FfiTransport::class);
})->skip(
    ! extension_loaded('ffi'),
    'FFI extension is required',
)->skip(
    ! file_exists('/usr/local/lib/libcurl-impersonate.so')
    && ! file_exists('/usr/lib/libcurl-impersonate.so')
    && ! file_exists('/usr/lib/x86_64-linux-gnu/libcurl-impersonate.so')
    && ! file_exists('/usr/local/lib/libcurl-impersonate.dylib')
    && ! file_exists('/opt/homebrew/lib/libcurl-impersonate.dylib'),
    'libcurl-impersonate is not installed',
);

it('returns CurlTransport when ext-curl is available and FFI transport is not', function (): void {
    $transport = TransportFactory::create();

    expect($transport)->toBeInstanceOf(CurlTransport::class);
})->skip(
    ! extension_loaded('curl'),
    'ext-curl is not available',
)->skip(
    extension_loaded('ffi')
    && (file_exists('/usr/local/lib/libcurl-impersonate.so')
        || file_exists('/usr/lib/libcurl-impersonate.so')
        || file_exists('/usr/lib/x86_64-linux-gnu/libcurl-impersonate.so')
        || file_exists('/usr/local/lib/libcurl-impersonate.dylib')
        || file_exists('/opt/homebrew/lib/libcurl-impersonate.dylib')),
    'FfiTransport takes priority over CurlTransport on this system',
);
