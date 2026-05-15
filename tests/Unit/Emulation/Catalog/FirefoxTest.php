<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Firefox;
use Reqxide\Emulation\Profile;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Tls\KeyShare;

it('returns a Profile with non-null tlsOptions and http2Options for each version', function (string $method): void {
    /** @var Profile $profile */
    $profile = Firefox::$method();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
})->with([
    ['v133'], ['v135'], ['v136'], ['v144'], ['v147'], ['v148'], ['v149'],
]);

it('does NOT have GREASE enabled', function (): void {
    $profile = Firefox::v136();

    expect($profile->tlsOptions?->greaseEnabled)->toBeFalse();
});

it('has ffdhe2048 in curvesList', function (): void {
    $profile = Firefox::v136();

    expect($profile->tlsOptions?->curvesList)->toContain('ffdhe2048');
});

it('has pseudo order of Method, Path, Authority, Scheme', function (): void {
    $profile = Firefox::v136();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Path,
        PseudoHeader::Authority,
        PseudoHeader::Scheme,
    ]);
});

it('has initialWindowSize of 131072', function (): void {
    $profile = Firefox::v136();

    expect($profile->http2Options?->initialWindowSize)->toBe(131072);
});

it('has Sec-Fetch-* headers', function (): void {
    $profile = Firefox::v136();

    expect($profile->defaultHeaders)->toHaveKeys([
        'Sec-Fetch-Dest',
        'Sec-Fetch-Mode',
        'Sec-Fetch-Site',
        'Sec-Fetch-User',
    ]);
});

it('has correct User-Agent containing Firefox/136 for v136', function (): void {
    $profile = Firefox::v136();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Firefox/136');
});

it('has correct User-Agent containing Firefox/135 for v135', function (): void {
    $profile = Firefox::v135();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Firefox/135');
});

it('has non-null originalHeaderMap', function (): void {
    $profile = Firefox::v136();

    expect($profile->originalHeaderMap)->not->toBeNull();
});

// curl_impersonate Firefox tests

it('v133 has MLKEM in curvesList', function (): void {
    $profile = Firefox::v133();

    expect($profile->tlsOptions?->curvesList)->toContain('X25519MLKEM768');
});

it('v133 has explicit keyShares', function (): void {
    $profile = Firefox::v133();

    expect($profile->tlsOptions?->keyShares)->toBe([KeyShare::X25519MLKEM768, KeyShare::X25519, KeyShare::P256]);
});

it('v133 has recordSizeLimit 4001', function (): void {
    $profile = Firefox::v133();

    expect($profile->tlsOptions?->recordSizeLimit)->toBe(4001);
});

it('v133 has certificateCompressors', function (): void {
    $profile = Firefox::v133();

    expect($profile->tlsOptions?->certificateCompressors)->toHaveCount(3);
});

it('v133 has delegatedCredentials', function (): void {
    $profile = Firefox::v133();

    expect($profile->tlsOptions?->delegatedCredentials)->not->toBeNull();
});

it('v133 has macOS User-Agent', function (): void {
    $profile = Firefox::v133();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Macintosh');
});

it('v133 has TE trailers header', function (): void {
    $profile = Firefox::v133();

    expect($profile->defaultHeaders)->toHaveKey('TE');
});

it('CI profiles have extensionPermutation', function (string $method): void {
    /** @var Profile $profile */
    $profile = Firefox::$method();

    expect($profile->tlsOptions?->extensionPermutation)->not->toBeNull();
})->with([['v133'], ['v144'], ['v147'], ['v148'], ['v149']]);
