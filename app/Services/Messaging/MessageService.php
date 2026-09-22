<?php

namespace App\Services\Messaging;

use App\Enums\ConnectionStatus;
use App\Enums\MessageStatus;
use App\Jobs\SendOutboundMessageJob;
use App\Models\Message;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessageService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function queueOutbound(
        Partner $partner,
        WhatsappConnection $connection,
        string $toNumber,
        string $messageType,
        ?string $body = null,
        array $payload = [],
    ): Message {
        if ($connection->partner_id !== $partner->id) {
            throw ValidationException::withMessages([
                'connection_id' => ['Connection does not belong to this partner.'],
            ]);
        }

        if ($connection->connection_status !== ConnectionStatus::Active) {
            throw ValidationException::withMessages([
                'connection_id' => ['WhatsApp connection is not active.'],
            ]);
        }

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'direction' => 'outbound',
            'status' => MessageStatus::Queued,
            'from_number' => $connection->display_phone_number,
            'to_number' => $toNumber,
            'message_type' => $messageType,
            'body' => $body,
            'payload' => $payload === [] ? null : $payload,
        ]);

        SendOutboundMessageJob::dispatch($message->id);

        return $message;
    }
}
