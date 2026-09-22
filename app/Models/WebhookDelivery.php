<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'webhook_endpoint_id',
    'partner_id',
    'event_id',
    'event_type',
    'payload',
    'status',
    'attempt_count',
    'next_retry_at',
    'delivered_at',
    'response_status',
    'response_body',
    'error_message',
])]
class WebhookDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookDeliveryStatus::class,
            'attempt_count' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
