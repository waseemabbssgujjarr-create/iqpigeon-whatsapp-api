<?php

namespace App\Services\Auth;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Str;

class PartnerProvisioner
{
    public function createForOwner(User $user, string $companyName): Partner
    {
        $slugBase = Str::slug($companyName) ?: 'partner';
        $slug = $slugBase;
        $suffix = 1;

        while (Partner::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'name' => $companyName,
            'slug' => $slug,
            'status' => PartnerStatus::Pending,
            'provisioning_status' => ProvisioningStatus::Registered,
        ]);

        $user->forceFill(['partner_id' => $partner->id])->save();

        return $partner;
    }
}
