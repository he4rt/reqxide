<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\NetworkException;
use Reqxide\Exception\TransportException;
use Reqxide\Proxy\Proxy;

final readonly class ProcessTransport implements TransportInterface
{
    public function __construct(
        private ?string $binaryPath = null,
    ) {}

    public function send(RequestInterface $request, Profile $profile, TransportOptions $options): ResponseInterface
    {
        $binary = $this->binaryPath ?? self::detectBinaryPath();

        if ($binary === null) {
            throw new TransportException('curl_impersonate binary not found. Install curl-impersonate or set the binary path.');
        }

        $args = $this->buildArguments($request, $profile, $options);
        $command = escapeshellcmd($binary).' '.implode(' ', array_map(escapeshellarg(...), $args));

        // Include response headers with -D - and suppress progress meter
        $command .= ' -s -D -';

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process)) {
            throw new TransportException('Failed to start curl_impersonate process.');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $stdout === false) {
            throw new NetworkException(
                $request,
                'curl_impersonate failed (exit '.$exitCode.'): '.($stderr !== false && $stderr !== '' ? $stderr : 'unknown error'),
                $exitCode,
            );
        }

        return $this->parseResponse($stdout);
    }

    public function supportsFingerprinting(): bool
    {
        return true;
    }

    public function supportsHttp2Configuration(): bool
    {
        return true;
    }

    public static function detectBinaryPath(): ?string
    {
        $envPath = getenv('REQXIDE_CURL_IMPERSONATE_PATH');
        if ($envPath !== false && is_executable($envPath)) {
            return $envPath;
        }

        $paths = [
            '/usr/local/bin/curl-impersonate',
            '/usr/bin/curl-impersonate',
            '/usr/local/bin/curl-impersonate-chrome',
            '/usr/bin/curl-impersonate-chrome',
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = trim((string) shell_exec('which curl-impersonate 2>/dev/null'));
        if ($which !== '' && is_executable($which)) {
            return $which;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function buildArguments(RequestInterface $request, Profile $profile, TransportOptions $options): array
    {
        $args = [];

        $args[] = (string) $request->getUri();

        $args[] = '-X';
        $args[] = $request->getMethod();

        $headers = $profile->defaultHeaders;
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        foreach ($headers as $name => $value) {
            $args[] = '-H';
            $args[] = $name.': '.$value;
        }

        $body = (string) $request->getBody();
        if ($body !== '') {
            $args[] = '-d';
            $args[] = $body;
        }

        $args[] = '--max-time';
        $args[] = (string) (int) ceil($options->timeoutMs / 1000);
        $args[] = '--connect-timeout';
        $args[] = (string) (int) ceil($options->connectTimeoutMs / 1000);

        if (! $options->verifySsl) {
            $args[] = '-k';
        }

        if ($options->caBundle !== null) {
            $args[] = '--cacert';
            $args[] = $options->caBundle;
        }

        if ($options->proxy instanceof Proxy) {
            $args[] = '--proxy';
            $args[] = $options->proxy->toUrl();
        }

        // Let curl handle decompression of gzip/br/zstd responses
        $args[] = '--compressed';

        return $args;
    }

    /**
     * Parse curl output into a PSR-7 response.
     *
     * Handles multiple header blocks (e.g. proxy CONNECT response followed
     * by the actual HTTP/2 response) by finding the last HTTP status line.
     */
    private function parseResponse(string $raw): ResponseInterface
    {
        // Find the last header/body separator — with proxies, curl outputs
        // multiple header blocks (CONNECT tunnel + actual response).
        $separator = "\r\n\r\n";
        $lastHeaderEnd = strrpos($raw, $separator);

        if ($lastHeaderEnd === false) {
            $separator = "\n\n";
            $lastHeaderEnd = strrpos($raw, $separator);

            if ($lastHeaderEnd === false) {
                return new Response(200, [], $raw);
            }
        }

        $headerSection = substr($raw, 0, $lastHeaderEnd);
        $body = substr($raw, $lastHeaderEnd + strlen($separator));

        // Find the last HTTP status line to skip proxy CONNECT headers
        $lastHttpPos = strrpos($headerSection, 'HTTP/');
        if ($lastHttpPos !== false && $lastHttpPos > 0) {
            $headerSection = substr($headerSection, $lastHttpPos);
        }

        $lines = preg_split('/\r?\n/', $headerSection);

        if ($lines === false) {
            return new Response(200, [], $raw);
        }

        $statusCode = 200;
        /** @var array<string, list<string>> $headers */
        $headers = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'HTTP/')) {
                preg_match('/HTTP\/[\d.]+\s+(\d+)/', $line, $matches);
                if (isset($matches[1])) {
                    $statusCode = (int) $matches[1];
                }
            } elseif (str_contains($line, ':')) {
                $parts = explode(':', $line, 2);
                $headers[trim($parts[0])][] = trim($parts[1]);
            }
        }

        return new Response($statusCode, $headers, $body);
    }
}
