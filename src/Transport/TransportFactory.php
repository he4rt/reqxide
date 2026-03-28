<?php

declare(strict_types=1);

namespace Reqxide\Transport;

use Reqxide\Contract\TransportInterface;
use Reqxide\Exception\TransportException;

final class TransportFactory
{
    public static function create(): TransportInterface
    {
        // Phase 7 will add: FFI check first
        // if (extension_loaded('ffi') && self::findImpersonateLibrary() !== null) {
        //     return new FfiTransport();
        // }

        if (extension_loaded('curl')) {
            return new CurlTransport;
        }

        $binaryPath = ProcessTransport::detectBinaryPath();
        if ($binaryPath !== null) {
            return new ProcessTransport($binaryPath);
        }

        throw new TransportException('No suitable transport available. Install ext-curl or curl-impersonate.');
    }
}
