<?php

declare(strict_types=1);

use Reqxide\Contract\ProfileInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Http1\Http1Options;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Tls\TlsOptions;

it('implements ProfileInterface', function (): void {
    $profile = new Profile;

    expect($profile)->toBeInstanceOf(ProfileInterface::class);
});

it('has correct default values', function (): void {
    $profile = new Profile;

    expect($profile->tlsOptions)->toBeNull()
        ->and($profile->http2Options)->toBeNull()
        ->and($profile->http1Options)->toBeNull()
        ->and($profile->defaultHeaders)->toBe([])
        ->and($profile->originalHeaderMap)->toBeNull();
});

it('can be constructed with all custom values', function (): void {
    $tlsOptions = new TlsOptions;
    $http2Options = new Http2Options;
    $http1Options = new Http1Options;
    $headers = ['User-Agent' => 'Mozilla/5.0', 'Accept' => 'text/html'];
    $headerMap = new OriginalHeaderMap(['User-Agent', 'Accept']);

    $profile = new Profile(
        tlsOptions: $tlsOptions,
        http2Options: $http2Options,
        http1Options: $http1Options,
        defaultHeaders: $headers,
        originalHeaderMap: $headerMap,
    );

    expect($profile->tlsOptions)->toBe($tlsOptions)
        ->and($profile->http2Options)->toBe($http2Options)
        ->and($profile->http1Options)->toBe($http1Options)
        ->and($profile->defaultHeaders)->toBe($headers)
        ->and($profile->originalHeaderMap)->toBe($headerMap);
});

it('returns tlsOptions via accessor method', function (): void {
    $tlsOptions = new TlsOptions;
    $profile = new Profile(tlsOptions: $tlsOptions);

    expect($profile->tlsOptions())->toBe($tlsOptions);
});

it('returns null tlsOptions via accessor method when not set', function (): void {
    $profile = new Profile;

    expect($profile->tlsOptions())->toBeNull();
});

it('returns http2Options via accessor method', function (): void {
    $http2Options = new Http2Options;
    $profile = new Profile(http2Options: $http2Options);

    expect($profile->http2Options())->toBe($http2Options);
});

it('returns null http2Options via accessor method when not set', function (): void {
    $profile = new Profile;

    expect($profile->http2Options())->toBeNull();
});

it('returns http1Options via accessor method', function (): void {
    $http1Options = new Http1Options;
    $profile = new Profile(http1Options: $http1Options);

    expect($profile->http1Options())->toBe($http1Options);
});

it('returns null http1Options via accessor method when not set', function (): void {
    $profile = new Profile;

    expect($profile->http1Options())->toBeNull();
});

it('returns defaultHeaders via accessor method', function (): void {
    $headers = ['Content-Type' => 'application/json'];
    $profile = new Profile(defaultHeaders: $headers);

    expect($profile->defaultHeaders())->toBe($headers);
});

it('returns empty defaultHeaders via accessor method when not set', function (): void {
    $profile = new Profile;

    expect($profile->defaultHeaders())->toBe([]);
});
