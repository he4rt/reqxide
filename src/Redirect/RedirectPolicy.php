<?php

declare(strict_types=1);

namespace Reqxide\Redirect;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\RedirectPolicyInterface;

final readonly class RedirectPolicy implements RedirectPolicyInterface
{
    /**
     * @param  (Closure(RequestInterface, ResponseInterface, int): bool)|null  $customPolicy
     */
    private function __construct(
        private int $maxRedirects,
        private ?Closure $customPolicy,
    ) {}

    public static function limited(int $max = 10): self
    {
        return new self(maxRedirects: $max, customPolicy: null);
    }

    public static function none(): self
    {
        return new self(maxRedirects: 0, customPolicy: null);
    }

    /**
     * @param  Closure(RequestInterface, ResponseInterface, int): bool  $policy
     */
    public static function custom(Closure $policy, int $max = 10): self
    {
        return new self(maxRedirects: $max, customPolicy: $policy);
    }

    public static function default(): self
    {
        return self::limited(10);
    }

    public function shouldFollow(
        RequestInterface $request,
        ResponseInterface $response,
        int $redirectCount,
    ): bool {
        if ($this->maxRedirects === 0) {
            return false;
        }

        if ($redirectCount >= $this->maxRedirects) {
            return false;
        }

        if ($this->customPolicy instanceof Closure) {
            return ($this->customPolicy)($request, $response, $redirectCount);
        }

        return true;
    }

    public function maxRedirects(): int
    {
        return $this->maxRedirects;
    }
}
