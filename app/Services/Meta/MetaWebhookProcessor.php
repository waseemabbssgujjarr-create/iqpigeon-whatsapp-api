<?php

namespace App\Services\Meta;

use App\Jobs\DeliverPartnerWebhookJob;
use App\Jobs\ProcessMetaWebhookJob;
use App\Models\SystemEvent;
use Illuminate\Support\Str;

class MetaWebhookProcessor
{
    public function persistAndDispatch(array $payload): void
    {
        $externalId = $this->resolveExternalId($payload);

        $event = SystemEvent::query()->firstOrCreate(
            [
                'source' => 'meta',
                'external_id' => $externalId,
            ],
            [
                'event_type' => $this->resolveEventType($payload),
                'payload' => $payload,
            ],
        );

        if ($event->processed_at !== null) {
            return;
        }

        ProcessMetaWebhookJob::dispatch($event->id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function processStoredEvent(SystemEvent $event): void
    {
        if ($event->processed_at !== null) {
            return;
        }

        $payload = $event->payload;
        $entries = $payload['entry'] ?? [];

        if (! is_array($entries)) {
            $event->forceFill(['processed_at' => now()])->save();

            return;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $changes = $entry['changes'] ?? [];

            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $this->handleChange($change);
            }
        }

        $event->forceFill(['processed_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $change
     */
    private function handleChange(array $change): void
    {
        $value = $change['value'] ?? [];

        if (! is_array($value)) {
            return;
        }

        $phoneNumberId = data_get($value, 'metadata.phone_number_id')
            ?? data_get($value, 'phone_number_id');

        if ($phoneNumberId === null) {
            return;
        }

        $connection = \App\Models\WhatsappConnection::query()
            ->where('phone_number_id', (string) $phoneNumberId)
            ->first();

        if ($connection === null) {
            return;
        }

        $eventType = (string) ($change['field'] ?? 'meta.webhook');
        $eventId = (string) Str::uuid();

        DeliverPartnerWebhookJob::dispatch(
            $connection->partner_id,
            $eventType,
            $eventId,
            $value,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveExternalId(array $payload): string
    {
        $entryId = data_get($payload, 'entry.0.id');

        if (is_string($entryId) && $entryId !== '') {
            return $entryId.'_'.hash('sha256', json_encode($payload));
        }

        return hash('sha256', json_encode($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveEventType(array $payload): string
    {
        $field = data_get($payload, 'entry.0.changes.0.field');

        return is_string($field) && $field !== '' ? $field : 'meta.webhook';
    }
}
