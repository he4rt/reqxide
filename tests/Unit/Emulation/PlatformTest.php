<?php

declare(strict_types=1);

use Reqxide\Emulation\Platform;

it('has the correct number of cases', function (): void {
    expect(Platform::cases())->toHaveCount(5);
});

it('has correct backed values', function (Platform $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [Platform::MacOS, 'macos'],
    [Platform::Windows, 'windows'],
    [Platform::Linux, 'linux'],
    [Platform::Android, 'android'],
    [Platform::IOS, 'ios'],
]);

it('returns correct sec-ch-ua-platform', function (Platform $case, string $expected): void {
    expect($case->secChUaPlatform())->toBe($expected);
})->with([
    [Platform::MacOS, '"macOS"'],
    [Platform::Windows, '"Windows"'],
    [Platform::Linux, '"Linux"'],
    [Platform::Android, '"Android"'],
    [Platform::IOS, '"iOS"'],
]);

it('identifies mobile platforms', function (): void {
    expect(Platform::Android->isMobile())->toBeTrue()
        ->and(Platform::IOS->isMobile())->toBeTrue()
        ->and(Platform::MacOS->isMobile())->toBeFalse()
        ->and(Platform::Windows->isMobile())->toBeFalse()
        ->and(Platform::Linux->isMobile())->toBeFalse();
});

it('returns platform-specific UA string', function (Platform $case): void {
    expect($case->userAgentPlatform())->toBeString()->not->toBeEmpty();
})->with(Platform::cases());

it('random returns a valid Platform', function (): void {
    $platform = Platform::random();

    expect($platform)->toBeInstanceOf(Platform::class);
});
