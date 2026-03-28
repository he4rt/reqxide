<?php

declare(strict_types=1);

namespace Reqxide\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class ReqxideException extends \RuntimeException implements ClientExceptionInterface {}
