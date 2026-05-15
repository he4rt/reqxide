<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\ChromeSecChUa;
use Reqxide\Emulation\Profile;

final class Edge
{
    public static function v131(): Profile
    {
        $chromeProfile = Chrome::v131();

        $headers = $chromeProfile->defaultHeaders;
        $headers['sec-ch-ua'] = ChromeSecChUa::generate(131, 'Microsoft Edge');
        $headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0';

        return new Profile(
            tlsOptions: $chromeProfile->tlsOptions,
            http2Options: $chromeProfile->http2Options,
            defaultHeaders: $headers,
            originalHeaderMap: $chromeProfile->originalHeaderMap,
        );
    }

    public static function v99(): Profile
    {
        $chromeProfile = Chrome::v99();

        $headers = $chromeProfile->defaultHeaders;
        $headers['sec-ch-ua'] = '" Not A;Brand";v="99", "Chromium";v="99", "Microsoft Edge";v="99"';
        $headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36 Edg/99.0.1150.30';
        $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';

        return new Profile(
            tlsOptions: $chromeProfile->tlsOptions,
            http2Options: $chromeProfile->http2Options,
            defaultHeaders: $headers,
            originalHeaderMap: $chromeProfile->originalHeaderMap,
        );
    }

    public static function v101(): Profile
    {
        $chromeProfile = Chrome::v101();

        $headers = $chromeProfile->defaultHeaders;
        $headers['sec-ch-ua'] = '" Not A;Brand";v="99", "Chromium";v="101", "Microsoft Edge";v="101"';
        $headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.64 Safari/537.36 Edg/101.0.1210.47';
        $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';

        return new Profile(
            tlsOptions: $chromeProfile->tlsOptions,
            http2Options: $chromeProfile->http2Options,
            defaultHeaders: $headers,
            originalHeaderMap: $chromeProfile->originalHeaderMap,
        );
    }
}
