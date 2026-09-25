<?php

namespace App\Services\Meta;

use App\Enums\ConnectionStatus;
use App\Models\WhatsappConnection;
use Illuminate\Support\Facades\Log;

/**
 * Post-signup Meta validation — never prints secrets.
 */
class WhatsappConnectionValidator
{
    public function __construct(
        private readonly MetaClient $metaClient,
        private readonly WhatsappCloudApiRegistrationService $registration,
    ) {}

    /**
     * @return array{ok: bool, checks: list<array{key: string, ok: bool, detail: string|null}>}
     */
    public function validate(WhatsappConnection $connection): array
    {
        $connection->loadMissing('credentials');
        $checks = [];

        $credentials = $connection->credentials;
        if ($credentials === null) {
            $checks[] = $this->check('credentials', false, 'No Meta credentials stored.');

            return ['ok' => false, 'checks' => $checks];
        }

        $accessToken = $credentials->access_token;
        if (! is_string($accessToken) || $accessToken === '') {
            $checks[] = $this->check('credentials', false, 'Access token missing.');

            return ['ok' => false, 'checks' => $checks];
        }

        $phoneNumberId = $connection->phone_number_id ?? $credentials->phone_number_id;
        if ($phoneNumberId === null || $phoneNumberId === '') {
            $checks[] = $this->check('phone_number_id', false, 'Phone number ID not hydrated.');

            return ['ok' => false, 'checks' => $checks];
        }

        $expectedAppId = (string) config('services.meta.app_id');
        $debug = $this->metaClient->graph('GET', 'debug_token', [
            'input_token' => $accessToken,
        ], accessToken: $this->metaClient->appAccessToken());

        $tokenOk = $debug->successful();
        $tokenAppId = $tokenOk ? (string) data_get($debug->json(), 'data.app_id', '') : '';
        $checks[] = $this->check(
            'token_valid',
            $tokenOk,
            $tokenOk ? null : 'debug_token failed.',
        );
        $checks[] = $this->check(
            'token_app_id',
            $expectedAppId === '' || $tokenAppId === $expectedAppId,
            $expectedAppId !== '' && $tokenAppId !== '' && $tokenAppId !== $expectedAppId
                ? 'Token was issued for a different Meta app.'
                : null,
        );

        $scopes = $this->collectScopes($debug->json());
        $checks[] = $this->check('scope_messaging', in_array('whatsapp_business_messaging', $scopes, true), null);
        $checks[] = $this->check('scope_management', in_array('whatsapp_business_management', $scopes, true), null);

        $wabaTargets = $this->collectWabaTargets($debug->json());
        $storedWaba = (string) ($connection->waba_id ?? '');
        $checks[] = $this->check(
            'waba_authorized',
            $storedWaba === '' || in_array($storedWaba, $wabaTargets, true),
            $storedWaba !== '' ? 'Stored WABA is not in token granular targets.' : null,
        );

        $phoneResponse = $this->metaClient->graph('GET', (string) $phoneNumberId, [
            'fields' => 'display_phone_number,verified_name,platform_type,code_verification_status,quality_rating,name_status',
        ], accessToken: $accessToken);

        $checks[] = $this->check('phone_metadata', $phoneResponse->successful(), $phoneResponse->successful()
            ? null
            : 'Cannot read phone number metadata from Meta.');

        if ($phoneResponse->successful()) {
            $this->registration->refreshPhoneSnapshot($connection, $accessToken, (string) $phoneNumberId);
            $connection->refresh();
        }

        $ok = collect($checks)->every(fn (array $row) => $row['ok'] === true);

        return ['ok' => $ok, 'checks' => $checks];
    }

    public function applyValidationResult(WhatsappConnection $connection, array $validation): void
    {
        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        $metadata['last_meta_validation_at'] = now()->toIso8601String();
        $metadata['last_meta_validation_ok'] = $validation['ok'] ?? false;
        $metadata['last_meta_validation_checks'] = $validation['checks'] ?? [];

        if ($validation['ok'] ?? false) {
            $connection->forceFill(['metadata' => $metadata])->save();

            return;
        }

        $failed = collect($validation['checks'] ?? [])
            ->filter(fn (array $row) => ($row['ok'] ?? false) === false)
            ->pluck('detail')
            ->filter()
            ->values()
            ->all();

        $metadata['validation_error'] = $failed === [] ? 'Meta validation failed.' : implode(' ', $failed);

        $connection->forceFill([
            'metadata' => $metadata,
            'connection_status' => ConnectionStatus::Error,
            'connected_at' => null,
        ])->save();

        Log::warning('whatsapp.connection.validation_failed', [
            'connection_uuid' => $connection->uuid,
        ]);
    }

    /**
     * @return list<string>
     */
    private function collectScopes(mixed $debugJson): array
    {
        $names = [];
        $data = data_get($debugJson, 'data', []);

        if (! is_array($data)) {
            return [];
        }

        foreach ($data['scopes'] ?? [] as $scope) {
            if (is_string($scope) && $scope !== '') {
                $names[] = $scope;
            }
        }

        foreach ($data['granular_scopes'] ?? [] as $granular) {
            if (is_array($granular) && is_string($granular['scope'] ?? null)) {
                $names[] = $granular['scope'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @return list<string>
     */
    private function collectWabaTargets(mixed $debugJson): array
    {
        $targets = [];
        $data = data_get($debugJson, 'data', []);

        if (! is_array($data)) {
            return [];
        }

        foreach ($data['granular_scopes'] ?? [] as $granular) {
            if (! is_array($granular)) {
                continue;
            }

            $name = (string) ($granular['scope'] ?? '');
            if (! str_contains($name, 'whatsapp')) {
                continue;
            }

            foreach ($granular['target_ids'] ?? [] as $targetId) {
                if (is_string($targetId) || is_int($targetId)) {
                    $targets[] = (string) $targetId;
                }
            }
        }

        return array_values(array_unique($targets));
    }

    /**
     * @return array{key: string, ok: bool, detail: string|null}
     */
    private function check(string $key, bool $ok, ?string $detail): array
    {
        return ['key' => $key, 'ok' => $ok, 'detail' => $detail];
    }
}
