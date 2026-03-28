<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

use Reqxide\Emulation\Catalog\Chrome;
use Reqxide\Emulation\Catalog\Edge;
use Reqxide\Emulation\Catalog\Firefox;
use Reqxide\Emulation\Catalog\OkHttp;
use Reqxide\Emulation\Catalog\Safari;

final class Catalog
{
    public static function resolve(Browser $browser): Profile
    {
        return match ($browser) {
            Browser::Chrome131 => Chrome::v131(),
            Browser::Chrome130 => Chrome::v130(),
            Browser::Chrome129 => Chrome::v129(),
            Browser::Chrome128 => Chrome::v128(),
            Browser::Firefox136 => Firefox::v136(),
            Browser::Firefox135 => Firefox::v135(),
            Browser::Safari18 => Safari::v18(),
            Browser::SafariIPad18 => Safari::iPad18(),
            Browser::SafariIOS18 => Safari::iOS18(),
            Browser::Edge131 => Edge::v131(),
            Browser::OkHttp5 => OkHttp::v5(),
            Browser::OkHttp4 => OkHttp::v4(),
        };
    }
}
