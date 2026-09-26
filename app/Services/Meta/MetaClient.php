<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaClient
{
    public function graph(string $method, string $path, array $query = [], array $body = [], ?string $accessToken = null): Response
    {
        $token = $accessToken ?? $this->appAccessToken();
        $url = rtrim($this->graphBaseUrl(), '/').'/'.ltrim($path, '/');

        $request = $this->baseRequest()->withToken($token);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $query),
            'POST' => $request->asJson()->post($url, $body !== [] ? $body : $query),
            'DELETE' => $request->delete($url, $query),
            default => throw new RuntimeException("Unsupported Meta Graph method [{$method}]."),
        };

        if ($response->failed()) {
            Log::warning('meta.graph.request_failed', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $response->status(),
                'error_type' => data_get($response->json(), 'error.type'),
                'error_code' => data_get($response->json(), 'error.code'),
            ]);
        }

        return $response;
    }

    public function appAccessToken(): string
    {
        $appId = (string) config('services.meta.app_id');
        $appSecret = (string) config('services.meta.app_secret');

        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('Meta app credentials are not configured.');
        }

        return $appId.'|'.$appSecret;
    }

    public function graphBaseUrl(): string
    {
        $version = (string) config('services.meta.graph_version', 'v21.0');

        return 'https://graph.facebook.com/'.$version;
    }

    public function embeddedSignupConfigId(): string
    {
        return $this->embeddedSignupConfigIdForFlow(MetaEmbeddedSignupService::FLOW_STANDARD);
    }

    public function embeddedSignupConfigIdForFlow(string $flow): string
    {
        if ($flow === MetaEmbeddedSignupService::FLOW_COEXISTENCE) {
            $coexistence = (string) config('services.meta.es_config_id_coexistence', '');

            if ($coexistence !== '') {
                return $coexistence;
            }
        }

        return (string) config('services.meta.es_config_id', '');
    }

    /**
     * Exchange an Embedded Signup / OAuth authorization code for a customer access token.
     *
     * @param  'js_sdk'|'redirect'  $flow
     */
    public function exchangeOAuthAuthorizationCode(string $code, string $flow = 'js_sdk'): string
    {
        $appId = (string) config('services.meta.app_id');
        $appSecret = (string) config('services.meta.app_secret');

        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('Meta app credentials are not configured.');
        }

        $redirectUri = url('/oauth/meta/callback');
        $redirectAttempts = $flow === 'redirect'
            ? [true, false]
            : [false, true];

        $lastResponse = null;

        foreach ($redirectAttempts as $useRedirectUri) {
            $payload = [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ];

            if ($useRedirectUri) {
                $payload['redirect_uri'] = $redirectUri;
            }

            $lastResponse = $this->requestOAuthAccessToken($payload);

            if ($lastResponse->successful()) {
                $accessToken = (string) data_get($lastResponse->json(), 'access_token', '');

                if ($accessToken !== '') {
                    Log::info('meta.oauth.token_exchange_ok', [
                        'flow' => $flow,
                        'uses_redirect_uri' => $useRedirectUri,
                    ]);

                    return $accessToken;
                }
            }
        }

        Log::warning('meta.oauth.token_exchange_failed', [
            'flow' => $flow,
            'status' => $lastResponse?->status(),
            'error_message' => data_get($lastResponse?->json(), 'error.message'),
            'error_subcode' => data_get($lastResponse?->json(), 'error.error_subcode'),
        ]);

        throw new RuntimeException('Failed to exchange Meta authorization code.');
    }

    /**
     * OAuth token exchange must not send a Graph Bearer token (only client_id + secret + code).
     *
     * @param  array<string, string>  $payload
     */
    private function requestOAuthAccessToken(array $payload): Response
    {
        $url = rtrim($this->graphBaseUrl(), '/').'/oauth/access_token';

        $request = $this->baseRequest();

        $response = $request->get($url, $payload);

        if ($response->successful()) {
            return $response;
        }

        $postPayload = array_merge($payload, ['grant_type' => 'authorization_code']);

        return $request->asForm()->post($url, $postPayload);
    }

    private function baseRequest(): PendingRequest
    {
        $timeout = (int) config('services.meta.timeout', 30);

        return Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => (string) config('services.meta.user_agent', 'IQPigeon-WhatsApp-API'),
            ]);
    }
}
