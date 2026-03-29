<?php

declare(strict_types=1);

namespace Reqxide\Cookie;

readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public ?string $domain = null,
        public ?string $path = null,
        public ?int $maxAge = null,
        public ?\DateTimeImmutable $expires = null,
        public bool $secure = false,
        public bool $httpOnly = false,
        public ?string $sameSite = null,
    ) {}

    public static function parse(string $setCookieHeader): self
    {
        $parts = explode(';', $setCookieHeader);
        $nameValue = trim(array_shift($parts));
        $equalsPos = strpos($nameValue, '=');

        if ($equalsPos === false) {
            return new self(name: $nameValue, value: '');
        }

        $name = trim(substr($nameValue, 0, $equalsPos));
        $value = trim(substr($nameValue, $equalsPos + 1));

        $domain = null;
        $path = null;
        $maxAge = null;
        $expires = null;
        $secure = false;
        $httpOnly = false;
        $sameSite = null;

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $attrEqualsPos = strpos($part, '=');

            if ($attrEqualsPos === false) {
                $attrName = strtolower($part);

                if ($attrName === 'secure') {
                    $secure = true;
                } elseif ($attrName === 'httponly') {
                    $httpOnly = true;
                }

                continue;
            }

            $attrName = strtolower(trim(substr($part, 0, $attrEqualsPos)));
            $attrValue = trim(substr($part, $attrEqualsPos + 1));

            switch ($attrName) {
                case 'domain':
                    $domain = $attrValue;
                    break;
                case 'path':
                    $path = $attrValue;
                    break;
                case 'max-age':
                    $maxAge = (int) $attrValue;
                    break;
                case 'expires':
                    $parsed = \DateTimeImmutable::createFromFormat('D, d M Y H:i:s T', $attrValue);
                    if ($parsed !== false) {
                        $expires = $parsed;
                    }

                    break;
                case 'samesite':
                    $sameSite = $attrValue;
                    break;
            }
        }

        return new self(
            name: $name,
            value: $value,
            domain: $domain,
            path: $path,
            maxAge: $maxAge,
            expires: $expires,
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite,
        );
    }

    public function isExpired(): bool
    {
        if ($this->maxAge !== null && $this->maxAge <= 0) {
            return true;
        }

        return $this->expires instanceof \DateTimeImmutable && $this->expires < new \DateTimeImmutable;
    }
}
