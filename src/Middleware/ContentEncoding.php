<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

enum ContentEncoding: string
{
    case Gzip = 'gzip';
    case Deflate = 'deflate';
    case Brotli = 'br';
    case Zstd = 'zstd';

    /**
     * Check whether the data appears to actually be compressed in this encoding
     * by verifying magic bytes. Returns true for encodings without reliable signatures.
     */
    public function looksCompressed(string $data): bool
    {
        return match ($this) {
            self::Gzip => strlen($data) >= 2 && $data[0] === "\x1f" && $data[1] === "\x8b",
            self::Zstd => strlen($data) >= 4 && str_starts_with($data, "\x28\xb5\x2f\xfd"),
            self::Deflate, self::Brotli => true,
        };
    }
}
