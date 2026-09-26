<?php

namespace Tests\Feature\Meta;

use App\Models\EmbeddedSignupSession;
use App\Models\Partner;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetaEmbeddedSignupTokenExchangeTest extends TestCase
{
    public function test_js_sdk_complete_omits_redirect_uri_on_token_exchange(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::factory()->for($partner)->create();
        $rawToken = Str::random(64);
        $session = EmbeddedSignupSession::query()->create([
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

                return Http::response(['data' => []], 200);
            },
        ]);

        app(MetaEmbeddedSignupService::class)->completeForConnection($session, 'auth-code');
    }

    public function test_redirect_oauth_callback_includes_redirect_uri_on_token_exchange(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
            'app.url' => 'https://whatsappapi.iqpigeon.com',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::factory()->for($partner)->create();
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
                    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                    $this->assertSame(
                        'https://whatsappapi.iqpigeon.com/oauth/meta/callback',
                        $query['redirect_uri'] ?? null,
                    );

                    return Http::response(['access_token' => 'customer-token'], 200);
                }

                return Http::response(['data' => []], 200);
            },
        ]);

        app(MetaEmbeddedSignupService::class)->completeCallback('auth-code', $rawToken);
    }

    public function test_embedded_signup_complete_route_exchanges_without_redirect_uri(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $user = User::factory()->create();
        $partner = Partner::factory()->create(['owner_user_id' => $user->id]);
        $connection = WhatsappConnection::factory()->for($partner)->create([
            'metadata' => ['onboarding_source' => 'coexistence'],
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

        $this->actingAs($user)
            ->post(route('connections.embedded-signup.complete', $connection->uuid), [
                'code' => 'auth-code',
                'session_token' => $rawToken,
            ])
            ->assertRedirect(route('app.connections'));

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), 'oauth/access_token')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ! array_key_exists('redirect_uri', $query);
        });
    }
}
