<?php

declare(strict_types=1);

use Reqxide\Emulation\Catalog\Chrome;
use Reqxide\Emulation\Profile;
use Reqxide\Http2\PseudoHeader;
use Reqxide\Http2\SettingId;

it('returns a Profile with non-null tlsOptions and http2Options for each version', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->tlsOptions)->not->toBeNull()
        ->and($profile->http2Options)->not->toBeNull();
})->with([
    ['v99'], ['v100'], ['v101'], ['v104'], ['v107'],
    ['v110'], ['v116'],
    ['v119'], ['v120'], ['v123'], ['v124'],
    ['v128'], ['v129'], ['v130'], ['v131'],
    ['v133a'], ['v136'], ['v142'], ['v145'], ['v146'], ['v147'],
    ['v99Android'], ['v131Android'],
]);

it('has X25519MLKEM768 in curvesList for Chrome 131', function (): void {
    $profile = Chrome::v131();

    expect($profile->tlsOptions?->curvesList)->toContain('X25519MLKEM768');
});

it('does not have X25519MLKEM768 in curvesList for Chrome 128', function (): void {
    $profile = Chrome::v128();

    expect($profile->tlsOptions?->curvesList)->not->toContain('X25519MLKEM768');
});

it('has correct User-Agent containing Chrome/131 for v131', function (): void {
    $profile = Chrome::v131();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Chrome/131');
});

it('has correct sec-ch-ua for Chrome 131', function (): void {
    $profile = Chrome::v131();

    expect($profile->defaultHeaders['sec-ch-ua'])
        ->toBe('"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"');
});

it('has correct HTTP/2 pseudo order of Method, Authority, Scheme, Path', function (): void {
    $profile = Chrome::v131();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Authority,
        PseudoHeader::Scheme,
        PseudoHeader::Path,
    ]);
});

it('has expected HTTP/2 settings values', function (): void {
    $profile = Chrome::v131();
    $http2 = $profile->http2Options;

    expect($http2?->initialWindowSize)->toBe(6291456)
        ->and($http2?->headerTableSize)->toBe(65536)
        ->and($http2?->enablePush)->toBeFalse()
        ->and($http2?->maxConcurrentStreams)->toBeNull()
        ->and($http2?->maxFrameSize)->toBeNull()
        ->and($http2?->maxHeaderListSize)->toBe(262144)
        ->and($http2?->initialConnWindowSize)->toBe(15663105);
});

it('has expected HTTP/2 settings order', function (): void {
    $profile = Chrome::v131();

    expect($profile->http2Options?->settingsOrder?->settings)->toBe([
        SettingId::HeaderTableSize,
        SettingId::EnablePush,
        SettingId::InitialWindowSize,
        SettingId::MaxHeaderListSize,
    ]);
});

it('has non-null originalHeaderMap', function (): void {
    $profile = Chrome::v131();

    expect($profile->originalHeaderMap)->not->toBeNull();
});

// Era-specific tests for curl_impersonate profiles

it('era 1 does not permute extensions', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->tlsOptions?->permuteExtensions)->toBeFalse();
})->with([['v99'], ['v100'], ['v101'], ['v104'], ['v107']]);

it('era 2 enables permute extensions', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->tlsOptions?->permuteExtensions)->toBeTrue();
})->with([['v110'], ['v116']]);

it('era 3+ enables ECH GREASE', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->tlsOptions?->enableEchGrease)->toBeTrue();
})->with([['v119'], ['v120'], ['v123'], ['v124'], ['v133a'], ['v136']]);

it('era 1-2 does not enable ECH GREASE', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->tlsOptions?->enableEchGrease)->toBeFalse();
})->with([['v99'], ['v107'], ['v110'], ['v116']]);

it('v124 uses Kyber curves', function (): void {
    $profile = Chrome::v124();

    expect($profile->tlsOptions?->curvesList)->toContain('X25519Kyber768Draft00');
});

it('v133a+ uses ALPS new codepoint', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->tlsOptions?->alpsUseNewCodepoint)->toBeTrue();
})->with([['v133a'], ['v136'], ['v142'], ['v145'], ['v146']]);

it('era 3 without maxConcurrentStreams in HTTP/2', function (): void {
    $profile = Chrome::v119();

    expect($profile->http2Options?->maxConcurrentStreams)->toBeNull();
});

it('era 1 has maxConcurrentStreams 1000 in HTTP/2', function (): void {
    $profile = Chrome::v99();

    expect($profile->http2Options?->maxConcurrentStreams)->toBe(1000);
});

it('android variants use Android platform', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->defaultHeaders['sec-ch-ua-platform'])->toBe('"Android"');
})->with([['v99Android'], ['v131Android']]);

it('v99Android has mobile User-Agent', function (): void {
    $profile = Chrome::v99Android();

    expect($profile->defaultHeaders['User-Agent'])->toContain('Mobile');
});

it('v124+ has Priority header', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->defaultHeaders)->toHaveKey('Priority');
})->with([['v124'], ['v133a'], ['v136']]);

it('era 1-3 does not have Priority header', function (string $method): void {
    /** @var Profile $profile */
    $profile = Chrome::$method();

    expect($profile->defaultHeaders)->not->toHaveKey('Priority');
})->with([['v99'], ['v110'], ['v119']]);
