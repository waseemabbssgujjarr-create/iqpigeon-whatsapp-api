<?php

namespace Tests\Feature\Meta;

use App\Enums\ConnectionStatus;
use App\Models\EmbeddedSignupSession;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetaEmbeddedSignupHydrationTest extends TestCase
{
    public function test_oauth_callback_hydrates_phone_from_waba_phone_numbers_endpoint(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $rawToken = Str::random(64);
        EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $this->fakeMetaGraphForHydration();

        $session = app(MetaEmbeddedSignupService::class)->completeCallback('auth-code', $rawToken);

        $connection = $session->whatsappConnection->fresh();
        $this->assertSame(ConnectionStatus::Pending, $connection->connection_status);
        $this->assertSame('106540352242922', $connection->phone_number_id);
        $this->assertTrue(data_get($connection->metadata, 'cloud_api_registration_required'));
        $this->assertSame('waba_123', $connection->waba_id);

        $credential = WhatsappConnectionCredential::query()->where('whatsapp_connection_id', $connection->id)->first();
        $this->assertNotNull($credential);
        $this->assertSame('106540352242922', $credential->phone_number_id);
    }

    public function test_oauth_callback_stays_pending_when_meta_returns_no_phone_numbers(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $rawToken = Str::random(64);
        EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'graph.facebook.com/*' => function (Request $request) {
                if (str_contains($request->url(), 'oauth/access_token')) {
                    return Http::response(['access_token' => 'customer-token'], 200);
                }

                if (str_contains($request->url(), 'debug_token')) {
                    return Http::response(['data' => ['granular_scopes' => []]], 200);
                }

                return Http::response(['data' => []], 200);
            },
        ]);

        app(MetaEmbeddedSignupService::class)->completeCallback('auth-code', $rawToken);

        $connection->refresh();
        $this->assertSame(ConnectionStatus::Pending, $connection->connection_status);
        $this->assertNull($connection->phone_number_id);
    }

    private function fakeMetaGraphForHydration(): void
    {
        Http::fake([
            'graph.facebook.com/*' => function (Request $request) {
                if (str_contains($request->url(), 'oauth/access_token')) {
                    return Http::response(['access_token' => 'customer-token'], 200);
                }

                if (str_contains($request->url(), 'debug_token')) {
                    return Http::response([
                        'data' => [
                            'app_id' => 'app-id',
                            'scopes' => ['whatsapp_business_messaging', 'whatsapp_business_management'],
                            'granular_scopes' => [
                                [
                                    'scope' => 'whatsapp_business_management',
                                    'target_ids' => ['waba_123'],
                                ],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($request->url(), 'phone_numbers')) {
                    return Http::response([
                        'data' => [
                            [
                                'id' => '106540352242922',
                                'display_phone_number' => '+1 555-0100',
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($request->url(), '106540352242922')) {
                    return Http::response([
                        'display_phone_number' => '+1 555-0100',
                        'verified_name' => 'Test',
                        'platform_type' => 'CLOUD_API',
                        'code_verification_status' => 'VERIFIED',
                    ], 200);
                }

                return Http::response([], 404);
            },
        ]);
    }
}
