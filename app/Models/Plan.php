<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'description',
    'stripe_price_id',
    'price_cents',
    'currency',
    'interval',
    'feature_json',
    'is_active',
    'sort_order',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'feature_json' => 'array',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return list<string>
     */
    public function defaultScopes(): array
    {
        $scopes = $this->feature_json['default_scopes'] ?? [];

        return is_array($scopes) ? array_values($scopes) : [];
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        return data_get($this->feature_json, $key, $default);
    }
}
