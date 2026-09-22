<?php

namespace Database\Factories;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'status' => PartnerStatus::Active,
            'provisioning_status' => ProvisioningStatus::Active,
            'activated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'provisioning_status' => ProvisioningStatus::Registered,
            'status' => PartnerStatus::Pending,
            'activated_at' => null,
        ]);
    }
}
