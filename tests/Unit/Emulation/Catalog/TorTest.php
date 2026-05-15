<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Tor;
use Reqxide\Emulation\Profile;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Tls\KeyShare;

it('returns a Profile with non-null tlsOptions and http2Options', function (): void {
    $profile = Tor::v145();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
});

it('has Firefox-like User-Agent', function (): void {
    $profile = Tor::v145();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Firefox/128.0');
});

it('has Sec-GPC header', function (): void {
    $profile = Tor::v145();

    expect($profile->defaultHeaders)->toHaveKey('Sec-GPC')
        ->and($profile->defaultHeaders['Sec-GPC'])->toBe('1');
});

it('has explicit keyShares', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->keyShares)->toBe([KeyShare::X25519, KeyShare::P256]);
});

it('has recordSizeLimit 16385', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->recordSizeLimit)->toBe(16385);
});

it('does not have MLKEM in curves', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->curvesList)->not->toContain('MLKEM');
});

it('has ffdhe2048 in curvesList', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->curvesList)->toContain('ffdhe2048');
});

it('has certificateCompressors', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->certificateCompressors)->toHaveCount(3);
});

it('has delegatedCredentials', function (): void {
    $profile = Tor::v145();

    expect($profile->tlsOptions?->delegatedCredentials)->not->toBeNull();
});

it('has pseudo order mpas', function (): void {
    $profile = Tor::v145();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Path,
        PseudoHeader::Authority,
        PseudoHeader::Scheme,
    ]);
});

it('has TE trailers header', function (): void {
    $profile = Tor::v145();

    expect($profile->defaultHeaders)->toHaveKey('TE')
        ->and($profile->defaultHeaders['TE'])->toBe('trailers');
});

it('has non-null originalHeaderMap', function (): void {
    $profile = Tor::v145();

    expect($profile->originalHeaderMap)->not->toBeNull();
});
