<?php

declare(strict_types=1);

namespace Reqxide\Transport\Ffi;

use FFI;
use FFI\CData;
use Reqxide\Exception\FfiException;

class FfiWrapper
{
    private FFI $ffi;

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

        $this->ffi = FFI::cdef($header, $libraryPath);
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
}
