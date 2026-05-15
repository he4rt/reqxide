<?php

declare(strict_types=1);

namespace Reqxide\Proxy;

readonly class Proxy
{
    /**
     * @param  list<string>  $noProxy
     */
    public function __construct(
        public ProxyScheme $scheme,
        public string $host,
        public int $port,
        public ?string $username = null,
        public ?string $password = null,
        public array $noProxy = [],
    ) {}

    public static function http(string $address): self
    {
        return self::parse(ProxyScheme::Http, $address);
    }

    public static function https(string $address): self
    {
        return self::parse(ProxyScheme::Https, $address);
    }

    public static function socks5(string $address): self
    {
        return self::parse(ProxyScheme::Socks5, $address);
    }

    public static function socks5h(string $address): self
    {
        return self::parse(ProxyScheme::Socks5h, $address);
    }

    public static function socks4(string $address): self
    {
        return self::parse(ProxyScheme::Socks4, $address);
    }

    public function toUrl(): string
    {
        $auth = '';
        if ($this->username !== null) {
            $auth = $this->password !== null
                ? urlencode($this->username).':'.urlencode($this->password).'@'
                : urlencode($this->username).'@';
        }

        return $this->scheme->value.'://'.$auth.$this->host.':'.$this->port;
    }

    public function shouldBypass(string $host): bool
    {
        foreach ($this->noProxy as $pattern) {
            if ($pattern === '*') {
                return true;
            }

            if (strcasecmp($host, $pattern) === 0) {
                return true;
            }

            if (str_starts_with($pattern, '.') && str_ends_with(strtolower($host), strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    private static function parse(ProxyScheme $scheme, string $address): self
    {
        // Prefix with // so parse_url can detect user:pass@host:port
        $urlToParse = $address;
        if (! str_contains($address, '://') && ! str_starts_with($address, '//')) {
            $urlToParse = '//'.$address;
        }

        $parts = parse_url($urlToParse);

        if ($parts === false) {
            $hostPort = explode(':', $address, 2);

            return new self(
                scheme: $scheme,
                host: $hostPort[0],
                port: isset($hostPort[1]) ? (int) $hostPort[1] : self::defaultPort($scheme),
            );
        }

        return new self(
            scheme: $scheme,
            host: $parts['host'] ?? $parts['path'] ?? $address,
            port: $parts['port'] ?? self::defaultPort($scheme),
            username: isset($parts['user']) ? urldecode($parts['user']) : null,
            password: isset($parts['pass']) ? urldecode($parts['pass']) : null,
        );
    }

    private static function defaultPort(ProxyScheme $scheme): int
    {
        return match ($scheme) {
            ProxyScheme::Http => 80,
            ProxyScheme::Https => 443,
            ProxyScheme::Socks4, ProxyScheme::Socks5, ProxyScheme::Socks5h => 1080,
        };
    }
}
