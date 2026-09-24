<?php

namespace Tests\Feature;

use App\Enums\ConnectionStatus;
use App\Models\Partner;
use App\Services\CrmReturnUrlService;
use App\Support\OnboardingReturnSignature;
use Database\Seeders\ApiScopeSeeder;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class CrmReturnUrlTest extends TestCase
{
    use CreatesApiPartners;

    public function test_return_url_must_be_allowlisted_for_api_connection_create(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.write']);

        $response = $this->postJson('/api/v1/connections', [
            'return_url' => 'https://evil.example/hook',
        ], $this->connectionCreateHeaders($secret));

        $response->assertStatus(422)->assertJsonPath('error.code', 'return_url_not_allowed');

        $metadata = is_array($partner->metadata) ? $partner->metadata : [];
        $metadata['allowed_return_urls'] = ['https://crm.example/callback'];
        $partner->forceFill(['metadata' => $metadata])->save();

        $response = $this->postJson('/api/v1/connections', [
            'return_url' => 'https://crm.example/callback',
            'external_ref' => 'cust_1',
        ], $this->connectionCreateHeaders($secret));

        $response->assertCreated()->assertJsonPath('data.onboarding_url', fn ($url) => str_contains($url, '/oauth/meta/start'));
    }

    public function test_crm_return_url_service_rejects_non_https_in_production(): void
    {
        $partner = Partner::factory()->create();
        $metadata = ['allowed_return_urls' => ['http://localhost/cb']];
        $partner->forceFill(['metadata' => $metadata])->save();

        $service = app(CrmReturnUrlService::class);
        $this->assertTrue($service->isAllowed($partner, 'http://localhost/cb'));
    }

    public function test_onboarding_return_signature_uses_integration_signing_secret(): void
    {
        $partner = Partner::factory()->create();
        $metadata = ['integration_signing_secret' => str_repeat('a', 32)];
        $partner->forceFill(['metadata' => $metadata])->save();

        $connection = $partner->whatsappConnections()->create([
            'uuid' => (string) Str::uuid(),
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $signed = OnboardingReturnSignature::forConnection($partner, $connection);
        $this->assertNotNull($signed['signature']);
        $this->assertSame($connection->uuid, $signed['query']['connection_id']);
    }

    public function test_onboarding_return_signature_null_without_integration_secret(): void
    {
        $partner = Partner::factory()->create();
        $connection = $partner->whatsappConnections()->create([
            'uuid' => (string) Str::uuid(),
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $signed = OnboardingReturnSignature::forConnection($partner, $connection);
        $this->assertNull($signed['signature']);
    }

    /**
     * @return array<string, string>
     */
    private function connectionCreateHeaders(string $secret): array
    {
        return array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'crm-return-url-'.uniqid(),
        ]);
    }
}
