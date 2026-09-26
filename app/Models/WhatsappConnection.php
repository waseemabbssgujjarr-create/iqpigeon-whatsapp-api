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
    protected static function booted(): void
    {
        static::saving(function (WhatsappConnection $connection): void {
            if ($connection->connection_status !== ConnectionStatus::Active) {
                return;
            }

            $phoneId = $connection->phone_number_id;

            if ($phoneId === null || $phoneId === '') {
                $connection->connection_status = ConnectionStatus::Pending;
                $connection->connected_at = null;

                return;
            }

            $registeredAt = data_get($connection->metadata, 'cloud_api_registered_at');

            if (! is_string($registeredAt) || $registeredAt === '') {
                $connection->connection_status = ConnectionStatus::Pending;
                $connection->connected_at = null;
            }
        });
    }

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

    /**
     * `(partner_id, phone_number_id)` is unique; disconnected rows must not block reconnect.
     */
    public static function releasePhoneNumberId(int $partnerId, string $phoneNumberId, int $exceptConnectionId): void
    {
        $phoneNumberId = trim($phoneNumberId);

        if ($phoneNumberId === '') {
            return;
        }

        $connectionIds = static::query()
            ->where('partner_id', $partnerId)
            ->where('phone_number_id', $phoneNumberId)
            ->where('id', '!=', $exceptConnectionId)
            ->pluck('id');

        if ($connectionIds->isEmpty()) {
            return;
        }

        static::query()
            ->whereIn('id', $connectionIds)
            ->update(['phone_number_id' => null]);

        WhatsappConnectionCredential::query()
            ->whereIn('whatsapp_connection_id', $connectionIds)
            ->update(['phone_number_id' => null]);
    }
}
