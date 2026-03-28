<?php

declare(strict_types=1);

namespace Reqxide\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Emulation\Profile;

interface TransportInterface
{
    public function send(
        RequestInterface $request,
        Profile $profile,
    ): ResponseInterface;

    public function supportsFingerprinting(): bool;

    public function supportsHttp2Configuration(): bool;
}
