<?php

namespace App\Services\Meta;

use App\Support\MetaEmbeddedSignupExtras;
use App\Models\EmbeddedSignupSession;
use App\Models\WhatsappConnectionCredential;
use App\Services\ConnectionOnboardingService;
use Illuminate\Support\Facades\Log;

class MetaEmbeddedSignupService
{
    public const FLOW_STANDARD = 'standard';

    public const FLOW_COEXISTENCE = 'coexistence';

    public function __construct(
        private readonly MetaClient $metaClient,
        private readonly ConnectionOnboardingService $onboarding,
        private readonly WhatsappConnectionHydrator $hydrator,
        private readonly WhatsappConnectionValidator $validator,
        private readonly WhatsappCloudApiRegistrationService $registration,
    ) {}

    public function authorizationUrl(EmbeddedSignupSession $session, string $rawToken): string
    {
        $flow = $this->resolveSessionFlow($session);
        $configId = $this->metaClient->embeddedSignupConfigIdForFlow($flow);
        $appId = (string) config('services.meta.app_id');
        $redirectUri = url('/oauth/meta/callback');

        $extrasParam = $flow === self::FLOW_COEXISTENCE
            ? MetaEmbeddedSignupExtras::encodeBusinessAppCoexistence()
            : MetaEmbeddedSignupExtras::encode(MetaEmbeddedSignupExtras::v4ApiAccessOnly());

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $rawToken,
            'response_type' => 'code',
            'config_id' => $configId,
            'extras' => $extrasParam,
        ]);

        $version = ltrim((string) config('services.meta.graph_version', 'v21.0'), '/');

        return 'https://www.facebook.com/'.$version.'/dialog/oauth?'.$query;
    }

    /**
     * @param  array<string, mixed>  $embeddedSignupEvent  Optional WA_EMBEDDED_SIGNUP payload from the JS SDK.
     */
    public function completeCallback(string $code, string $rawToken, array $embeddedSignupEvent = []): EmbeddedSignupSession
    {
        $session = $this->onboarding->findSessionByToken($rawToken);

        if ($session === null) {
            throw new \RuntimeException('Invalid or expired onboarding session.');
        }

        return $this->finalizeSession($session, $code, $embeddedSignupEvent, oauthFlow: 'redirect');
    }

    /**
     * @param  array<string, mixed>  $embeddedSignupEvent
     */
    public function completeForConnection(EmbeddedSignupSession $session, string $code, array $embeddedSignupEvent = []): EmbeddedSignupSession
    {
        if ($session->status === 'completed') {
            return $session->fresh(['whatsappConnection', 'partner']);
        }

        if ($session->status !== 'pending' || $session->expires_at <= now()) {
            throw new \RuntimeException('Onboarding session is not active.');
        }

        return $this->finalizeSession($session, $code, $embeddedSignupEvent, oauthFlow: 'js_sdk');
    }

    /**
     * @param  array<string, mixed>  $embeddedSignupEvent
     * @param  'js_sdk'|'redirect'  $oauthFlow
     */
    private function finalizeSession(
        EmbeddedSignupSession $session,
        string $code,
        array $embeddedSignupEvent,
        string $oauthFlow,
    ): EmbeddedSignupSession {
        $accessToken = $this->metaClient->exchangeOAuthAuthorizationCode($code, $oauthFlow);

        $connection = $session->whatsappConnection;

        $this->persistEmbeddedSignupHints($connection, $embeddedSignupEvent);

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

        $connection = $connection->fresh();
        $connection->loadMissing('credentials');
        if ($connection->credentials !== null) {
            $this->registration->refreshPhoneSnapshot(
                $connection,
                $accessToken,
                $connection->phone_number_id,
            );
        }

        if ($hydrated) {
            $validation = $this->validator->validate($connection->fresh());
            $this->validator->applyValidationResult($connection->fresh(), $validation);

            if ($validation['ok'] ?? false) {
                $this->hydrator->applyOperationalStatusAfterHydration($connection->fresh());
            }
        }

        $session->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        return $session->fresh(['whatsappConnection', 'partner']);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function persistEmbeddedSignupHints(\App\Models\WhatsappConnection $connection, array $event): void
    {
        if ($event === []) {
            return;
        }

        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $metadata['embedded_signup_event'] = [
            'waba_id' => data_get($event, 'data.waba_id') ?? data_get($event, 'waba_id'),
            'phone_number_id' => data_get($event, 'data.phone_number_id') ?? data_get($event, 'phone_number_id'),
            'business_id' => data_get($event, 'data.business_id') ?? data_get($event, 'business_id'),
        ];

        $phoneNumberId = (string) ($metadata['embedded_signup_event']['phone_number_id'] ?? '');
        $wabaId = (string) ($metadata['embedded_signup_event']['waba_id'] ?? '');

        if ($phoneNumberId !== '') {
            $connection->forceFill(['phone_number_id' => $phoneNumberId]);
        }

        if ($wabaId !== '') {
            $connection->forceFill(['waba_id' => $wabaId]);
        }

        $connection->forceFill(['metadata' => $metadata])->save();
    }

    private function resolveSessionFlow(EmbeddedSignupSession $session): string
    {
        $connection = $session->whatsappConnection;
        $metadata = is_array($connection?->metadata) ? $connection->metadata : [];
        $source = (string) ($metadata['onboarding_source'] ?? self::FLOW_STANDARD);

        return $source === self::FLOW_COEXISTENCE ? self::FLOW_COEXISTENCE : self::FLOW_STANDARD;
    }
}
