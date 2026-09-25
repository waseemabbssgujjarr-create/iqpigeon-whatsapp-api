<?php

namespace App\Support;

final class MetaEmbeddedSignupExtras
{
    /**
     * v4 default: coexistence + standard paths (Meta detects Business App numbers automatically).
     *
     * @return array<string, mixed>
     */
    public static function v4Default(): array
    {
        return [
            'version' => 'v4',
        ];
    }

    /**
     * v4 API-only onboarding (standard Cloud API / new number button).
     *
     * @return array<string, mixed>
     */
    public static function v4ApiAccessOnly(): array
    {
        return [
            'version' => 'v4',
            'features' => ['api_access_only'],
        ];
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    public static function assertV4WithoutLegacyKeys(array $extras): void
    {
        if (array_key_exists('featureType', $extras) || array_key_exists('sessionInfoVersion', $extras)) {
            throw new \InvalidArgumentException('Embedded Signup v4 must not include featureType or sessionInfoVersion.');
        }

        if (($extras['version'] ?? '') !== 'v4') {
            throw new \InvalidArgumentException('Embedded Signup extras must set version to v4.');
        }
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    public static function encode(array $extras): string
    {
        self::assertV4WithoutLegacyKeys($extras);

        return json_encode($extras, JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
