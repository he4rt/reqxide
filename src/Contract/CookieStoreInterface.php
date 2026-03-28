<?php

declare(strict_types=1);

namespace Reqxide\Contract;

use Psr\Http\Message\UriInterface;

interface CookieStoreInterface
{
    /** @param  list<string>  $cookieHeaders */
    public function setCookies(array $cookieHeaders, UriInterface $uri): void;

    /** @return list<string> */
    public function getCookies(UriInterface $uri): array;

    public function clear(): void;
}
