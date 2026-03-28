<?php

declare(strict_types=1);

namespace Reqxide\Http2;

readonly class SettingsOrder
{
    /** @param  list<SettingId>  $settings */
    public function __construct(
        public array $settings,
    ) {}
}
