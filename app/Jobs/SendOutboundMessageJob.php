<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\MetaClient;
use App\Support\WhatsAppRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOutboundMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $messageId,
    ) {}

    public function handle(MetaClient $metaClient): void
    {
        $message = Message::query()->with('whatsappConnection.credentials')->find($this->messageId);

        if ($message === null) {
            return;
        }

        if ($message->status !== MessageStatus::Queued) {
            return;
        }

        $connection = $message->whatsappConnection;

        /** @var WhatsappConnectionCredential|null $credentials */
        $credentials = $connection->credentials;

        if ($credentials === null) {
            $this->markFailed($message, 'missing_credentials', 'WhatsApp connection has no stored Meta credentials.');

            return;
        }

        $phoneNumberId = $connection->phone_number_id ?? $credentials->phone_number_id;

        if ($phoneNumberId === null || $phoneNumberId === '') {
            $this->markFailed($message, 'missing_phone_number_id', 'WhatsApp phone number ID is not configured on this connection.');

            return;
        }

        $to = WhatsAppRecipient::normalizeForGraph((string) $message->to_number);

        if ($to === '') {
            $this->markFailed($message, 'invalid_recipient', 'Recipient phone number is empty or invalid after normalization.');

            return;
        }

        $graphBody = $this->buildGraphPayload($message, $to);

        $response = $metaClient->graph(
            'POST',
            $phoneNumberId.'/messages',
            body: $graphBody,
            accessToken: $credentials->access_token,
        );

        if ($response->failed()) {
            $providerCode = (string) data_get($response->json(), 'error.code', '');
            $providerMessage = (string) data_get($response->json(), 'error.message', 'Meta Graph request failed.');
            $providerType = (string) data_get($response->json(), 'error.type', '');

            Log::warning('message.send_failed', [
                'message_id' => $message->id,
                'http_status' => $response->status(),
                'provider_type' => $providerType !== '' ? $providerType : null,
                'provider_code' => $providerCode !== '' ? $providerCode : null,
            ]);

            $detail = trim($providerMessage);
            if ($providerCode !== '') {
                $detail = 'Meta error '.$providerCode.': '.$detail;
            }
            $detail = 'HTTP '.$response->status().'. '.$detail;

            $this->markFailed($message, 'graph_request_failed', substr($detail, 0, 2000));

            return;
        }

        $waMessageId = (string) data_get($response->json(), 'messages.0.id', '');

        if ($waMessageId === '') {
            $this->markFailed($message, 'graph_invalid_response', 'Meta Graph returned success but no WhatsApp message id.');

            return;
        }

        $message->forceFill([
            'status' => MessageStatus::Sent,
            'wa_message_id' => $waMessageId,
            'sent_at' => now(),
            'failure_code' => null,
            'failure_message' => null,
        ])->save();
    }

    private function markFailed(Message $message, string $code, string $detail): void
    {
        $message->forceFill([
            'status' => MessageStatus::Failed,
            'failure_code' => $code,
            'failure_message' => $detail,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGraphPayload(Message $message, string $toDigits): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toDigits,
        ];

        if ($message->message_type === 'template') {
            $payload['type'] = 'template';
            $payload['template'] = $message->payload ?? [];
        } elseif ($message->message_type === 'text') {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => (string) $message->body];
        } else {
            $payload['type'] = $message->message_type;
            if ($message->payload !== null) {
                $payload[$message->message_type] = $message->payload;
            }
        }

        return $payload;
    }
}
