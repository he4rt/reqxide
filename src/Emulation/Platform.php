<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

enum Platform: string
{
    case MacOS = 'macos';
    case Windows = 'windows';
    case Linux = 'linux';
    case Android = 'android';
    case IOS = 'ios';

    public function secChUaPlatform(): string
    {
        return match ($this) {
            self::MacOS => '"macOS"',
            self::Windows => '"Windows"',
            self::Linux => '"Linux"',
            self::Android => '"Android"',
            self::IOS => '"iOS"',
        };
    }

    public function isMobile(): bool
    {
        return match ($this) {
            self::Android, self::IOS => true,
            default => false,
        };
    }

    public function userAgentPlatform(): string
    {
        return match ($this) {
            self::MacOS => 'Macintosh; Intel Mac OS X 10_15_7',
            self::Windows => 'Windows NT 10.0; Win64; x64',
            self::Linux => 'X11; Linux x86_64',
            self::Android => 'Linux; Android 10; K',
            self::IOS => 'iPhone; CPU iPhone OS 18_0 like Mac OS X',
        };
    }

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
