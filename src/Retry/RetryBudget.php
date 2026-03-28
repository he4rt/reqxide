<?php

declare(strict_types=1);

namespace Reqxide\Retry;

class RetryBudget
{
    private int $totalRequests = 0;

    private int $retryRequests = 0;

    public function __construct(
        private readonly float $ratio = 0.2,
        private readonly int $minRequests = 10,
    ) {}

    public function deposit(): void
    {
        $this->totalRequests++;
    }

    public function withdraw(): void
    {
        $this->retryRequests++;
        $this->totalRequests++;
    }

    public function canRetry(): bool
    {
        if ($this->totalRequests < $this->minRequests) {
            return true;
        }

        return ($this->retryRequests / $this->totalRequests) < $this->ratio;
    }

    public function reset(): void
    {
        $this->totalRequests = 0;
        $this->retryRequests = 0;
    }
}
