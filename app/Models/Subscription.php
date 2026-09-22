<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'partner_id',
    'plan_id',
    'type',
    'stripe_id',
    'stripe_status',
    'stripe_price',
    'quantity',
    'trial_ends_at',
    'ends_at',
])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return [
            'stripe_status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        $status = $this->stripe_status;

        return $status instanceof SubscriptionStatus
            ? $status->isBillable()
            : SubscriptionStatus::fromStripe((string) $status)->isBillable();
    }
}
