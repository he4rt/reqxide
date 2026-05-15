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
}
