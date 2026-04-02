<?php

declare(strict_types=1);

namespace Reqxide\Middleware;

use Nyholm\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class CompressionMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        if (! $request->hasHeader('Accept-Encoding')) {
            $encodings = $this->supportedEncodings();

            if ($encodings !== '') {
                $request = $request->withHeader('Accept-Encoding', $encodings);
            }
        }

        $response = $next($request);

        $encoding = ContentEncoding::tryFrom(strtolower($response->getHeaderLine('Content-Encoding')));

        if ($encoding instanceof ContentEncoding) {
            $body = (string) $response->getBody();
            $decompressed = $this->decompress($body, $encoding);

            if ($decompressed !== null) {
                $response = $response
                    ->withBody(Stream::create($decompressed))
                    ->withoutHeader('Content-Encoding')
                    ->withoutHeader('Content-Length');
            }
        }

        return $response;
    }

    private function supportedEncodings(): string
    {
        $encodings = [ContentEncoding::Gzip->value, ContentEncoding::Deflate->value];

        if (function_exists('brotli_uncompress')) {
            $encodings[] = ContentEncoding::Brotli->value;
        }

        if (function_exists('zstd_uncompress')) {
            $encodings[] = ContentEncoding::Zstd->value;
        }

        return implode(', ', $encodings);
    }

    private function decompress(string $data, ContentEncoding $encoding): ?string
    {
        if ($data === '') {
            return '';
        }

        if (! $encoding->looksCompressed($data)) {
            return $data;
        }

        return match ($encoding) {
            ContentEncoding::Gzip => ($result = @gzdecode($data)) === false ? null : $result,
            ContentEncoding::Deflate => ($result = @gzinflate($data)) === false ? null : $result,
            ContentEncoding::Brotli => function_exists('brotli_uncompress')
                ? (($result = @brotli_uncompress($data)) === false ? null : $result)
                : null,
            ContentEncoding::Zstd => function_exists('zstd_uncompress')
                ? (($result = @zstd_uncompress($data)) === false ? null : $result)
                : null,
        };
    }
}
