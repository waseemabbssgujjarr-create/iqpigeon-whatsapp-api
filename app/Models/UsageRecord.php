<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'partner_id',
    'metric',
    'quantity',
    'period_start',
    'period_end',
    'metadata',
])]
class UsageRecord extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
