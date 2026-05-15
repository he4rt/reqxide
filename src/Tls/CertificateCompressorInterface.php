<?php

declare(strict_types=1);

namespace Reqxide\Tls;

interface CertificateCompressorInterface
{
    public function algorithm(): CertificateCompressionAlgorithm;

    public function compress(string $data): string;

    public function decompress(string $data, int $uncompressedLength): string;
}
