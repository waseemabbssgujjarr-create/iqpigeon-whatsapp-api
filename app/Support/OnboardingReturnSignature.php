<?php

namespace App\Support;

use App\Models\Partner;
use App\Models\WhatsappConnection;

class OnboardingReturnSignature
{
    public static function signingSecret(Partner $partner): ?string
    {
        $fromMeta = data_get($partner->metadata, 'integration_signing_secret');

        return is_string($fromMeta) && strlen($fromMeta) >= 16 ? $fromMeta : null;
    }

    /**
     * @return array{query: array<string, string>, signature: string|null}
     */
    public static function forConnection(Partner $partner, WhatsappConnection $connection, string $status = 'connected'): array
    {
        $query = array_filter([
            'connection_id' => $connection->uuid,
            'status' => $status,
            'external_ref' => $connection->external_ref,
            'timestamp' => (string) now()->timestamp,
        ], static fn ($v) => $v !== null && $v !== '');

        ksort($query);

        $secret = self::signingSecret($partner);
        $signature = null;

        if ($secret !== null) {
            $signature = hash_hmac('sha256', http_build_query($query), $secret);
        }

        return [
            'query' => $query,
            'signature' => $signature,
        ];
    }

    public static function appendToUrl(string $baseUrl, Partner $partner, WhatsappConnection $connection, string $status = 'connected'): string
    {
        $signed = self::forConnection($partner, $connection, $status);
        $query = $signed['query'];
        if ($signed['signature'] !== null) {
            $query['signature'] = $signed['signature'];
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query($query);
    }
}
