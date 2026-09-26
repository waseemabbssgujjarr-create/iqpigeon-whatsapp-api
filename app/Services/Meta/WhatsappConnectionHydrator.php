<?php

namespace App\Services\Meta;

use App\Enums\ConnectionStatus;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use Illuminate\Support\Facades\Log;

class WhatsappConnectionHydrator
{
    public function __construct(
        private readonly MetaClient $metaClient,
        private readonly WhatsappCloudApiRegistrationService $registration,
    ) {}

    /**
     * Re-fetch phone number metadata from Meta using stored encrypted credentials.
     */
    public function rehydrateFromStoredCredentials(WhatsappConnection $connection): bool
    {
        $connection->loadMissing('credentials');

        $credentials = $connection->credentials;

        if ($credentials === null) {
            return false;
        }

        $accessToken = $credentials->access_token;

        if (! is_string($accessToken) || $accessToken === '') {
            return false;
        }

        return $this->hydrateFromAccessToken($connection, $accessToken);
    }

    /**
     * Populate phone_number_id, display_phone_number, and waba_id from Meta Graph.
     */
    public function hydrateFromAccessToken(WhatsappConnection $connection, string $accessToken): bool
    {
        $wabaIds = $this->resolveWabaIds($connection, $accessToken);

        foreach ($wabaIds as $wabaId) {
            $response = $this->metaClient->graph('GET', $wabaId.'/phone_numbers', accessToken: $accessToken);

            if ($response->failed()) {
                continue;
            }

            $phones = data_get($response->json(), 'data', []);

            if (! is_array($phones)) {
                continue;
            }

            $selected = $this->selectPhoneNumberRow($phones, $connection);

            if ($selected === null) {
                continue;
            }

            $phoneNumberId = data_get($selected, 'id');

            if ($phoneNumberId === null || $phoneNumberId === '') {
                continue;
            }

            $phoneNumberIdString = (string) $phoneNumberId;
            WhatsappConnection::releasePhoneNumberId(
                (int) $connection->partner_id,
                $phoneNumberIdString,
                (int) $connection->id,
            );

            $connection->forceFill([
                'phone_number_id' => $phoneNumberIdString,
                'display_phone_number' => data_get($selected, 'display_phone_number'),
                'waba_id' => $wabaId,
            ])->save();

            WhatsappConnectionCredential::query()
                ->where('whatsapp_connection_id', $connection->id)
                ->update([
                    'phone_number_id' => (string) $phoneNumberId,
                    'waba_id' => $wabaId,
                ]);

            return true;
        }

        Log::warning('meta.hydrate.phone_numbers_unavailable', [
            'connection_id' => $connection->id,
            'waba_count' => count($wabaIds),
        ]);

        return false;
    }

    /**
     * Active connections must have a Meta phone number ID. Demote incomplete rows.
     *
     * @return bool True when the record was changed.
     */
    public function reconcileOperationalStatus(WhatsappConnection $connection): bool
    {
        $connection->loadMissing('credentials');

        $phoneOnConnection = $this->normalizedPhoneId($connection->phone_number_id);
        $phoneOnCredential = $this->normalizedPhoneId($connection->credentials?->phone_number_id);

        if ($phoneOnConnection === null && $phoneOnCredential !== null) {
            $connection->forceFill(['phone_number_id' => $phoneOnCredential])->save();
            $phoneOnConnection = $phoneOnCredential;
        }

        $hasPhone = $phoneOnConnection !== null;

        if ($connection->connection_status === ConnectionStatus::Active && ! $hasPhone) {
            $connection->forceFill([
                'connection_status' => ConnectionStatus::Pending,
                'connected_at' => null,
            ])->save();

            Log::warning('whatsapp.connection.demoted_incomplete_active', [
                'connection_id' => $connection->id,
                'connection_uuid' => $connection->uuid,
            ]);

            return true;
        }

        if ($this->registration->reconcileRegistrationStatus($connection)) {
            return true;
        }

        return false;
    }

    /**
     * Pending until phone_number_id exists and Meta Cloud API register has succeeded.
     */
    public function applyOperationalStatusAfterHydration(WhatsappConnection $connection): void
    {
        $this->registration->applyOperationalStatusAfterHydration($connection);
    }

    /**
     * @return list<string>
     */
    private function resolveWabaIds(WhatsappConnection $connection, string $accessToken): array
    {
        $ids = [];

        $debug = $this->metaClient->graph('GET', 'debug_token', [
            'input_token' => $accessToken,
        ], accessToken: $this->metaClient->appAccessToken());

        if (! $debug->failed()) {
            foreach (data_get($debug->json(), 'data.granular_scopes', []) as $scope) {
                if (! is_array($scope)) {
                    continue;
                }

                $scopeName = (string) ($scope['scope'] ?? '');

                if (! str_contains($scopeName, 'whatsapp')) {
                    continue;
                }

                foreach ($scope['target_ids'] ?? [] as $targetId) {
                    if (is_string($targetId) && $targetId !== '') {
                        $ids[] = $targetId;
                    } elseif (is_int($targetId)) {
                        $ids[] = (string) $targetId;
                    }
                }
            }
        }

        foreach ([$connection->waba_id, $connection->credentials?->waba_id] as $fallbackWaba) {
            if (is_string($fallbackWaba) && $fallbackWaba !== '') {
                $ids[] = $fallbackWaba;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, mixed>  $phones
     * @return array<string, mixed>|null
     */
    private function selectPhoneNumberRow(array $phones, WhatsappConnection $connection): ?array
    {
        $normalizedRows = array_values(array_filter($phones, 'is_array'));

        if ($normalizedRows === []) {
            return null;
        }

        $targetDisplay = $connection->display_phone_number;

        if (is_string($targetDisplay) && $targetDisplay !== '') {
            $targetDigits = preg_replace('/\D+/', '', $targetDisplay) ?? '';

            foreach ($normalizedRows as $row) {
                $rowDisplay = (string) data_get($row, 'display_phone_number', '');
                $rowDigits = preg_replace('/\D+/', '', $rowDisplay) ?? '';

                if ($targetDigits !== '' && $rowDigits === $targetDigits) {
                    return $row;
                }
            }
        }

        return $normalizedRows[0];
    }

    private function normalizedPhoneId(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $id = trim((string) $value);

        return $id === '' ? null : $id;
    }
}
