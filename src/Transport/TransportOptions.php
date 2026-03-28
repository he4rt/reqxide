<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use Reqxide\Proxy\Proxy;

readonly class TransportOptions
{
    public function __construct(
        public int $timeoutMs = 30_000,
        public int $connectTimeoutMs = 10_000,
        public bool $verifySsl = true,
        public ?string $caBundle = null,
        public ?Proxy $proxy = null,
    ) {}
}
