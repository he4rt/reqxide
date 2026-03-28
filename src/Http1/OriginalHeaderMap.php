<?php

declare(strict_types=1);

namespace Reqxide\Http1;

readonly class OriginalHeaderMap
{
    /** @param  list<string>  $headerOrder */
    public function __construct(
        public array $headerOrder,
    ) {}
}
