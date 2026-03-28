<?php

declare(strict_types=1);

namespace Reqxide\Emulation\Catalog;

use Reqxide\Emulation\Profile;

final class OkHttp
{
    public static function v5(): Profile
    {
        return new Profile;
    }

    public static function v4(): Profile
    {
        return new Profile;
    }
}
