<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Database\Seeders\ApiScopeSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class ApiKeyLifecycleTest extends TestCase
{
    use CreatesApiPartners;

    public function test_api_key_secret_is_hashed_in_database(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey();

        $key = ApiKey::query()->where('partner_id', $partner->id)->first();

        $this->assertNotSame($secret, $key->key_hash);
        $this->assertTrue(password_verify($secret, $key->key_hash));
    }

    public function test_revoked_key_cannot_authenticate(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $key = ApiKey::query()->where('partner_id', $partner->id)->first();
        app(ApiKeyService::class)->revoke($key);

        $this->getJson('/api/v1/me', $this->withBearer($secret))
            ->assertUnauthorized();
    }
}
