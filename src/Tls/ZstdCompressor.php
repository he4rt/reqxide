<?php

declare(strict_types=1);

namespace Reqxide\Tls;

final readonly class ZstdCompressor implements CertificateCompressorInterface
{
    public function algorithm(): CertificateCompressionAlgorithm
    {
        return CertificateCompressionAlgorithm::Zstd;
    }

    /**
     * @codeCoverageIgnore
     */
    public function compress(string $data): string
    {
        if (! function_exists('zstd_compress')) {
            throw new \RuntimeException('zstd extension is required for ZstdCompressor');
        }

        $result = zstd_compress($data);

        if ($result === false) {
            throw new \RuntimeException('zstd compression failed');
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     */
    public function decompress(string $data, int $uncompressedLength): string
    {
        if (! function_exists('zstd_uncompress')) {
            throw new \RuntimeException('zstd extension is required for ZstdCompressor');
        }

        $result = zstd_uncompress($data);

        if ($result === false) {
            throw new \RuntimeException('zstd decompression failed');
        }

        return $result;
    }
}
