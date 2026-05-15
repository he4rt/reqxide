<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

readonly class ConnectionGroup
{
    public function __construct(
        public string $identifier,
    ) {}

    public static function named(string $name): self
    {
        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->identifier === $other->identifier;
    }
}
