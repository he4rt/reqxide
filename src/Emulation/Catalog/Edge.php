<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\Profile;

final class Edge
{
    public static function v131(): Profile
    {
        $chromeProfile = Chrome::v131();

        $headers = $chromeProfile->defaultHeaders;
        $headers['sec-ch-ua'] = '"Microsoft Edge";v="131", "Chromium";v="131", "Not_A Brand";v="24"';
        $headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0';

        return new Profile(
            tlsOptions: $chromeProfile->tlsOptions,
            http2Options: $chromeProfile->http2Options,
            defaultHeaders: $headers,
            originalHeaderMap: $chromeProfile->originalHeaderMap,
        );
    }
}
