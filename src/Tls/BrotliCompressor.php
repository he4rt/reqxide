<?php

declare(strict_types=1);

namespace Reqxide\Tls;

final readonly class BrotliCompressor implements CertificateCompressorInterface
{
    public function algorithm(): CertificateCompressionAlgorithm
    {
        return CertificateCompressionAlgorithm::Brotli;
    }

    /**
     * @codeCoverageIgnore
     */
    public function compress(string $data): string
    {
        if (! function_exists('brotli_compress')) {
            throw new \RuntimeException('brotli extension is required for BrotliCompressor');
        }

        $result = brotli_compress($data);

        if ($result === false) {
            throw new \RuntimeException('brotli compression failed');
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     */
    public function decompress(string $data, int $uncompressedLength): string
    {
        if (! function_exists('brotli_uncompress')) {
            throw new \RuntimeException('brotli extension is required for BrotliCompressor');
        }

        $result = brotli_uncompress($data);

        if ($result === false) {
            throw new \RuntimeException('brotli decompression failed');
        }

        return $result;
    }
}
