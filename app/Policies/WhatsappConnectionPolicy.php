<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;
use App\Models\WhatsappConnection;

class WhatsappConnectionPolicy
{
    public function view(User $user, WhatsappConnection $connection): bool
    {
        return $this->samePartner($user, $connection);
    }

    public function delete(User $user, WhatsappConnection $connection): bool
    {
        return $this->samePartner($user, $connection);
    }

    public function viewApi(?User $user, WhatsappConnection $connection, Partner $partner): bool
    {
        return $connection->partner_id === $partner->id;
    }

    public function deleteApi(?User $user, WhatsappConnection $connection, Partner $partner): bool
    {
        return $connection->partner_id === $partner->id;
    }

    private function samePartner(User $user, WhatsappConnection $connection): bool
    {
        $partnerId = $user->partner_id ?? $user->ownedPartner?->id;

        return $partnerId !== null && (int) $partnerId === (int) $connection->partner_id;
    }
}
