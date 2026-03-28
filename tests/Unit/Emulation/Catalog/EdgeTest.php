<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Chrome;
use Reqxide\Emulation\Catalog\Edge;
use Reqxide\Emulation\Profile;

it('returns a Profile with non-null tlsOptions and http2Options for v131', function (): void {
    $profile = Edge::v131();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
});

it('has Edg/ in User-Agent', function (): void {
    $profile = Edge::v131();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Edg/');
});

it('has Microsoft Edge in sec-ch-ua', function (): void {
    $profile = Edge::v131();

    expect($profile->defaultHeaders['sec-ch-ua'])->toContain('Microsoft Edge');
});

it('has the same TLS cipher list as Chrome', function (): void {
    $edgeProfile = Edge::v131();
    $chromeProfile = Chrome::v131();

    expect($edgeProfile->tlsOptions?->cipherList)
        ->toBe($chromeProfile->tlsOptions?->cipherList);
});
