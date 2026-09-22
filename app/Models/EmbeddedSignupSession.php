<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'partner_id',
    'whatsapp_connection_id',
    'state_token_hash',
    'status',
    'expires_at',
    'completed_at',
    'metadata',
])]
class EmbeddedSignupSession extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
