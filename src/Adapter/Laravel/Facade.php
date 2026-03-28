<?php

declare(strict_types=1);

namespace Reqxide\Adapter\Laravel;

use Reqxide\Client;
use Reqxide\Emulation\Browser;

/**
 * Simple facade-like wrapper for the reqxide Client.
 *
 * Lazily builds a Client on first access, caching it for subsequent calls.
 *
 * Usage:
 *   $facade = new Facade(Browser::Chrome131);
 *   $response = $facade->client()->get('https://example.com')->send();
 */
final class Facade
{
    private ?Client $client = null;

    public function __construct(
        private readonly Browser $browser,
    ) {}

    public function client(): Client
    {
        return $this->client ??= Client::builder()
            ->emulation($this->browser)
            ->build();
    }
}
