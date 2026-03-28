<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use Reqxide\Contract\TransportInterface;
use Reqxide\Exception\FfiException;
use Reqxide\Exception\TransportException;

final class TransportFactory
{
    public static function create(): TransportInterface
    {
        if (extension_loaded('ffi')) {
            try {
                return new FfiTransport;
            } catch (FfiException) {
                // Library not found — fall through to other transports
            }
        }

        $binaryPath = ProcessTransport::detectBinaryPath();
        if ($binaryPath !== null) {
            return new ProcessTransport($binaryPath);
        }

        if (extension_loaded('curl')) {
            return new CurlTransport;
        }

        throw new TransportException('No suitable transport available. Install ext-curl or curl-impersonate.');
    }
}
