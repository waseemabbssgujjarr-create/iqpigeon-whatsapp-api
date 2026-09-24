<?php

namespace App\Services\Messaging;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Support\Carbon;

/**
 * Apply Meta Cloud API message status webhooks (statuses[]) to platform messages.
 */
class MetaMessageStatusSync
{
    /**
     * @param  array<string, mixed>  $value  Meta change "value" object (messages/statuses/metadata).
     */
    public function applyFromWebhookValue(array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $statusRow) {
            if (! is_array($statusRow)) {
                continue;
            }

            $this->applySingleStatus($statusRow);
        }
    }

    /**
     * @param  array<string, mixed>  $statusRow
     */
    private function applySingleStatus(array $statusRow): void
    {
        $waMessageId = (string) ($statusRow['id'] ?? '');

        if ($waMessageId === '') {
            return;
        }

        $message = Message::query()->where('wa_message_id', $waMessageId)->first();

        if ($message === null) {
            return;
        }

        $metaStatus = strtolower((string) ($statusRow['status'] ?? ''));

        if ($metaStatus === 'failed') {
            $error = is_array($statusRow['errors'][0] ?? null) ? $statusRow['errors'][0] : [];
            $code = (string) ($error['code'] ?? '');
            $title = (string) ($error['title'] ?? '');
            $detail = (string) ($error['message'] ?? $title);
            if ($code !== '') {
                $detail = trim('Meta status error '.$code.': '.$detail);
            }

            $message->forceFill([
                'status' => MessageStatus::Failed,
                'failure_code' => $code !== '' ? 'meta_status_'.$code : 'meta_status_failed',
                'failure_message' => substr($detail !== '' ? $detail : 'Meta reported message delivery failed.', 0, 2000),
            ])->save();

            return;
        }

        $timestamp = $this->parseMetaTimestamp($statusRow['timestamp'] ?? null);

        if ($metaStatus === 'sent') {
            $message->forceFill([
                'status' => MessageStatus::Sent,
                'sent_at' => $message->sent_at ?? $timestamp ?? now(),
            ])->save();

            return;
        }

        if ($metaStatus === 'delivered') {
            $message->forceFill([
                'status' => MessageStatus::Delivered,
                'delivered_at' => $timestamp ?? now(),
            ])->save();

            return;
        }

        if ($metaStatus === 'read') {
            $message->forceFill([
                'status' => MessageStatus::Read,
                'read_at' => $timestamp ?? now(),
            ])->save();
        }
    }

    private function parseMetaTimestamp(mixed $timestamp): ?Carbon
    {
        if (is_int($timestamp) || (is_string($timestamp) && ctype_digit($timestamp))) {
            return Carbon::createFromTimestamp((int) $timestamp);
        }

        return null;
    }
}
