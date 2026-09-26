<?php

namespace Tests\Feature\Meta;

use App\Models\EmbeddedSignupSession;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetaEmbeddedSignupAuthorizationUrlTest extends TestCase
{
    public function test_standard_oauth_url_uses_v4_api_access_only_extras(): void
    {
        config([
            'services.meta.app_id' => '552479924130015',
            'services.meta.es_config_id' => '881059205043199',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => 'pending',
            'metadata' => ['onboarding_source' => 'standard'],
        ]);

        $session = EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', 'token'),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $url = app(MetaEmbeddedSignupService::class)->authorizationUrl($session, 'token');

        $this->assertStringContainsString('api_access_only', $url);
        $this->assertStringContainsString('version', $url);
        $this->assertStringNotContainsString('featureType', $url);
        $this->assertStringNotContainsString('sessionInfoVersion', $url);
    }

    public function test_coexistence_oauth_url_uses_business_app_extras_not_v4_only(): void
    {
        config([
            'services.meta.app_id' => '552479924130015',
            'services.meta.es_config_id' => '1647730086942089',
            'services.meta.es_config_id_coexistence' => '97624893834457',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => 'pending',
            'metadata' => ['onboarding_source' => 'coexistence'],
        ]);

        $session = EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', 'token'),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $url = app(MetaEmbeddedSignupService::class)->authorizationUrl($session, 'token');

        $this->assertStringContainsString('97624893834457', $url);
        $this->assertStringContainsString('whatsapp_business_app_onboarding', $url);
        $this->assertStringContainsString('sessionInfoVersion', $url);
        $this->assertStringNotContainsString('"version"%3A%22v4%22', $url);
        $this->assertStringNotContainsString('api_access_only', $url);
    }
}
