<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Browser
    |--------------------------------------------------------------------------
    |
    | The browser profile to emulate by default. This should match one of the
    | Browser enum values (e.g., 'chrome_131', 'firefox_136', 'safari_18').
    |
    */
    'default_browser' => env('REQXIDE_BROWSER', 'chrome_131'),

    /*
    |--------------------------------------------------------------------------
    | curl-impersonate Library Path
    |--------------------------------------------------------------------------
    |
    | Path to the curl-impersonate shared library for FFI transport.
    | Leave null to use auto-detection.
    |
    */
    'library_path' => env('REQXIDE_CURL_IMPERSONATE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Whether to verify SSL certificates. Should be true in production.
    |
    */
    'verify_ssl' => env('REQXIDE_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Default request timeout in seconds.
    |
    */
    'timeout' => env('REQXIDE_TIMEOUT', 30),
];
