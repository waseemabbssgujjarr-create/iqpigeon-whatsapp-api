<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'connection_id' => $this->whatsappConnection?->uuid,
            'direction' => $this->direction,
            'status' => $this->status?->value ?? $this->status,
            'to' => $this->to_number,
            'from' => $this->from_number,
            'type' => $this->message_type,
            'body' => $this->body,
            'wa_message_id' => $this->wa_message_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
