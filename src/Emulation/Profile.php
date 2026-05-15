<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

use Reqxide\Contract\ProfileInterface;
use Reqxide\Http1\Http1Options;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Tls\TlsOptions;

readonly class Profile implements ProfileInterface
{
    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        public ?TlsOptions $tlsOptions = null,
        public ?Http2Options $http2Options = null,
        public ?Http1Options $http1Options = null,
        public array $defaultHeaders = [],
        public ?OriginalHeaderMap $originalHeaderMap = null,
        public ?ConnectionGroup $connectionGroup = null,
    ) {}

    public function tlsOptions(): ?TlsOptions
    {
        return $this->tlsOptions;
    }

    public function http2Options(): ?Http2Options
    {
        return $this->http2Options;
    }

    public function http1Options(): ?Http1Options
    {
        return $this->http1Options;
    }

    /** @return array<string, string> */
    public function defaultHeaders(): array
    {
        return $this->defaultHeaders;
    }

    public function originalHeaderMap(): ?OriginalHeaderMap
    {
        return $this->originalHeaderMap;
    }

    public function connectionGroup(): ?ConnectionGroup
    {
        return $this->connectionGroup;
    }
}
