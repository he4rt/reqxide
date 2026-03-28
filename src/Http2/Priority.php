<?php

declare(strict_types=1);

namespace Reqxide\Http2;

readonly class Priority
{
    public function __construct(
        public int $streamId,
        public int $dependsOn,
        public int $weight,
        public bool $exclusive = false,
    ) {}
}
