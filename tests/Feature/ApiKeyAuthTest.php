<?php

namespace Tests\Feature;

use App\Models\Partner;
use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use CreatesApiPartners;

    public function test_api_requests_require_valid_bearer_key(): void
    {
        $this->seed(ApiScopeSeeder::class);

        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_valid_api_key_can_access_me_endpoint(): void
    {
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $response = $this->getJson('/api/v1/me', $this->withBearer($secret));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['partner' => ['id', 'name']]]);
    }

    public function test_inactive_partner_cannot_use_api(): void
    {
        $this->seed(ApiScopeSeeder::class);

        $partner = Partner::factory()->inactive()->create();
        $generated = app(\App\Services\ApiKeyService::class)->generate($partner, 'Test', ['connections.read']);

        $response = $this->getJson('/api/v1/me', $this->withBearer($generated['secret']));

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'partner_not_active');
    }
}
