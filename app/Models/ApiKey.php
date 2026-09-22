<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid',
    'partner_id',
    'label',
    'prefix',
    'key_hash',
    'environment',
    'revoked_at',
    'last_used_at',
    'expires_at',
])]
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(ApiScope::class, 'api_key_scope');
    }

    public function apiRequests(): HasMany
    {
        return $this->hasMany(ApiRequest::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasScope(string $scopeName): bool
    {
        return $this->scopes->contains('name', $scopeName);
    }
}
