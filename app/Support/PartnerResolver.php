<?php

namespace App\Support;

use App\Models\Partner;
use App\Models\User;

class PartnerResolver
{
    public static function fromUser(?User $user): ?Partner
    {
        if ($user === null) {
            return null;
        }

        if ($user->partner_id) {
            return $user->partner;
        }

        return $user->ownedPartner;
    }
}
