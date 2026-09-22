<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\MetaClient;
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
        $phoneNumberId = $connection->phone_number_id;

        if ($phoneNumberId === null) {
            $message->forceFill(['status' => MessageStatus::Failed])->save();

            return;
        }

        /** @var WhatsappConnectionCredential|null $credentials */
        $credentials = $connection->credentials;

        if ($credentials === null) {
            $message->forceFill(['status' => MessageStatus::Failed])->save();

            return;
        }

        $graphBody = $this->buildGraphPayload($message);

        $response = $metaClient->graph(
            'POST',
            $phoneNumberId.'/messages',
            body: $graphBody,
            accessToken: $credentials->access_token,
        );

        if ($response->failed()) {
            Log::warning('message.send_failed', [
                'message_id' => $message->id,
                'status' => $response->status(),
            ]);
            $message->forceFill(['status' => MessageStatus::Failed])->save();

            return;
        }

        $waMessageId = (string) data_get($response->json(), 'messages.0.id', '');

        $message->forceFill([
            'status' => MessageStatus::Sent,
            'wa_message_id' => $waMessageId !== '' ? $waMessageId : null,
            'sent_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGraphPayload(Message $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => (string) $message->to_number,
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
