<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Models\Partner;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\WebhookUrlValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DeliverPartnerWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $partnerId,
        public string $eventType,
        public string $eventId,
        public array $payload,
    ) {}

    public function handle(WebhookUrlValidator $urlValidator): void
    {
        $partner = Partner::query()->find($this->partnerId);

        if ($partner === null) {
            return;
        }

        $endpoints = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if (! $urlValidator->isAllowed($endpoint->url)) {
                continue;
            }

            if (! $this->endpointSubscribed($endpoint, $this->eventType)) {
                continue;
            }

            $secret = (string) ($endpoint->secret ?? $partner->webhook_secret ?? '');

            if ($secret === '') {
                continue;
            }

            $delivery = WebhookDelivery::query()->firstOrCreate(
                [
                    'webhook_endpoint_id' => $endpoint->id,
                    'event_id' => $this->eventId,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'partner_id' => $partner->id,
                    'event_type' => $this->eventType,
                    'payload' => [
                        'data' => $this->payload,
                    ],
                    'status' => WebhookDeliveryStatus::Pending,
                    'attempt_count' => 0,
                ],
            );

            if ($delivery->status === WebhookDeliveryStatus::Delivered) {
                continue;
            }

            DeliverPartnerWebhookAttemptJob::dispatch($delivery->id);
        }
    }

    private function endpointSubscribed(WebhookEndpoint $endpoint, string $eventType): bool
    {
        $events = $endpoint->events;

        if ($events === null || $events === []) {
            return true;
        }

        return in_array($eventType, $events, true) || in_array('*', $events, true);
    }
}
