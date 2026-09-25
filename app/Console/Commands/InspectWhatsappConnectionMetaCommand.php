<?php

namespace App\Console\Commands;

use App\Models\WhatsappConnection;
use App\Services\Meta\MetaClient;
use Illuminate\Console\Command;

/**
 * Read-only Meta Graph inspection for a connection (no tokens or secrets printed).
 */
class InspectWhatsappConnectionMetaCommand extends Command
{
    protected $signature = 'whatsapp:inspect-meta {uuid : Connection UUID}';

    protected $description = 'Safe live Meta phone + token scope inspection (no secrets printed).';

    public function handle(MetaClient $metaClient): int
    {
        $connection = WhatsappConnection::query()
            ->where('uuid', $this->argument('uuid'))
            ->with('credentials')
            ->first();

        if ($connection === null) {
            $this->error('Connection not found.');

            return self::FAILURE;
        }

        $this->line('connection_uuid: '.$connection->uuid);
        $this->line('stored_waba_id: '.($connection->waba_id ?? '(null)'));
        $this->line('stored_phone_number_id: '.($connection->phone_number_id ?? '(null)'));
        $this->line('stored_display_phone_number: '.($connection->display_phone_number ?? '(null)'));

        if ($connection->credentials === null) {
            $this->error('No stored Meta credentials.');

            return self::FAILURE;
        }

        $accessToken = $connection->credentials->access_token;

        if (! is_string($accessToken) || $accessToken === '') {
            $this->error('Stored access token is empty.');

            return self::FAILURE;
        }

        $phoneNumberId = $connection->phone_number_id ?? $connection->credentials->phone_number_id;

        if ($phoneNumberId === null || $phoneNumberId === '') {
            $this->error('No phone_number_id on connection or credentials.');

            return self::FAILURE;
        }

        $expectedAppId = (string) config('services.meta.app_id');

        $this->line('--- phone GET ---');
        $phoneResponse = $metaClient->graph('GET', (string) $phoneNumberId, [
            'fields' => 'display_phone_number,verified_name,platform_type,code_verification_status,quality_rating',
        ], accessToken: $accessToken);

        $this->line('http_status: '.$phoneResponse->status());

        if ($phoneResponse->failed()) {
            $this->line('provider_error_code: '.(string) data_get($phoneResponse->json(), 'error.code', ''));
            $this->line('provider_error_message: '.(string) data_get($phoneResponse->json(), 'error.message', ''));
        } else {
            $body = $phoneResponse->json();
            $this->line('phone_number_id: '.$phoneNumberId);
            $this->line('display_phone_number: '.(string) ($body['display_phone_number'] ?? ''));
            $this->line('verified_name: '.(string) ($body['verified_name'] ?? ''));
            $this->line('platform_type: '.(string) ($body['platform_type'] ?? ''));
            $this->line('code_verification_status: '.(string) ($body['code_verification_status'] ?? ''));
            $this->line('quality_rating: '.(string) ($body['quality_rating'] ?? ''));
        }

        $this->line('--- debug_token (scopes only) ---');

        $debugResponse = $metaClient->graph('GET', 'debug_token', [
            'input_token' => $accessToken,
        ], accessToken: $metaClient->appAccessToken());

        $this->line('http_status: '.$debugResponse->status());

        if ($debugResponse->failed()) {
            $this->line('provider_error_code: '.(string) data_get($debugResponse->json(), 'error.code', ''));
            $this->line('provider_error_message: '.(string) data_get($debugResponse->json(), 'error.message', ''));

            return $phoneResponse->successful() ? self::SUCCESS : self::FAILURE;
        }

        $data = data_get($debugResponse->json(), 'data', []);

        if (! is_array($data)) {
            $this->warn('debug_token returned no data object.');

            return self::FAILURE;
        }

        $tokenAppId = (string) ($data['app_id'] ?? '');
        $this->line('token_app_id: '.($tokenAppId !== '' ? $tokenAppId : '(unknown)'));
        $this->line('configured_meta_app_id: '.($expectedAppId !== '' ? $expectedAppId : '(not set)'));
        $this->line('token_app_matches_configured_app: '.($expectedAppId !== '' && $tokenAppId === $expectedAppId ? 'yes' : 'no'));

        $scopeNames = [];
        $wabaTargets = [];

        foreach ($data['scopes'] ?? [] as $scope) {
            if (is_string($scope) && $scope !== '') {
                $scopeNames[] = $scope;
            }
        }

        foreach ($data['granular_scopes'] ?? [] as $granular) {
            if (! is_array($granular)) {
                continue;
            }

            $name = (string) ($granular['scope'] ?? '');

            if ($name !== '') {
                $scopeNames[] = $name;
            }

            if (str_contains($name, 'whatsapp')) {
                foreach ($granular['target_ids'] ?? [] as $targetId) {
                    if (is_string($targetId) || is_int($targetId)) {
                        $wabaTargets[] = (string) $targetId;
                    }
                }
            }
        }

        $scopeNames = array_values(array_unique($scopeNames));
        sort($scopeNames);

        $this->line('token_scopes: '.($scopeNames === [] ? '(none reported)' : implode(', ', $scopeNames)));
        $this->line('has_whatsapp_business_messaging: '.(in_array('whatsapp_business_messaging', $scopeNames, true) ? 'yes' : 'no'));
        $this->line('has_whatsapp_business_management: '.(in_array('whatsapp_business_management', $scopeNames, true) ? 'yes' : 'no'));

        $wabaTargets = array_values(array_unique($wabaTargets));
        $this->line('whatsapp_granular_target_ids: '.($wabaTargets === [] ? '(none)' : implode(', ', $wabaTargets)));

        $storedWaba = (string) ($connection->waba_id ?? '');
        if ($storedWaba !== '') {
            $this->line('stored_waba_in_token_targets: '.(in_array($storedWaba, $wabaTargets, true) ? 'yes' : 'no'));
        }

        return $phoneResponse->successful() ? self::SUCCESS : self::FAILURE;
    }
}
