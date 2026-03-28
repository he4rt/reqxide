<?php

declare(strict_types=1);

use Reqxide\Exception\FfiException;
use Reqxide\Transport\Ffi\FfiWrapper;

it('throws FfiException when header file cannot be read', function (): void {
    new FfiWrapper('/nonexistent/path/to/header.h', '/usr/lib/libcurl.so');
})->throws(FfiException::class, 'Could not read FFI header file: /nonexistent/path/to/header.h')
    ->skip(! extension_loaded('ffi'), 'FFI extension is required for this test');

it('throws FfiException when FFI extension is not loaded', function (): void {
    new FfiWrapper('/some/header.h', '/some/lib.so');
})->throws(FfiException::class, 'PHP FFI extension is not loaded.')
    ->skip(extension_loaded('ffi'), 'This test requires FFI extension to NOT be loaded');

it('is a non-final class', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->isFinal())->toBeFalse();
});

it('has easyInit method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easyInit'))->toBeTrue();
});

it('has easySetopt method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easySetopt'))->toBeTrue();
});

it('has easyPerform method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easyPerform'))->toBeTrue();
});

it('has easyGetinfo method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easyGetinfo'))->toBeTrue();
});

it('has easyStrerror method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easyStrerror'))->toBeTrue();
});

it('has easyCleanup method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('easyCleanup'))->toBeTrue();
});

it('has slistAppend method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('slistAppend'))->toBeTrue();
});

it('has slistFreeAll method', function (): void {
    $reflection = new ReflectionClass(FfiWrapper::class);

    expect($reflection->hasMethod('slistFreeAll'))->toBeTrue();
});
