<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'description',
    'group',
])]
class ApiScope extends Model
{
    public function apiKeys(): BelongsToMany
    {
        return $this->belongsToMany(ApiKey::class, 'api_key_scope');
    }
}
