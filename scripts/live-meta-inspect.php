<?php

declare(strict_types=1);

/**
 * One-off read-only Meta inspection (no secrets printed). Run on VPS:
 * php scripts/live-meta-inspect.php f0480962-5010-4c4d-aa9d-dcefb53fb821
 */

use App\Models\WhatsappConnection;
use App\Services\Meta\MetaClient;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$uuid = $argv[1] ?? '';

if ($uuid === '') {
    fwrite(STDERR, "Usage: php scripts/live-meta-inspect.php {connection_uuid}\n");
    exit(1);
}

$connection = WhatsappConnection::query()->where('uuid', $uuid)->with('credentials')->first();

if ($connection === null) {
    echo "error: connection not found\n";
    exit(1);
}

echo 'connection_uuid: '.$connection->uuid."\n";
echo 'stored_waba_id: '.($connection->waba_id ?? '(null)')."\n";
echo 'stored_phone_number_id: '.($connection->phone_number_id ?? '(null)')."\n";

if ($connection->credentials === null) {
    echo "error: no stored credentials\n";
    exit(1);
}

$accessToken = $connection->credentials->access_token;

if (! is_string($accessToken) || $accessToken === '') {
    echo "error: empty access token\n";
    exit(1);
}

$phoneNumberId = (string) ($connection->phone_number_id ?? $connection->credentials->phone_number_id ?? '');

if ($phoneNumberId === '') {
    echo "error: no phone_number_id\n";
    exit(1);
}

/** @var MetaClient $metaClient */
$metaClient = app(MetaClient::class);

echo "--- phone GET ---\n";
$phoneResponse = $metaClient->graph('GET', $phoneNumberId, [
    'fields' => 'display_phone_number,verified_name,platform_type,code_verification_status,quality_rating',
], accessToken: $accessToken);

echo 'http_status: '.$phoneResponse->status()."\n";

if ($phoneResponse->failed()) {
    echo 'provider_error_code: '.(string) data_get($phoneResponse->json(), 'error.code', '')."\n";
    echo 'provider_error_message: '.(string) data_get($phoneResponse->json(), 'error.message', '')."\n";
} else {
    $body = $phoneResponse->json();
    echo 'phone_number_id: '.$phoneNumberId."\n";
    echo 'display_phone_number: '.(string) ($body['display_phone_number'] ?? '')."\n";
    echo 'verified_name: '.(string) ($body['verified_name'] ?? '')."\n";
    echo 'platform_type: '.(string) ($body['platform_type'] ?? '')."\n";
    echo 'code_verification_status: '.(string) ($body['code_verification_status'] ?? '')."\n";
    echo 'quality_rating: '.(string) ($body['quality_rating'] ?? '')."\n";
}

echo "--- debug_token (scopes only) ---\n";
$debugResponse = $metaClient->graph('GET', 'debug_token', [
    'input_token' => $accessToken,
], accessToken: $metaClient->appAccessToken());

echo 'http_status: '.$debugResponse->status()."\n";

if ($debugResponse->failed()) {
    echo 'provider_error_code: '.(string) data_get($debugResponse->json(), 'error.code', '')."\n";
    echo 'provider_error_message: '.(string) data_get($debugResponse->json(), 'error.message', '')."\n";
    exit($phoneResponse->successful() ? 0 : 1);
}

$data = data_get($debugResponse->json(), 'data', []);

if (! is_array($data)) {
    echo "error: debug_token missing data\n";
    exit(1);
}

$expectedAppId = (string) config('services.meta.app_id');
$tokenAppId = (string) ($data['app_id'] ?? '');

echo 'token_app_id: '.($tokenAppId !== '' ? $tokenAppId : '(unknown)')."\n";
echo 'configured_meta_app_id: '.($expectedAppId !== '' ? $expectedAppId : '(not set)')."\n";
echo 'token_app_matches_configured_app: '.($expectedAppId !== '' && $tokenAppId === $expectedAppId ? 'yes' : 'no')."\n";

$scopeNames = [];

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
}

$scopeNames = array_values(array_unique($scopeNames));
sort($scopeNames);

echo 'token_scopes: '.($scopeNames === [] ? '(none reported)' : implode(', ', $scopeNames))."\n";
echo 'has_whatsapp_business_messaging: '.(in_array('whatsapp_business_messaging', $scopeNames, true) ? 'yes' : 'no')."\n";
echo 'has_whatsapp_business_management: '.(in_array('whatsapp_business_management', $scopeNames, true) ? 'yes' : 'no')."\n";

$wabaTargets = [];

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
            $wabaTargets[] = (string) $targetId;
        }
    }
}

$wabaTargets = array_values(array_unique($wabaTargets));
echo 'whatsapp_granular_target_ids: '.($wabaTargets === [] ? '(none)' : implode(', ', $wabaTargets))."\n";

$storedWaba = (string) ($connection->waba_id ?? '');

if ($storedWaba !== '') {
    echo 'stored_waba_in_token_targets: '.(in_array($storedWaba, $wabaTargets, true) ? 'yes' : 'no')."\n";
}

exit($phoneResponse->successful() ? 0 : 1);
