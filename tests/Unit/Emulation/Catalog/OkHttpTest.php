<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\OkHttp;
use Reqxide\Emulation\Profile;

it('returns a Profile with non-null tlsOptions but null http2Options for each version', function (string $method): void {
    /** @var Profile $profile */
    $profile = OkHttp::$method();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->toBeNull();
})->with([
    ['v5'],
    ['v4'],
]);

it('has okhttp in User-Agent', function (): void {
    $profile = OkHttp::v5();

    expect($profile->defaultHeaders['User-Agent'])->toContain('okhttp');
});

it('has 5.0 in User-Agent for v5', function (): void {
    $profile = OkHttp::v5();

    expect($profile->defaultHeaders['User-Agent'])->toContain('5.0');
});

it('has 4.12 in User-Agent for v4', function (): void {
    $profile = OkHttp::v4();

    expect($profile->defaultHeaders['User-Agent'])->toContain('4.12');
});

it('does NOT have browser headers', function (): void {
    $profile = OkHttp::v5();

    expect($profile->defaultHeaders)->not->toHaveKey('sec-ch-ua')
        ->and($profile->defaultHeaders)->not->toHaveKey('Accept-Language');
});

it('has non-null originalHeaderMap', function (): void {
    $profile = OkHttp::v5();

    expect($profile->originalHeaderMap)->not->toBeNull();
});
