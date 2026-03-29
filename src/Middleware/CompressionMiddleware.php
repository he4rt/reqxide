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
        if ($data === '') {
            return '';
        }

        return match ($encoding) {
            'gzip' => ($result = gzdecode($data)) === false ? null : $result,
            'deflate' => ($result = gzinflate($data)) === false ? null : $result,
            'br' => function_exists('brotli_uncompress')
                ? (($result = brotli_uncompress($data)) === false ? null : $result)
                : null,
            'zstd' => function_exists('zstd_uncompress')
                ? (($result = zstd_uncompress($data)) === false ? null : $result)
                : null,
            default => null,
        };
    }
}
