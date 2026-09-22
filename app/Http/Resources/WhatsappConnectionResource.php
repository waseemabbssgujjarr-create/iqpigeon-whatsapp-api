<?php

namespace App\Http\Resources;

use App\Models\WhatsappConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WhatsappConnection */
class WhatsappConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'external_ref' => $this->external_ref,
            'status' => $this->connection_status?->value ?? $this->connection_status,
            'display_phone_number' => $this->display_phone_number,
            'phone_number_id' => $this->phone_number_id,
            'waba_id' => $this->waba_id,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'onboarding_url' => $this->when(
                isset($this->additional['onboarding_url']),
                fn () => $this->additional['onboarding_url'],
            ),
            'onboarding_expires_at' => $this->when(
                isset($this->additional['onboarding_expires_at']),
                fn () => $this->additional['onboarding_expires_at'],
            ),
        ];
    }
}
