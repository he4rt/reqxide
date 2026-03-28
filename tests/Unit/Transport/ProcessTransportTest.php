<?php

declare(strict_types=1);

use Nyholm\Psr7\Request;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Profile;
use Reqxide\Exception\NetworkException;
use Reqxide\Exception\TransportException;
use Reqxide\Proxy\Proxy;
use Reqxide\Transport\ProcessTransport;
use Reqxide\Transport\TransportOptions;

it('implements TransportInterface', function (): void {
    $transport = new ProcessTransport;

    expect($transport)->toBeInstanceOf(TransportInterface::class);
});

it('supports fingerprinting', function (): void {
    $transport = new ProcessTransport;

    expect($transport->supportsFingerprinting())->toBeTrue();
});

it('supports HTTP/2 configuration', function (): void {
    $transport = new ProcessTransport;

    expect($transport->supportsHttp2Configuration())->toBeTrue();
});

it('accepts optional binary path in constructor', function (): void {
    $transport = new ProcessTransport('/usr/local/bin/curl-impersonate');

    expect($transport)->toBeInstanceOf(ProcessTransport::class);
});

it('accepts null binary path in constructor', function (): void {
    $transport = new ProcessTransport;

    expect($transport)->toBeInstanceOf(ProcessTransport::class);
});

it('detects binary path returning string or null', function (): void {
    $result = ProcessTransport::detectBinaryPath();

    expect($result)->toBeIn([null, ...array_filter([$result], is_string(...))]);
});

it('throws TransportException when binary not found and no path detected', function (): void {
    $transport = new ProcessTransport('/nonexistent/path/curl-impersonate-does-not-exist');

    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);
})->throws(NetworkException::class);

it('throws NetworkException when process exits with non-zero code', function (): void {
    $transport = new ProcessTransport('/usr/bin/false');

    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);
})->throws(NetworkException::class);

it('throws TransportException when binary path is null and cannot be detected', function (): void {
    // Override env and use a transport with null path, but patch detection
    // We test this by using a reflection or by ensuring no binary exists
    // Since detectBinaryPath may return null on CI, we test the exception path
    $transport = new ProcessTransport;

    // If binary is not installed, this will throw TransportException
    // If binary IS installed, the test won't throw - so we conditionally skip
    if (ProcessTransport::detectBinaryPath() !== null) {
        $this->markTestSkipped('curl-impersonate binary is installed on this system.');
    }

    $request = new Request('GET', 'https://example.com');
    $profile = new Profile;
    $options = new TransportOptions;

    $transport->send($request, $profile, $options);
})->throws(TransportException::class, 'curl_impersonate binary not found');

it('sends request successfully with echo binary', function (): void {
    // Create a tiny shell script that mimics a curl response
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nHello World'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200)
            ->and((string) $response->getBody())->toBe('Hello World')
            ->and($response->getHeaderLine('Content-Type'))->toBe('text/plain');
    } finally {
        unlink($scriptPath);
    }
});

it('parses response with LF line endings', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_lf_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 404 Not Found\nContent-Type: application/json\n\n{\"error\":\"not found\"}'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com/missing');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(404)
            ->and((string) $response->getBody())->toBe('{"error":"not found"}')
            ->and($response->getHeaderLine('Content-Type'))->toBe('application/json');
    } finally {
        unlink($scriptPath);
    }
});

it('parses response without header separator as 200 with raw body', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_noheader_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'just plain text without headers'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200)
            ->and((string) $response->getBody())->toBe('just plain text without headers');
    } finally {
        unlink($scriptPath);
    }
});

it('builds arguments with request body', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_body_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 201 Created\r\n\r\nok'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('POST', 'https://api.example.com/data', ['Content-Type' => 'application/json'], '{"key":"value"}');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(201)
            ->and((string) $response->getBody())->toBe('ok');
    } finally {
        unlink($scriptPath);
    }
});

it('builds arguments with SSL disabled', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_nossl_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 200 OK\r\n\r\nok'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile;
        $options = new TransportOptions(verifySsl: false);

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200);
    } finally {
        unlink($scriptPath);
    }
});

it('builds arguments with CA bundle', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_ca_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 200 OK\r\n\r\nok'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile;
        $options = new TransportOptions(caBundle: '/etc/ssl/certs/ca-certificates.crt');

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200);
    } finally {
        unlink($scriptPath);
    }
});

it('builds arguments with proxy', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_proxy_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 200 OK\r\n\r\nok'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile;
        $proxy = Proxy::http('127.0.0.1:8080');
        $options = new TransportOptions(proxy: $proxy);

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200);
    } finally {
        unlink($scriptPath);
    }
});

it('builds arguments with profile default headers', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_headers_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/1.1 200 OK\r\n\r\nok'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com');
        $profile = new Profile(defaultHeaders: ['User-Agent' => 'Chrome/131', 'Accept' => '*/*']);
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200);
    } finally {
        unlink($scriptPath);
    }
});

it('parses HTTP/2 status line', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_h2_'.getmypid().'.sh';
    file_put_contents($scriptPath, "#!/bin/sh\nprintf 'HTTP/2 301\r\nLocation: https://example.com/new\r\n\r\nredirected'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://example.com/old');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(301)
            ->and($response->getHeaderLine('Location'))->toBe('https://example.com/new')
            ->and((string) $response->getBody())->toBe('redirected');
    } finally {
        unlink($scriptPath);
    }
});

it('parses response with proxy CONNECT tunnel headers', function (): void {
    // Simulates curl output through a proxy: CONNECT headers + actual HTTP/2 response
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_proxy_connect_'.getmypid().'.sh';
    $output = implode("\r\n", [
        'HTTP/1.1 200 Connection established',
        '',
        'HTTP/2 200',
        'content-type: application/json',
        'x-custom: value',
        '',
        '{"data":"hello"}',
    ]);
    file_put_contents($scriptPath, "#!/bin/sh\nprintf '".addcslashes($output, "'")."'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://api.example.com/data');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('content-type'))->toBe('application/json')
            ->and($response->getHeaderLine('x-custom'))->toBe('value')
            ->and((string) $response->getBody())->toBe('{"data":"hello"}');
    } finally {
        unlink($scriptPath);
    }
});

it('parses response with proxy CONNECT tunnel and non-200 status', function (): void {
    $scriptPath = sys_get_temp_dir().'/reqxide_test_curl_proxy_403_'.getmypid().'.sh';
    $output = implode("\r\n", [
        'HTTP/1.1 200 Connection established',
        '',
        'HTTP/2 403',
        'content-type: application/json',
        '',
        '{"error":"forbidden"}',
    ]);
    file_put_contents($scriptPath, "#!/bin/sh\nprintf '".addcslashes($output, "'")."'\n");
    chmod($scriptPath, 0755);

    try {
        $transport = new ProcessTransport($scriptPath);
        $request = new Request('GET', 'https://api.example.com/blocked');
        $profile = new Profile;
        $options = new TransportOptions;

        $response = $transport->send($request, $profile, $options);

        expect($response->getStatusCode())->toBe(403)
            ->and((string) $response->getBody())->toBe('{"error":"forbidden"}');
    } finally {
        unlink($scriptPath);
    }
});
