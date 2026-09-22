<?php

namespace App\Services;

use App\Enums\ConnectionStatus;
use App\Models\EmbeddedSignupSession;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use Illuminate\Support\Str;

class ConnectionOnboardingService
{
    public const SESSION_TTL_HOURS = 48;

    public function __construct(
        private readonly PlanEntitlementService $entitlements,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{connection: WhatsappConnection, session: EmbeddedSignupSession, onboarding_url: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function startOnboarding(Partner $partner, ?string $externalRef = null, array $metadata = []): array
    {
        if (! $this->entitlements->canAddConnection($partner)) {
            throw new \RuntimeException('Connection limit reached or partner is not operational.');
        }

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => $externalRef,
            'connection_status' => ConnectionStatus::Pending,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $rawToken = Str::random(64);
        $expiresAt = now()->addHours(self::SESSION_TTL_HOURS);

        $session = EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        $onboardingUrl = url('/oauth/meta/start?token='.urlencode($rawToken));

        return [
            'connection' => $connection,
            'session' => $session,
            'onboarding_url' => $onboardingUrl,
            'expires_at' => $expiresAt,
        ];
    }

    public function findSessionByToken(string $token): ?EmbeddedSignupSession
    {
        $hash = hash('sha256', $token);

        return EmbeddedSignupSession::query()
            ->where('state_token_hash', $hash)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['whatsappConnection', 'partner'])
            ->first();
    }
}
