<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function view(User $user, ApiKey $apiKey): bool
    {
        return $this->samePartner($user, $apiKey);
    }

    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $this->samePartner($user, $apiKey);
    }

    private function samePartner(User $user, ApiKey $apiKey): bool
    {
        $partnerId = $user->partner_id ?? $user->ownedPartner?->id;

        return $partnerId !== null && (int) $partnerId === (int) $apiKey->partner_id;
    }
}
