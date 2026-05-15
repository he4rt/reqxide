<?php

declare(strict_types=1);

namespace Reqxide\Tls;

final readonly class ZlibCompressor implements CertificateCompressorInterface
{
    public function algorithm(): CertificateCompressionAlgorithm
    {
        return CertificateCompressionAlgorithm::Zlib;
    }

    public function compress(string $data): string
    {
        $result = gzcompress($data);

        if ($result === false) {
            throw new \RuntimeException('zlib compression failed'); // @codeCoverageIgnore
        }

        return $result;
    }

    public function decompress(string $data, int $uncompressedLength): string
    {
        $result = gzuncompress($data, $uncompressedLength);

        if ($result === false) {
            throw new \RuntimeException('zlib decompression failed'); // @codeCoverageIgnore
        }

        return $result;
    }
}
