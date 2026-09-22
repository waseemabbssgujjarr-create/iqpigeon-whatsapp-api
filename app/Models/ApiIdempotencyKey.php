<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'partner_id',
    'idempotency_key',
    'request_method',
    'request_path',
    'request_hash',
    'response_status',
    'response_body',
    'expires_at',
])]
class ApiIdempotencyKey extends Model
{
    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'expires_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
