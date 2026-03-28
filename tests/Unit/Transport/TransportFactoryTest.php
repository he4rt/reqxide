<?php

declare(strict_types=1);

use Reqxide\Contract\TransportInterface;
use Reqxide\Transport\CurlTransport;
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

it('returns CurlTransport when ext-curl is available', function (): void {
    // ext-curl is loaded in the test runner
    if (! extension_loaded('curl')) {
        $this->markTestSkipped('ext-curl is not available.');
    }

    $transport = TransportFactory::create();

    expect($transport)->toBeInstanceOf(CurlTransport::class);
});
