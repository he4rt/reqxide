<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

enum Browser: string
{
    // Chrome
    case Chrome131 = 'chrome_131';
    case Chrome130 = 'chrome_130';
    case Chrome129 = 'chrome_129';
    case Chrome128 = 'chrome_128';

    // Firefox
    case Firefox136 = 'firefox_136';
    case Firefox135 = 'firefox_135';

    // Safari
    case Safari18 = 'safari_18';
    case SafariIPad18 = 'safari_ipad_18';
    case SafariIOS18 = 'safari_ios_18';

    // Edge
    case Edge131 = 'edge_131';

    // OkHttp
    case OkHttp5 = 'okhttp_5';
    case OkHttp4 = 'okhttp_4';

    public function profile(): Profile
    {
        return Catalog::resolve($this);
    }
}
