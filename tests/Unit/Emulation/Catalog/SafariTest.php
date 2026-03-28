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
    ['v18'],
    ['iPad18'],
    ['iOS18'],
]);

it('does NOT have ECH GREASE', function (): void {
    $profile = Safari::v18();

    expect($profile->tlsOptions?->enableEchGrease)->toBeFalse();
});

it('does NOT have GREASE enabled', function (): void {
    $profile = Safari::v18();

    expect($profile->tlsOptions?->greaseEnabled)->toBeFalse();
});

it('has pseudo order of Method, Scheme, Path, Authority', function (): void {
    $profile = Safari::v18();

    expect($profile->http2Options?->headersPseudoOrder?->headers)->toBe([
        PseudoHeader::Method,
        PseudoHeader::Scheme,
        PseudoHeader::Path,
        PseudoHeader::Authority,
    ]);
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
