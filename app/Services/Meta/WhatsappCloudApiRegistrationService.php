<?php

namespace App\Services\Meta;

use App\Enums\ConnectionStatus;
use App\Models\WhatsappConnection;
use Illuminate\Support\Facades\Log;

/**
 * Meta Cloud API phone number registration (POST /{phone_number_id}/register).
 *
 * Registration is confirmed only after a successful Graph register response —
 * never inferred from phone_number_id alone.
 */
class WhatsappCloudApiRegistrationService
{
    public function __construct(
        private readonly MetaClient $metaClient,
    ) {}

    public function isRegisteredForSending(WhatsappConnection $connection): bool
    {
        $registeredAt = data_get($connection->metadata, 'cloud_api_registered_at');

        return is_string($registeredAt) && $registeredAt !== '';
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function registerWithPin(WhatsappConnection $connection, string $pin): array
    {
        $connection->loadMissing('credentials');

        $phoneNumberId = $connection->phone_number_id ?? $connection->credentials?->phone_number_id;
        $accessToken = $connection->credentials?->access_token;

        if ($phoneNumberId === null || $phoneNumberId === '') {
            return ['ok' => false, 'error' => 'Phone number ID is not configured on this connection.'];
        }

        if (! is_string($accessToken) || $accessToken === '') {
            return ['ok' => false, 'error' => 'Meta credentials are missing for this connection.'];
        }

        $response = $this->metaClient->graph(
            'POST',
            $phoneNumberId.'/register',
            body: [
                'messaging_product' => 'whatsapp',
                'pin' => $pin,
            ],
            accessToken: $accessToken,
        );

        if ($response->failed()) {
            $code = (string) data_get($response->json(), 'error.code', '');
            $message = (string) data_get($response->json(), 'error.message', 'Registration failed.');

            Log::warning('meta.phone.register_failed', [
                'connection_id' => $connection->id,
                'http_status' => $response->status(),
                'provider_code' => $code !== '' ? $code : null,
            ]);

            return [
                'ok' => false,
                'error' => $code !== '' ? 'Meta error '.$code.': '.$message : $message,
            ];
        }

        $success = data_get($response->json(), 'success');

        if ($success !== true && $success !== 'true') {
            return ['ok' => false, 'error' => 'Meta did not confirm phone number registration.'];
        }

        $this->markRegistered($connection, confirmedVia: 'meta_register_api');
        $this->refreshPhoneSnapshot($connection, $accessToken, (string) $phoneNumberId);

        return ['ok' => true];
    }

    public function markRegistered(WhatsappConnection $connection, ?string $confirmedVia = null): void
    {
        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $metadata['cloud_api_registered_at'] = now()->toIso8601String();
        $metadata['cloud_api_register_confirmed'] = true;
        if ($confirmedVia !== null && $confirmedVia !== '') {
            $metadata['cloud_api_register_source'] = $confirmedVia;
        }
        unset($metadata['cloud_api_registration_required']);

        $connection->forceFill([
            'metadata' => $metadata,
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => $connection->connected_at ?? now(),
        ])->save();
    }

    public function markRegistrationRequired(WhatsappConnection $connection): void
    {
        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $metadata['cloud_api_registration_required'] = true;
        unset($metadata['cloud_api_registered_at']);

        $connection->forceFill([
            'metadata' => $metadata,
            'connection_status' => ConnectionStatus::Pending,
            'connected_at' => null,
        ])->save();
    }

    /**
     * After hydration: Pending until Cloud API register succeeds.
     */
    public function applyOperationalStatusAfterHydration(WhatsappConnection $connection): void
    {
        $connection->refresh();

        $hasPhone = $connection->phone_number_id !== null && $connection->phone_number_id !== '';

        if (! $hasPhone) {
            $connection->forceFill([
                'connection_status' => ConnectionStatus::Pending,
                'connected_at' => null,
            ])->save();

            return;
        }

        if ($this->isRegisteredForSending($connection)) {
            $connection->forceFill([
                'connection_status' => ConnectionStatus::Active,
                'connected_at' => $connection->connected_at ?? now(),
            ])->save();

            return;
        }

        $this->markRegistrationRequired($connection);
    }

    /**
     * Demote Active connections that have phone_number_id but no verified registration.
     */
    public function reconcileRegistrationStatus(WhatsappConnection $connection): bool
    {
        if ($connection->connection_status !== ConnectionStatus::Active) {
            return false;
        }

        if ($this->isRegisteredForSending($connection)) {
            return false;
        }

        if ($connection->phone_number_id === null || $connection->phone_number_id === '') {
            return false;
        }

        $this->markRegistrationRequired($connection);

        Log::warning('whatsapp.connection.demoted_unregistered_active', [
            'connection_id' => $connection->id,
            'connection_uuid' => $connection->uuid,
        ]);

        return true;
    }

    public function refreshPhoneSnapshot(WhatsappConnection $connection, string $accessToken, ?string $phoneNumberId = null): void
    {
        $phoneNumberId ??= $connection->phone_number_id;

        if ($phoneNumberId === null || $phoneNumberId === '') {
            return;
        }

        $response = $this->metaClient->graph('GET', $phoneNumberId, [
            'fields' => 'display_phone_number,verified_name,platform_type,code_verification_status,quality_rating',
        ], accessToken: $accessToken);

        if ($response->failed()) {
            return;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return;
        }

        $safe = [
            'display_phone_number' => $data['display_phone_number'] ?? null,
            'verified_name' => $data['verified_name'] ?? null,
            'platform_type' => $data['platform_type'] ?? null,
            'code_verification_status' => $data['code_verification_status'] ?? null,
            'quality_rating' => $data['quality_rating'] ?? null,
        ];

        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $metadata['meta_phone_snapshot'] = $safe;
        $metadata['meta_phone_snapshot_at'] = now()->toIso8601String();

        $connection->forceFill(['metadata' => $metadata])->save();
    }
}
