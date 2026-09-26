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
    public function startOnboarding(
        Partner $partner,
        ?string $externalRef = null,
        array $metadata = [],
        string $onboardingSource = 'standard',
    ): array {
        if (! $this->entitlements->canAddConnection($partner)) {
            throw new \RuntimeException('Connection limit reached or partner is not operational.');
        }

        $returnUrl = isset($metadata['return_url']) && is_string($metadata['return_url'])
            ? $metadata['return_url']
            : null;
        unset($metadata['return_url']);

        $metadata['onboarding_source'] = $onboardingSource === 'coexistence' ? 'coexistence' : 'standard';

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => $externalRef,
            'connection_status' => ConnectionStatus::Pending,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        $rawToken = Str::random(64);
        $expiresAt = now()->addHours(self::SESSION_TTL_HOURS);

        $sessionMeta = $returnUrl !== null ? ['return_url' => $returnUrl] : null;

        $session = EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'metadata' => $sessionMeta,
        ]);

        $onboardingUrl = url('/oauth/meta/start?token='.urlencode($rawToken));

        return [
            'connection' => $connection,
            'session' => $session,
            'session_token' => $rawToken,
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

    /**
     * @return array{onboarding_url: string, session_token: string, expires_at: \Illuminate\Support\Carbon}
     */
    public function resumeOnboarding(Partner $partner, WhatsappConnection $connection): array
    {
        if ($connection->partner_id !== $partner->id) {
            throw new \InvalidArgumentException('Connection does not belong to this partner.');
        }

        if ($connection->connection_status !== ConnectionStatus::Pending) {
            throw new \RuntimeException('Only pending connections can resume setup.');
        }

        $previousReturnUrl = EmbeddedSignupSession::query()
            ->where('whatsapp_connection_id', $connection->id)
            ->whereNotNull('metadata')
            ->orderByDesc('id')
            ->value('metadata');

        EmbeddedSignupSession::query()
            ->where('whatsapp_connection_id', $connection->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $rawToken = Str::random(64);
        $expiresAt = now()->addHours(self::SESSION_TTL_HOURS);

        $sessionMeta = is_array($previousReturnUrl) && isset($previousReturnUrl['return_url'])
            ? ['return_url' => $previousReturnUrl['return_url']]
            : null;

        EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'metadata' => $sessionMeta,
        ]);

        return [
            'onboarding_url' => url('/oauth/meta/start?token='.urlencode($rawToken)),
            'session_token' => $rawToken,
            'expires_at' => $expiresAt,
        ];
    }
}
