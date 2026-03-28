<?php

declare(strict_types=1);

use Reqxide\Emulation\Profile;
use Reqxide\Emulation\ProfileBuilder;
use Reqxide\Http1\Http1Options;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Tls\TlsOptions;

it('builds a Profile with default values', function (): void {
    $profile = (new ProfileBuilder)->build();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->toBeNull()
        ->and($profile->http2Options)->toBeNull()
        ->and($profile->http1Options)->toBeNull()
        ->and($profile->defaultHeaders)->toBe([])
        ->and($profile->originalHeaderMap)->toBeNull();
});

it('supports fluent tlsOptions setter', function (): void {
    $tlsOptions = new TlsOptions;
    $builder = (new ProfileBuilder)->tlsOptions($tlsOptions);

    expect($builder)->toBeInstanceOf(ProfileBuilder::class)
        ->and($builder->build()->tlsOptions)->toBe($tlsOptions);
});

it('supports fluent http2Options setter', function (): void {
    $http2Options = new Http2Options;
    $builder = (new ProfileBuilder)->http2Options($http2Options);

    expect($builder)->toBeInstanceOf(ProfileBuilder::class)
        ->and($builder->build()->http2Options)->toBe($http2Options);
});

it('supports fluent http1Options setter', function (): void {
    $http1Options = new Http1Options;
    $builder = (new ProfileBuilder)->http1Options($http1Options);

    expect($builder)->toBeInstanceOf(ProfileBuilder::class)
        ->and($builder->build()->http1Options)->toBe($http1Options);
});

it('supports fluent defaultHeaders setter', function (): void {
    $headers = ['User-Agent' => 'TestAgent', 'Accept' => '*/*'];
    $builder = (new ProfileBuilder)->defaultHeaders($headers);

    expect($builder)->toBeInstanceOf(ProfileBuilder::class)
        ->and($builder->build()->defaultHeaders)->toBe($headers);
});

it('supports fluent originalHeaderMap setter', function (): void {
    $map = new OriginalHeaderMap(['Host', 'User-Agent']);
    $builder = (new ProfileBuilder)->originalHeaderMap($map);

    expect($builder)->toBeInstanceOf(ProfileBuilder::class)
        ->and($builder->build()->originalHeaderMap)->toBe($map);
});

it('supports chaining all setters together', function (): void {
    $tlsOptions = new TlsOptions;
    $http2Options = new Http2Options;
    $http1Options = new Http1Options;
    $headers = ['User-Agent' => 'Mozilla/5.0'];
    $headerMap = new OriginalHeaderMap(['User-Agent']);

    $profile = (new ProfileBuilder)
        ->tlsOptions($tlsOptions)
        ->http2Options($http2Options)
        ->http1Options($http1Options)
        ->defaultHeaders($headers)
        ->originalHeaderMap($headerMap)
        ->build();

    expect($profile->tlsOptions)->toBe($tlsOptions)
        ->and($profile->http2Options)->toBe($http2Options)
        ->and($profile->http1Options)->toBe($http1Options)
        ->and($profile->defaultHeaders)->toBe($headers)
        ->and($profile->originalHeaderMap)->toBe($headerMap);
});
