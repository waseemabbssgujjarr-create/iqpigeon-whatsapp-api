<?php

namespace App\Http\Resources;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Partner */
class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status?->value ?? $this->status,
            'provisioning_status' => $this->provisioning_status?->value ?? $this->provisioning_status,
            'activated_at' => $this->activated_at?->toIso8601String(),
        ];
    }
}
