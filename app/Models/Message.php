<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'partner_id',
    'whatsapp_connection_id',
    'direction',
    'wa_message_id',
    'status',
    'failure_code',
    'failure_message',
    'from_number',
    'to_number',
    'message_type',
    'body',
    'payload',
    'sent_at',
    'delivered_at',
    'read_at',
])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function whatsappConnection(): BelongsTo
    {
        return $this->belongsTo(WhatsappConnection::class);
    }
}
