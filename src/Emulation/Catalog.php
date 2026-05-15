<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

use Reqxide\Emulation\Catalog\Chrome;
use Reqxide\Emulation\Catalog\Edge;
use Reqxide\Emulation\Catalog\Firefox;
use Reqxide\Emulation\Catalog\OkHttp;
use Reqxide\Emulation\Catalog\Safari;
use Reqxide\Emulation\Catalog\Tor;

final class Catalog
{
    public static function resolve(Browser $browser): Profile
    {
        return match ($browser) {
            // Chrome Desktop
            Browser::Chrome99 => Chrome::v99(),
            Browser::Chrome100 => Chrome::v100(),
            Browser::Chrome101 => Chrome::v101(),
            Browser::Chrome104 => Chrome::v104(),
            Browser::Chrome107 => Chrome::v107(),
            Browser::Chrome110 => Chrome::v110(),
            Browser::Chrome116 => Chrome::v116(),
            Browser::Chrome119 => Chrome::v119(),
            Browser::Chrome120 => Chrome::v120(),
            Browser::Chrome123 => Chrome::v123(),
            Browser::Chrome124 => Chrome::v124(),
            Browser::Chrome128 => Chrome::v128(),
            Browser::Chrome129 => Chrome::v129(),
            Browser::Chrome130 => Chrome::v130(),
            Browser::Chrome131 => Chrome::v131(),
            Browser::Chrome133a => Chrome::v133a(),
            Browser::Chrome136 => Chrome::v136(),
            Browser::Chrome142 => Chrome::v142(),
            Browser::Chrome145 => Chrome::v145(),
            Browser::Chrome146 => Chrome::v146(),
            Browser::Chrome147 => Chrome::v147(),

            // Chrome Android
            Browser::Chrome99Android => Chrome::v99Android(),
            Browser::Chrome131Android => Chrome::v131Android(),

            // Firefox
            Browser::Firefox133 => Firefox::v133(),
            Browser::Firefox135 => Firefox::v135(),
            Browser::Firefox136 => Firefox::v136(),
            Browser::Firefox144 => Firefox::v144(),
            Browser::Firefox147 => Firefox::v147(),
            Browser::Firefox148 => Firefox::v148(),
            Browser::Firefox149 => Firefox::v149(),

            // Safari
            Browser::Safari153 => Safari::v153(),
            Browser::Safari155 => Safari::v155(),
            Browser::Safari170 => Safari::v170(),
            Browser::Safari172iOS => Safari::v172iOS(),
            Browser::Safari18 => Safari::v18(),
            Browser::SafariIPad18 => Safari::iPad18(),
            Browser::SafariIOS18 => Safari::iOS18(),
            Browser::Safari184 => Safari::v184(),
            Browser::Safari184iOS => Safari::v184iOS(),
            Browser::Safari260 => Safari::v260(),
            Browser::Safari260iOS => Safari::v260iOS(),

            // Edge
            Browser::Edge99 => Edge::v99(),
            Browser::Edge101 => Edge::v101(),
            Browser::Edge131 => Edge::v131(),

            // OkHttp
            Browser::OkHttp5 => OkHttp::v5(),
            Browser::OkHttp4 => OkHttp::v4(),

            // Tor
            Browser::Tor145 => Tor::v145(),
        };
    }
}
