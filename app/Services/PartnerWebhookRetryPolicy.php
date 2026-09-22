<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

class PartnerWebhookRetryPolicy
{
    public function maxAttempts(): int
    {
        return (int) config('webhooks.delivery.max_attempts', 5);
    }

    /**
     * @return list<int>
     */
    public function backoffSeconds(): array
    {
        /** @var list<int> $backoff */
        $backoff = config('webhooks.delivery.backoff_seconds', [30, 60, 120, 300]);

        return $backoff;
    }

    public function timeoutSeconds(): int
    {
        return max(1, (int) config('webhooks.delivery.timeout_seconds', 15));
    }

    public function shouldRetryHttpStatus(?int $statusCode): bool
    {
        if ($statusCode === null) {
            return true;
        }

        if ($statusCode === 408 || $statusCode === 429) {
            return true;
        }

        if ($statusCode >= 500) {
            return true;
        }

        return false;
    }

    public function shouldRetryThrowable(Throwable $throwable): bool
    {
        if ($throwable instanceof ConnectionException) {
            return true;
        }

        if ($throwable instanceof RequestException) {
            return $this->shouldRetryHttpStatus($throwable->response?->status());
        }

        return false;
    }

    public function backoffDelayForAttempt(int $attemptNumber): int
    {
        $schedule = $this->backoffSeconds();
        $index = max(0, $attemptNumber - 1);

        return $schedule[$index] ?? $schedule[array_key_last($schedule)];
    }
}
