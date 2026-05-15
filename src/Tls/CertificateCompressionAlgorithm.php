<?php

declare(strict_types=1);

namespace Reqxide\Tls;

enum CertificateCompressionAlgorithm: int
{
    case Zlib = 0x0001;
    case Brotli = 0x0002;
    case Zstd = 0x0003;
}
