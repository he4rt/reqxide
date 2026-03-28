<?php

declare(strict_types=1);

namespace Reqxide\Http2;

readonly class PseudoHeaderOrder
{
    /** @param  list<PseudoHeader>  $headers */
    public function __construct(
        public array $headers,
    ) {}
}
