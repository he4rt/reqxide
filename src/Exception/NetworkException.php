<?php

declare(strict_types=1);

namespace Reqxide\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

class NetworkException extends ReqxideException implements NetworkExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create from a curl_impersonate process failure.
     */
    public static function fromCurlExitCode(RequestInterface $request, int $exitCode, string $stderr = ''): self
    {
        $detail = $stderr !== '' ? $stderr : self::describeExitCode($exitCode);

        return new self(
            $request,
            'curl_impersonate failed (exit '.$exitCode.'): '.$detail,
            $exitCode,
        );
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Map libcurl exit codes to human-readable descriptions.
     *
     * @see https://curl.se/libcurl/c/libcurl-errors.html
     */
    private static function describeExitCode(int $code): string
    {
        return match ($code) {
            1 => 'unsupported protocol',
            2 => 'failed to initialize',
            3 => 'malformed URL',
            5 => 'could not resolve proxy',
            6 => 'could not resolve host',
            7 => 'connection refused',
            9 => 'access denied (login/credentials)',
            18 => 'partial transfer (connection closed prematurely)',
            22 => 'HTTP error (server returned >= 400)',
            23 => 'write error (disk full or permissions)',
            26 => 'read error (could not read local file)',
            27 => 'out of memory',
            28 => 'operation timed out',
            33 => 'range error (server does not support byte ranges)',
            35 => 'SSL/TLS handshake failed',
            47 => 'too many redirects',
            51 => 'SSL certificate verification failed (peer)',
            52 => 'empty reply from server',
            55 => 'send error (network failure)',
            56 => 'receive error (connection reset)',
            58 => 'SSL client certificate error',
            60 => 'SSL CA certificate not found or not trusted',
            67 => 'login denied (authentication failure)',
            77 => 'SSL CA certificate path error',
            92 => 'HTTP/2 stream error',
            95 => 'HTTP/2 error',
            97 => 'HTTP/3 error',
            default => 'unknown error (code '.$code.')',
        };
    }
}
