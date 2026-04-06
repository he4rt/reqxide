<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Chrome;
use Reqxide\Emulation\Catalog\Edge;
use Reqxide\Emulation\Profile;

it('returns a Profile with non-null tlsOptions and http2Options', function (string $method): void {
    /** @var Profile $profile */
    $profile = Edge::$method();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
})->with([['v99'], ['v101'], ['v131']]);

it('has Edg/ in User-Agent', function (string $method): void {
    /** @var Profile $profile */
    $profile = Edge::$method();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Edg/');
})->with([['v99'], ['v101'], ['v131']]);

it('has Microsoft Edge in sec-ch-ua', function (string $method): void {
    /** @var Profile $profile */
    $profile = Edge::$method();

    expect($profile->defaultHeaders['sec-ch-ua'])->toContain('Microsoft Edge');
})->with([['v99'], ['v101'], ['v131']]);

it('has the same TLS cipher list as Chrome v131', function (): void {
    $edgeProfile = Edge::v131();
    $chromeProfile = Chrome::v131();

    expect($edgeProfile->tlsOptions?->cipherList)
        ->toBe($chromeProfile->tlsOptions?->cipherList);
});

it('v99 shares TLS with Chrome v99', function (): void {
    $edgeProfile = Edge::v99();
    $chromeProfile = Chrome::v99();

    expect($edgeProfile->tlsOptions?->cipherList)
        ->toBe($chromeProfile->tlsOptions?->cipherList);
});
