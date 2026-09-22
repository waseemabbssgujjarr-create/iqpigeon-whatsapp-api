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
        return (string) config('services.meta.es_config_id', '');
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
