<?php

declare(strict_types=1);

namespace Reqxide\Http1;

readonly class Http1Options
{
    public function __construct(
        public ?OriginalHeaderMap $originalHeaderMap = null,
    ) {}

    public static function builder(): Http1OptionsBuilder
    {
        return new Http1OptionsBuilder;
    }
}
