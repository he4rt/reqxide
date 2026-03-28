<?php

declare(strict_types=1);

namespace Reqxide\Cookie;

use Psr\Http\Message\UriInterface;
use Reqxide\Contract\CookieStoreInterface;

class CookieJar implements CookieStoreInterface
{
    private const string DEFAULT_PATH = '/';

    /** @var array<string, array<string, array<string, Cookie>>> */
    private array $cookies = [];

    /** @param list<string> $cookieHeaders */
    public function setCookies(array $cookieHeaders, UriInterface $uri): void
    {
        foreach ($cookieHeaders as $header) {
            $cookie = Cookie::parse($header);
            $this->add($cookie, $uri);
        }
    }

    /** @return list<string> */
    public function getCookies(UriInterface $uri): array
    {
        $host = $uri->getHost();

        if ($host === '') {
            return [];
        }

        $host = $this->normalizeDomain($host);
        $requestPath = $uri->getPath();

        if ($requestPath === '') {
            $requestPath = self::DEFAULT_PATH;
        }

        $isSecure = $uri->getScheme() === 'https';
        $result = [];

        foreach ($this->cookies as $domain => $pathMap) {
            if (! $this->domainMatch($host, $domain)) {
                continue;
            }

            foreach ($pathMap as $cookiePath => $nameMap) {
                if (! $this->pathMatch($requestPath, $cookiePath)) {
                    continue;
                }

                foreach ($nameMap as $cookie) {
                    if ($cookie->secure && ! $isSecure) {
                        continue;
                    }

                    if ($cookie->isExpired()) {
                        continue;
                    }

                    $result[] = $cookie->name.'='.$cookie->value;
                }
            }
        }

        return $result;
    }

    public function clear(): void
    {
        $this->cookies = [];
    }

    public function add(Cookie $cookie, UriInterface $uri): void
    {
        $host = $uri->getHost();

        if ($host === '') {
            return;
        }

        $normalizedHost = $this->normalizeDomain($host);

        if ($cookie->domain !== null) {
            $normalizedCookieDomain = $this->normalizeDomain($cookie->domain);

            if ($normalizedCookieDomain === '' || ! $this->domainMatch($normalizedHost, $normalizedCookieDomain)) {
                return;
            }

            $domain = $normalizedCookieDomain;
        } else {
            $domain = $normalizedHost;
        }

        $cookiePath = $cookie->path;

        if ($cookiePath === null || ! str_starts_with($cookiePath, self::DEFAULT_PATH)) {
            $cookiePath = $this->normalizePath($uri->getPath());
        }

        $expired = ($cookie->expires instanceof \DateTimeImmutable && $cookie->expires < new \DateTimeImmutable)
            || ($cookie->maxAge !== null && $cookie->maxAge === 0);

        if ($expired) {
            unset($this->cookies[$domain][$cookiePath][$cookie->name]);

            if (isset($this->cookies[$domain][$cookiePath]) && $this->cookies[$domain][$cookiePath] === []) {
                unset($this->cookies[$domain][$cookiePath]);
            }

            if (isset($this->cookies[$domain]) && $this->cookies[$domain] === []) {
                unset($this->cookies[$domain]);
            }

            return;
        }

        $this->cookies[$domain][$cookiePath][$cookie->name] = $cookie;
    }

    public function get(string $name, UriInterface $uri): ?Cookie
    {
        $host = $uri->getHost();

        if ($host === '') {
            return null;
        }

        $host = $this->normalizeDomain($host);
        $path = $uri->getPath();

        if ($path === '') {
            $path = self::DEFAULT_PATH;
        }

        return $this->cookies[$host][$path][$name] ?? null;
    }

    public function remove(string $name, UriInterface $uri): void
    {
        $host = $uri->getHost();

        if ($host === '') {
            return;
        }

        $host = $this->normalizeDomain($host);
        $path = $uri->getPath();

        if ($path === '') {
            $path = self::DEFAULT_PATH;
        }

        unset($this->cookies[$host][$path][$name]);

        if (isset($this->cookies[$host][$path]) && $this->cookies[$host][$path] === []) {
            unset($this->cookies[$host][$path]);
        }

        if (isset($this->cookies[$host]) && $this->cookies[$host] === []) {
            unset($this->cookies[$host]);
        }
    }

    /**
     * RFC 6265 section 5.1.3 — Domain matching.
     *
     * Returns true if the host and domain are identical, or if the host
     * is a subdomain of the domain (host ends with ".domain").
     */
    private function domainMatch(string $host, string $domain): bool
    {
        if ($domain === '') {
            return false;
        }

        if ($host === $domain) {
            return true;
        }

        return strlen($host) > strlen($domain)
            && $host[strlen($host) - strlen($domain) - 1] === '.'
            && str_ends_with($host, $domain);
    }

    /**
     * RFC 6265 section 5.1.4 — Path matching.
     *
     * Returns true if the request path and cookie path are identical,
     * or if the request path starts with the cookie path and either
     * the cookie path ends with '/' or the next character after the
     * cookie path in the request path is '/'.
     */
    private function pathMatch(string $requestPath, string $cookiePath): bool
    {
        if ($requestPath === $cookiePath) {
            return true;
        }

        return str_starts_with($requestPath, $cookiePath)
            && (str_ends_with($cookiePath, self::DEFAULT_PATH) || str_starts_with(substr($requestPath, strlen($cookiePath)), self::DEFAULT_PATH));
    }

    /**
     * RFC 6265 section 5.2.3 — Normalize a domain by stripping
     * leading dot, port, and trailing dot.
     */
    private function normalizeDomain(string $domain): string
    {
        $hostWithoutPort = explode(':', $domain)[0];
        $withoutLeading = ltrim($hostWithoutPort, '.');

        return rtrim($withoutLeading, '.');
    }

    /**
     * RFC 6265 section 5.1.4 — Compute the default cookie path.
     */
    private function normalizePath(string $path): string
    {
        if (! str_starts_with($path, self::DEFAULT_PATH)) {
            return self::DEFAULT_PATH;
        }

        $lastSlash = strrpos($path, '/');

        if ($lastSlash === 0) {
            return self::DEFAULT_PATH;
        }

        if ($lastSlash === false) {
            return self::DEFAULT_PATH;
        }

        return substr($path, 0, $lastSlash);
    }
}
