<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use Reqxide\Contract\TransportInterface;
use Reqxide\Exception\TransportException;

final class TransportFactory
{
    public static function create(): TransportInterface
    {
        // CurlTransport is the most reliable default — ext/curl handles
        // TLS cipher/curves, decompression, and status codes correctly.
        if (extension_loaded('curl')) {
            return new CurlTransport;
        }

        // @codeCoverageIgnoreStart
        $binaryPath = ProcessTransport::detectBinaryPath();
        if ($binaryPath !== null) {
            return new ProcessTransport($binaryPath);
        }

        throw new TransportException('No suitable transport available. Install ext-curl or curl-impersonate.');
        // @codeCoverageIgnoreEnd
    }
}
