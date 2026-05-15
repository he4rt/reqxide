<?php

declare(strict_types=1);

namespace Reqxide\Contract;

use Reqxide\Emulation\ConnectionGroup;
use Reqxide\Http1\Http1Options;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Tls\TlsOptions;

interface ProfileInterface
{
    public function tlsOptions(): ?TlsOptions;

    public function http2Options(): ?Http2Options;

    public function http1Options(): ?Http1Options;

    /** @return array<string, string> */
    public function defaultHeaders(): array;

    public function originalHeaderMap(): ?OriginalHeaderMap;

    public function connectionGroup(): ?ConnectionGroup;
}
