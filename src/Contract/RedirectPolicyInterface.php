<?php

declare(strict_types=1);

namespace Reqxide\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RedirectPolicyInterface
{
    public function shouldFollow(
        RequestInterface $request,
        ResponseInterface $response,
        int $redirectCount,
    ): bool;

    public function maxRedirects(): int;
}
