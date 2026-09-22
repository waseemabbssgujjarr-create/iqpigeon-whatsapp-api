<?php

namespace Tests\Concerns;

use App\Models\Partner;
use App\Models\User;

trait CreatesDashboardUsers
{
    protected function createVerifiedOwner(): array
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['owner_user_id' => $user->id]);
        $user->forceFill(['partner_id' => $partner->id])->save();

        return ['user' => $user, 'partner' => $partner];
    }
}
