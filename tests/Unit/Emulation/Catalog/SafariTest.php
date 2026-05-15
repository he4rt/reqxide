<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Safari;
use Reqxide\Emulation\Profile;
use Reqxide\Http2\PseudoHeader;

it('returns a Profile with non-null tlsOptions and http2Options for each version', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
})->with([
    ['v18'], ['iPad18'], ['iOS18'],
    ['v153'], ['v155'], ['v170'], ['v172iOS'],
    ['v184'], ['v184iOS'], ['v260'], ['v260iOS'],
]);

it('does NOT have ECH GREASE', function (): void {
    $profile = Safari::v18();

    expect($profile->tlsOptions?->enableEchGrease)->toBeFalse();
});

it('has GREASE enabled for v18', function (): void {
    $profile = Safari::v18();

    expect($profile->tlsOptions?->greaseEnabled)->toBeTrue();
});

it('has iPad in User-Agent for iPad18', function (): void {
    $profile = Safari::iPad18();

    expect($profile->defaultHeaders['User-Agent'])->toContain('iPad');
});

it('has iPhone in User-Agent for iOS18', function (): void {
    $profile = Safari::iOS18();

    expect($profile->defaultHeaders['User-Agent'])->toContain('iPhone');
});

it('has Macintosh in User-Agent for v18', function (): void {
    $profile = Safari::v18();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Macintosh');
});

it('has non-null originalHeaderMap', function (): void {
    $profile = Safari::v18();

    expect($profile->originalHeaderMap)->not->toBeNull();
});

// Era-specific tests for curl_impersonate profiles

it('15.x uses TLS 1.0 minimum', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile->tlsOptions?->minTlsVersion?->value)->toBe('1.0');
})->with([['v153'], ['v155']]);

it('15.x has GREASE enabled', function (): void {
    $profile = Safari::v153();

    expect($profile->tlsOptions?->greaseEnabled)->toBeTrue();
});

it('15.3 has 3DES ciphers', function (): void {
    $profile = Safari::v153();

    expect($profile->tlsOptions?->cipherList)->toContain('DES-CBC3-SHA');
});

it('15.x has no session ticket', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile->tlsOptions?->sessionTicket)->toBeFalse();
})->with([['v153'], ['v155']]);

it('17.x uses pseudo mspa', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Scheme,
        PseudoHeader::Path,
        PseudoHeader::Authority,
    ]);
})->with([['v170'], ['v172iOS']]);

it('18.x+ uses pseudo msap', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Scheme,
        PseudoHeader::Authority,
        PseudoHeader::Path,
    ]);
})->with([['v18'], ['iPad18'], ['iOS18'], ['v184'], ['v184iOS'], ['v260'], ['v260iOS']]);

it('26.x uses TLS 1.2 minimum', function (string $method): void {
    /** @var Profile $profile */
    $profile = Safari::$method();

    expect($profile->tlsOptions?->minTlsVersion?->value)->toBe('1.2');
})->with([['v260'], ['v260iOS']]);

it('26.0 desktop has MLKEM curves', function (): void {
    $profile = Safari::v260();

    expect($profile->tlsOptions?->curvesList)->toContain('X25519MLKEM768');
});

it('26.0 iOS does not have MLKEM curves', function (): void {
    $profile = Safari::v260iOS();

    expect($profile->tlsOptions?->curvesList)->not->toContain('X25519MLKEM768');
});

it('26.x has zstd encoding', function (): void {
    $profile = Safari::v260();

    expect($profile->defaultHeaders['Accept-Encoding'])->toContain('zstd');
});

it('26.x has session ticket enabled', function (): void {
    $profile = Safari::v260();

    expect($profile->tlsOptions?->sessionTicket)->toBeTrue();
});

it('172iOS has iPhone User-Agent', function (): void {
    $profile = Safari::v172iOS();

    expect($profile->defaultHeaders['User-Agent'])->toContain('iPhone');
});
