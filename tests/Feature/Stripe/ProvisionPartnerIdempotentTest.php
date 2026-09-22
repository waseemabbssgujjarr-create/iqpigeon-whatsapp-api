<?php

namespace Tests\Feature\Stripe;

use App\Jobs\ProvisionPartnerJob;
use App\Models\ApiKey;
use App\Models\Partner;
use Database\Seeders\ApiScopeSeeder;
use Tests\TestCase;

class ProvisionPartnerIdempotentTest extends TestCase
{
    public function test_provision_partner_job_twice_does_not_duplicate_api_keys(): void
    {
        $this->seed(ApiScopeSeeder::class);

        $partner = Partner::factory()->inactive()->create();

        (new ProvisionPartnerJob($partner->id))->handle(app(\App\Services\PartnerProvisioningService::class));
        (new ProvisionPartnerJob($partner->id))->handle(app(\App\Services\PartnerProvisioningService::class));

        $this->assertSame(1, ApiKey::query()->where('partner_id', $partner->id)->whereNull('revoked_at')->count());
    }
}
