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

        // ProcessTransport shells out to curl-impersonate binary.
        // Headers and User-Agent are applied but TLS profile options
        // require the --impersonate flag (not yet mapped from Profile).
        $binaryPath = ProcessTransport::detectBinaryPath();
        if ($binaryPath !== null) {
            return new ProcessTransport($binaryPath);
        }

        // FfiTransport is disabled by default — see @todo in FfiTransport.php.
        // Users can opt-in via ClientBuilder::transport(new FfiTransport()).

        throw new TransportException('No suitable transport available. Install ext-curl or curl-impersonate.');
    }
}
