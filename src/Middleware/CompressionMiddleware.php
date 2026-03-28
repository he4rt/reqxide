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

        if ($response->hasHeader('Content-Encoding')) {
            $encoding = strtolower($response->getHeaderLine('Content-Encoding'));
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
        $encodings = ['gzip', 'deflate'];

        if (function_exists('brotli_uncompress')) {
            $encodings[] = 'br';
        }

        if (function_exists('zstd_uncompress')) {
            $encodings[] = 'zstd';
        }

        return implode(', ', $encodings);
    }

    private function decompress(string $data, string $encoding): ?string
    {
        return match ($encoding) {
            'gzip' => @gzdecode($data) ?: null,
            'deflate' => @gzinflate($data) ?: null,
            'br' => function_exists('brotli_uncompress') ? (@brotli_uncompress($data) ?: null) : null,
            'zstd' => function_exists('zstd_uncompress') ? (@zstd_uncompress($data) ?: null) : null,
            default => null,
        };
    }
}
