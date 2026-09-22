<?php

namespace App\Models;

use App\Enums\ConnectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uuid',
    'partner_id',
    'external_ref',
    'waba_id',
    'phone_number_id',
    'display_phone_number',
    'meta_business_id',
    'connection_status',
    'metadata',
    'connected_at',
    'disconnected_at',
])]
class WhatsappConnection extends Model
{
    protected function casts(): array
    {
        return [
            'connection_status' => ConnectionStatus::class,
            'metadata' => 'array',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function credentials(): HasOne
    {
        return $this->hasOne(WhatsappConnectionCredential::class);
    }

    public function embeddedSignupSessions(): HasMany
    {
        return $this->hasMany(EmbeddedSignupSession::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
