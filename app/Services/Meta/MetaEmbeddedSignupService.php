<?php

namespace App\Services\Meta;

use App\Models\EmbeddedSignupSession;
use App\Models\WhatsappConnectionCredential;
use App\Services\ConnectionOnboardingService;
use Illuminate\Support\Facades\Log;

class MetaEmbeddedSignupService
{
    public function __construct(
        private readonly MetaClient $metaClient,
        private readonly ConnectionOnboardingService $onboarding,
        private readonly WhatsappConnectionHydrator $hydrator,
    ) {}

    public function authorizationUrl(EmbeddedSignupSession $session, string $rawToken): string
    {
        $configId = $this->metaClient->embeddedSignupConfigId();
        $appId = (string) config('services.meta.app_id');
        $redirectUri = url('/oauth/meta/callback');

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $rawToken,
            'response_type' => 'code',
            'config_id' => $configId,
        ]);

        $version = ltrim((string) config('services.meta.graph_version', 'v21.0'), '/');

        return 'https://www.facebook.com/'.$version.'/dialog/oauth?'.$query;
    }

    public function completeCallback(string $code, string $rawToken): EmbeddedSignupSession
    {
        $session = $this->onboarding->findSessionByToken($rawToken);

        if ($session === null) {
            throw new \RuntimeException('Invalid or expired onboarding session.');
        }

        $redirectUri = url('/oauth/meta/callback');
        $appId = (string) config('services.meta.app_id');
        $appSecret = (string) config('services.meta.app_secret');

        $tokenResponse = $this->metaClient->graph('GET', 'oauth/access_token', [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if ($tokenResponse->failed()) {
            Log::warning('meta.oauth.token_exchange_failed', ['session_id' => $session->id]);

            throw new \RuntimeException('Failed to exchange Meta authorization code.');
        }

        $accessToken = (string) data_get($tokenResponse->json(), 'access_token', '');

        if ($accessToken === '') {
            throw new \RuntimeException('Meta access token missing from response.');
        }

        $connection = $session->whatsappConnection;

        WhatsappConnectionCredential::query()->updateOrCreate(
            ['whatsapp_connection_id' => $connection->id],
            [
                'access_token' => $accessToken,
                'token_expires_at' => now()->addDays(60),
            ],
        );

        $hydrated = $this->hydrator->hydrateFromAccessToken($connection, $accessToken);

        if (! $hydrated) {
            Log::warning('meta.oauth.connection_not_hydrated', [
                'connection_id' => $connection->id,
                'session_id' => $session->id,
            ]);
        }

        $this->hydrator->applyOperationalStatusAfterHydration($connection->fresh());

        $session->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        return $session->fresh(['whatsappConnection', 'partner']);
    }
}
