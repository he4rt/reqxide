<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

enum Browser: string
{
    // Chrome Desktop
    case Chrome99 = 'chrome_99';
    case Chrome100 = 'chrome_100';
    case Chrome101 = 'chrome_101';
    case Chrome104 = 'chrome_104';
    case Chrome107 = 'chrome_107';
    case Chrome110 = 'chrome_110';
    case Chrome116 = 'chrome_116';
    case Chrome119 = 'chrome_119';
    case Chrome120 = 'chrome_120';
    case Chrome123 = 'chrome_123';
    case Chrome124 = 'chrome_124';
    case Chrome128 = 'chrome_128';
    case Chrome129 = 'chrome_129';
    case Chrome130 = 'chrome_130';
    case Chrome131 = 'chrome_131';
    case Chrome133a = 'chrome_133a';
    case Chrome136 = 'chrome_136';
    case Chrome142 = 'chrome_142';
    case Chrome145 = 'chrome_145';
    case Chrome146 = 'chrome_146';
    case Chrome147 = 'chrome_147';

    // Chrome Android
    case Chrome99Android = 'chrome_99_android';
    case Chrome131Android = 'chrome_131_android';

    // Firefox
    case Firefox133 = 'firefox_133';
    case Firefox135 = 'firefox_135';
    case Firefox136 = 'firefox_136';
    case Firefox144 = 'firefox_144';
    case Firefox147 = 'firefox_147';
    case Firefox148 = 'firefox_148';
    case Firefox149 = 'firefox_149';

    // Safari
    case Safari153 = 'safari_15_3';
    case Safari155 = 'safari_15_5';
    case Safari170 = 'safari_17_0';
    case Safari172iOS = 'safari_17_2_ios';
    case Safari18 = 'safari_18';
    case SafariIPad18 = 'safari_ipad_18';
    case SafariIOS18 = 'safari_ios_18';
    case Safari184 = 'safari_18_4';
    case Safari184iOS = 'safari_18_4_ios';
    case Safari260 = 'safari_26_0';
    case Safari260iOS = 'safari_26_0_ios';

    // Edge
    case Edge99 = 'edge_99';
    case Edge101 = 'edge_101';
    case Edge131 = 'edge_131';

    // OkHttp
    case OkHttp5 = 'okhttp_5';
    case OkHttp4 = 'okhttp_4';

    // Tor
    case Tor145 = 'tor_14_5';

    public function profile(): Profile
    {
        return Catalog::resolve($this);
    }

    public function impersonateTarget(): ?string
    {
        return match ($this) {
            self::Chrome99 => 'chrome99',
            self::Chrome100 => 'chrome100',
            self::Chrome101 => 'chrome101',
            self::Chrome104 => 'chrome104',
            self::Chrome107 => 'chrome107',
            self::Chrome110 => 'chrome110',
            self::Chrome116 => 'chrome116',
            self::Chrome119 => 'chrome119',
            self::Chrome120 => 'chrome120',
            self::Chrome123 => 'chrome123',
            self::Chrome124 => 'chrome124',
            self::Chrome131 => 'chrome131',
            self::Chrome133a => 'chrome133a',
            self::Chrome136 => 'chrome136',
            self::Chrome142 => 'chrome142',
            self::Chrome145 => 'chrome145',
            self::Chrome146 => 'chrome146',
            self::Chrome99Android => 'chrome99_android',
            self::Chrome131Android => 'chrome131_android',
            self::Firefox133 => 'firefox133',
            self::Firefox135 => 'firefox135',
            self::Firefox144 => 'firefox144',
            self::Firefox147 => 'firefox147',
            self::Safari153 => 'safari153',
            self::Safari155 => 'safari155',
            self::Safari170 => 'safari170',
            self::Safari172iOS => 'safari172_ios',
            self::Safari18 => 'safari180',
            self::SafariIPad18 => 'safari180_ios',
            self::SafariIOS18 => 'safari180_ios',
            self::Safari184 => 'safari184',
            self::Safari184iOS => 'safari184_ios',
            self::Safari260 => 'safari260',
            self::Safari260iOS => 'safari260_ios',
            self::Edge99 => 'edge99',
            self::Edge101 => 'edge101',
            self::Tor145 => 'tor145',
            default => null,
        };
    }

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
