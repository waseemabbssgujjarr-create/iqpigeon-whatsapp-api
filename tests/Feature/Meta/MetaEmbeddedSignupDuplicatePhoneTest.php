<?php

namespace Tests\Feature\Meta;

use App\Enums\ConnectionStatus;
use App\Models\EmbeddedSignupSession;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\WhatsappConnectionHydrator;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetaEmbeddedSignupDuplicatePhoneTest extends TestCase
{
    public function test_complete_releases_phone_number_id_from_disconnected_sibling(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();

        $previous = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'waba_id' => '757872340505399',
            'connection_status' => ConnectionStatus::Disconnected,
            'metadata' => ['onboarding_source' => 'coexistence'],
        ]);

        $draft = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
            'metadata' => ['onboarding_source' => 'coexistence'],
        ]);

        $rawToken = Str::random(64);
        $session = EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $draft->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'graph.facebook.com/*' => function ($request) {
                if (str_contains($request->url(), 'oauth/access_token')) {
                    return Http::response(['access_token' => 'customer-token'], 200);
                }

                if (str_contains($request->url(), 'debug_token')) {
                    return Http::response([
                        'data' => [
                            'granular_scopes' => [
                                ['scope' => 'whatsapp_business_management', 'target_ids' => ['757872340505399']],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($request->url(), 'phone_numbers')) {
                    return Http::response([
                        'data' => [
                            ['id' => '758204954052103', 'display_phone_number' => '+92 311 4522101'],
                        ],
                    ], 200);
                }

                if (str_contains($request->url(), '758204954052103')) {
                    return Http::response([
                        'display_phone_number' => '+92 311 4522101',
                        'platform_type' => 'CLOUD_API',
                        'code_verification_status' => 'VERIFIED',
                    ], 200);
                }

                return Http::response(['data' => []], 200);
            },
        ]);

        app(MetaEmbeddedSignupService::class)->completeForConnection(
            $session,
            'auth-code',
            [
                'data' => [
                    'waba_id' => '757872340505399',
                    'phone_number_id' => '758204954052103',
                    'business_id' => '485696854464727',
                ],
            ],
        );

        $previous->refresh();
        $draft->refresh();

        $this->assertNull($previous->phone_number_id);
        $this->assertSame('758204954052103', $draft->phone_number_id);
    }

    public function test_dashboard_reconcile_does_not_restore_phone_on_disconnected_row(): void
    {
        $partner = Partner::factory()->create();

        $previous = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Disconnected,
            'phone_number_id' => null,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $previous->id,
            'access_token' => 'test-token',
            'phone_number_id' => '758204954052103',
        ]);

        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'connection_status' => ConnectionStatus::Active,
            'metadata' => ['cloud_api_registered_at' => now()->toIso8601String()],
        ]);

        app(WhatsappConnectionHydrator::class)->reconcileOperationalStatus($previous->fresh(['credentials']));

        $previous->refresh();
        $this->assertNull($previous->phone_number_id);
    }
}
