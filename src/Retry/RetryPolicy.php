<?php

declare(strict_types=1);

namespace Reqxide\Retry;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Reqxide\Contract\RetryPolicyInterface;

final readonly class RetryPolicy implements RetryPolicyInterface
{
    /**
     * @param  (Closure(RequestInterface, ?ResponseInterface, ?\Throwable): bool)|null  $classifier
     */
    private function __construct(
        private int $maxRetries,
        private ?Closure $classifier,
        private int $baseDelayMs,
        private ?RetryBudget $budget,
    ) {}

    public static function never(): self
    {
        return new self(maxRetries: 0, classifier: null, baseDelayMs: 0, budget: null);
    }

    public static function default(): self
    {
        return new self(
            maxRetries: 2,
            classifier: null,
            baseDelayMs: 500,
            budget: new RetryBudget(ratio: 0.2),
        );
    }

    /**
     * @param  Closure(RequestInterface, ?ResponseInterface, ?\Throwable): bool  $classifier
     */
    public static function custom(Closure $classifier, int $maxRetries = 2, int $baseDelayMs = 500): self
    {
        return new self(
            maxRetries: $maxRetries,
            classifier: $classifier,
            baseDelayMs: $baseDelayMs,
            budget: new RetryBudget(ratio: 0.2),
        );
    }

    public function shouldRetry(
        RequestInterface $request,
        ?ResponseInterface $response,
        ?\Throwable $exception,
        int $attempt,
    ): bool {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        if ($this->budget instanceof RetryBudget && ! $this->budget->canRetry()) {
            return false;
        }

        $isRetryable = $this->classifier instanceof Closure
            ? ($this->classifier)($request, $response, $exception)
            : $this->defaultClassifier($response, $exception);

        if ($isRetryable && $this->budget instanceof RetryBudget) {
            $this->budget->withdraw();
        } elseif ($this->budget instanceof RetryBudget) {
            $this->budget->deposit();
        }

        return $isRetryable;
    }

    public function delayMs(int $attempt): int
    {
        return $this->baseDelayMs * (2 ** $attempt);
    }

    private function defaultClassifier(?ResponseInterface $response, ?\Throwable $exception): bool
    {
        if ($exception instanceof \Throwable) {
            return true;
        }

        return $response instanceof ResponseInterface && $response->getStatusCode() >= 500;
    }
}
