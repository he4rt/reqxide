<?php

declare(strict_types=1);

namespace Reqxide\Transport\Ffi;

use FFI;
use FFI\CData;
use Reqxide\Exception\FfiException;

class FfiWrapper
{
    private const int RTLD_NOW = 2;

    private const int RTLD_DEEPBIND = 8;

    private FFI $ffi;

    /**
     * @var CData|null dlopen handle — kept alive to prevent unloading
     */
    private ?CData $dlHandle = null;

    public function __construct(string $headerPath, string $libraryPath)
    {
        if (! extension_loaded('ffi')) {
            throw new FfiException('PHP FFI extension is not loaded.');
        }

        if (! is_readable($headerPath)) {
            throw new FfiException('Could not read FFI header file: '.$headerPath);
        }

        $header = file_get_contents($headerPath);

        if ($header === false) {
            throw new FfiException('Could not read FFI header file: '.$headerPath);
        }

        // When ext/curl is loaded, PHP already has standard libcurl symbols in memory.
        // Loading libcurl-impersonate via FFI::cdef() causes symbol collision (both
        // export curl_easy_init, curl_easy_perform, etc.), leading to segfaults.
        //
        // Fix: use dlopen() with RTLD_DEEPBIND to make libcurl-impersonate prefer
        // its own symbols over the already-loaded standard libcurl.
        if (extension_loaded('curl') && PHP_OS_FAMILY === 'Linux') {
            $this->loadWithDeepbind($header, $libraryPath);
        } else {
            $this->ffi = FFI::cdef($header, $libraryPath);
        }
    }

    public function easyInit(): CData
    {
        /** @var CData|null $handle */
        $handle = $this->ffi->curl_easy_init();

        if ($handle === null) {
            throw new FfiException('curl_easy_init() returned null.');
        }

        return $handle;
    }

    public function easySetopt(CData $handle, int $option, mixed $value): int
    {
        /** @var int */
        return $this->ffi->curl_easy_setopt($handle, $option, $value);
    }

    public function easyPerform(CData $handle): int
    {
        /** @var int */
        return $this->ffi->curl_easy_perform($handle);
    }

    public function easyGetinfo(CData $handle, int $info): int
    {
        /** @var int */
        return $this->ffi->curl_easy_getinfo($handle, $info);
    }

    public function easyStrerror(int $code): string
    {
        /** @var string */
        return $this->ffi->curl_easy_strerror($code);
    }

    public function easyCleanup(CData $handle): void
    {
        $this->ffi->curl_easy_cleanup($handle);
    }

    public function slistAppend(?CData $list, string $value): CData
    {
        /** @var CData */
        return $this->ffi->curl_slist_append($list, $value);
    }

    public function slistFreeAll(?CData $list): void
    {
        if ($list !== null) {
            $this->ffi->curl_slist_free_all($list);
        }
    }

    /**
     * Load libcurl-impersonate with RTLD_DEEPBIND to avoid symbol collision
     * with PHP's ext/curl (which loads standard libcurl).
     */
    private function loadWithDeepbind(string $header, string $libraryPath): void
    {
        $dl = FFI::cdef('
            void *dlopen(const char *filename, int flags);
            char *dlerror(void);
        ');

        /** @var CData|null $handle */
        $handle = $dl->dlopen($libraryPath, self::RTLD_NOW | self::RTLD_DEEPBIND);

        if ($handle === null) {
            /** @var CData $errorPtr */
            $errorPtr = $dl->dlerror();
            $error = FFI::string($errorPtr);

            throw new FfiException('Failed to dlopen libcurl-impersonate: '.$error);
        }

        // Keep the handle alive so the library stays loaded
        $this->dlHandle = $handle;

        // Create FFI interface without a library path — symbols are already loaded
        // via dlopen with RTLD_DEEPBIND, so FFI resolves to the impersonate version
        $this->ffi = FFI::cdef($header);
    }
}
