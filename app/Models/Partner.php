<?php

namespace App\Models;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

#[Fillable([
    'uuid',
    'owner_user_id',
    'name',
    'slug',
    'status',
    'provisioning_status',
    'stripe_id',
    'pm_type',
    'pm_last_four',
    'trial_ends_at',
    'webhook_secret',
    'metadata',
    'activated_at',
])]
#[Hidden(['webhook_secret'])]
class Partner extends Model
{
    /** @use HasFactory<\Database\Factories\PartnerFactory> */
    use Billable, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'provisioning_status' => ProvisioningStatus::class,
            'webhook_secret' => 'encrypted',
            'metadata' => 'array',
            'trial_ends_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function whatsappConnections(): HasMany
    {
        return $this->hasMany(WhatsappConnection::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }
}
