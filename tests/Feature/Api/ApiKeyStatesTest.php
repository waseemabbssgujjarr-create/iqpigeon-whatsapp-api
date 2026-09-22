<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class ApiKeyStatesTest extends TestCase
{
    use CreatesApiPartners;

    public function test_invalid_bearer_token_is_unauthenticated(): void
    {
        $this->seed(ApiScopeSeeder::class);

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer not-a-valid-key', 'Accept' => 'application/json'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_expired_api_key_is_rejected(): void
    {
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $key = ApiKey::query()->where('partner_id', $partner->id)->firstOrFail();
        $key->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->getJson('/api/v1/me', $this->withBearer($secret))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $key = ApiKey::query()->where('partner_id', $partner->id)->firstOrFail();
        app(ApiKeyService::class)->revoke($key);

        $this->getJson('/api/v1/me', $this->withBearer($secret))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_successful_auth_updates_last_used_at(): void
    {
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $key = ApiKey::query()->where('partner_id', $partner->id)->firstOrFail();
        $this->assertNull($key->last_used_at);

        $this->getJson('/api/v1/me', $this->withBearer($secret))->assertOk();

        $this->assertNotNull($key->fresh()->last_used_at);
    }
}
