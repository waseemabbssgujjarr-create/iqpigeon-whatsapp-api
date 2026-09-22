<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'whatsapp_connection_id',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'waba_id',
    'phone_number_id',
    'metadata',
])]
#[Hidden(['access_token', 'refresh_token'])]
class WhatsappConnectionCredential extends Model
{
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function whatsappConnection(): BelongsTo
    {
        return $this->belongsTo(WhatsappConnection::class);
    }
}
