<?php

namespace App\Jobs;

use App\Services\PartnerWebhookDeliveryExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverPartnerWebhookAttemptJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $webhookDeliveryId,
    ) {}

    public function tries(): int
    {
        return (int) config('webhooks.delivery.max_attempts', 5);
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var list<int> $seconds */
        $seconds = config('webhooks.delivery.backoff_seconds', [30, 60, 120, 300]);

        return $seconds;
    }

    public function handle(PartnerWebhookDeliveryExecutor $executor): void
    {
        $attemptNumber = $this->attempts();

        $executor->attempt(
            \App\Models\WebhookDelivery::query()->findOrFail($this->webhookDeliveryId),
            $attemptNumber,
        );
    }
}
