<?php

namespace Tests\Unit\Services\Meta;

use App\Services\Meta\MetaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaClientOAuthExchangeTest extends TestCase
{
    public function test_js_sdk_flow_tries_without_redirect_uri_before_callback_uri(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
            'app.url' => 'https://whatsappapi.iqpigeon.com',
        ]);

        $attempt = 0;

        Http::fake([
            'graph.facebook.com/*' => function (Request $request) use (&$attempt) {
                if (! str_contains($request->url(), 'oauth/access_token')) {
                    return Http::response([], 404);
                }

                $attempt++;
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                if ($attempt === 1) {
                    $this->assertArrayNotHasKey('redirect_uri', $query);

                    return Http::response(['error' => ['message' => 'redirect mismatch', 'code' => 100]], 400);
                }

                $this->assertSame(
                    'https://whatsappapi.iqpigeon.com/oauth/meta/callback',
                    $query['redirect_uri'] ?? null,
                );

                return Http::response(['access_token' => 'customer-token'], 200);
            },
        ]);

        $token = app(MetaClient::class)->exchangeOAuthAuthorizationCode('auth-code', 'js_sdk');

        $this->assertSame('customer-token', $token);
        $this->assertSame(2, $attempt);
    }

    public function test_oauth_token_exchange_does_not_send_bearer_header(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        Http::fake([
            'graph.facebook.com/*' => function (Request $request) {
                $this->assertNull($request->header('Authorization'));

                return Http::response(['access_token' => 'customer-token'], 200);
            },
        ]);

        app(MetaClient::class)->exchangeOAuthAuthorizationCode('auth-code', 'js_sdk');
    }
}
