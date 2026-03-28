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
    ['v131'],
    ['v130'],
    ['v129'],
    ['v128'],
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
        ->toBe('"Chromium";v="131", "Not_A Brand";v="24"');
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
        ->and($http2?->maxConcurrentStreams)->toBe(1000)
        ->and($http2?->maxFrameSize)->toBe(16384)
        ->and($http2?->maxHeaderListSize)->toBe(262144)
        ->and($http2?->initialConnWindowSize)->toBe(15663105);
});

it('has expected HTTP/2 settings order', function (): void {
    $profile = Chrome::v131();

    expect($profile->http2Options?->settingsOrder?->settings)->toBe([
        SettingId::HeaderTableSize,
        SettingId::EnablePush,
        SettingId::MaxConcurrentStreams,
        SettingId::InitialWindowSize,
        SettingId::MaxFrameSize,
        SettingId::MaxHeaderListSize,
    ]);
});

it('has non-null originalHeaderMap', function (): void {
    $profile = Chrome::v131();

    expect($profile->originalHeaderMap)->not->toBeNull();
});
