<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum CertificateCompressor: string
{
    case Brotli = 'brotli';
    case Zlib = 'zlib';
    case Zstd = 'zstd';
}
