<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

use Reqxide\Http1\Http1Options;
use Reqxide\Http1\OriginalHeaderMap;
use Reqxide\Http2\Http2Options;
use Reqxide\Tls\TlsOptions;

final class ProfileBuilder
{
    private ?TlsOptions $tlsOptions = null;

    private ?Http2Options $http2Options = null;

    private ?Http1Options $http1Options = null;

    /** @var array<string, string> */
    private array $defaultHeaders = [];

    private ?OriginalHeaderMap $originalHeaderMap = null;

    public function tlsOptions(TlsOptions $options): self
    {
        $this->tlsOptions = $options;

        return $this;
    }

    public function http2Options(Http2Options $options): self
    {
        $this->http2Options = $options;

        return $this;
    }

    public function http1Options(Http1Options $options): self
    {
        $this->http1Options = $options;

        return $this;
    }

    /** @param  array<string, string>  $headers */
    public function defaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;

        return $this;
    }

    public function originalHeaderMap(OriginalHeaderMap $map): self
    {
        $this->originalHeaderMap = $map;

        return $this;
    }

    public function build(): Profile
    {
        return new Profile(
            tlsOptions: $this->tlsOptions,
            http2Options: $this->http2Options,
            http1Options: $this->http1Options,
            defaultHeaders: $this->defaultHeaders,
            originalHeaderMap: $this->originalHeaderMap,
        );
    }
}
