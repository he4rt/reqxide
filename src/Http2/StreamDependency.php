<?php

declare(strict_types=1);

namespace Reqxide\Http2;

readonly class StreamDependency
{
    public function __construct(
        public int $streamId,
        public int $weight,
        public bool $exclusive = false,
    ) {}
}
