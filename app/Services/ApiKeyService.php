<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\ApiScope;
use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApiKeyService
{
    public const PREFIX = 'iqp_live_';

    public const SECRET_HEX_LENGTH = 52;

    /**
     * @param  list<string>|Collection<int, string>  $scopeNames
     * @return array{api_key: ApiKey, secret: string}
     */
    public function generate(Partner $partner, string $label = 'Primary', array|Collection $scopeNames = []): array
    {
        $randomHex = bin2hex(random_bytes(self::SECRET_HEX_LENGTH / 2));
        $secret = self::PREFIX.$randomHex;
        $prefix = self::PREFIX.substr($randomHex, 0, 12);

        $apiKey = ApiKey::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'label' => $label,
            'prefix' => $prefix,
            'key_hash' => $this->hashSecret($secret),
            'environment' => 'live',
        ]);

        $this->syncScopes($apiKey, $scopeNames);

        return ['api_key' => $apiKey->load('scopes'), 'secret' => $secret];
    }

    public function hashSecret(string $secret): string
    {
        return password_hash($secret, PASSWORD_DEFAULT);
    }

    public function verify(string $secret, ApiKey $apiKey): bool
    {
        if ($apiKey->isRevoked()) {
            return false;
        }

        if (! str_starts_with($secret, self::PREFIX)) {
            return false;
        }

        return password_verify($secret, $apiKey->key_hash);
    }

    public function revoke(ApiKey $apiKey): ApiKey
    {
        $apiKey->forceFill(['revoked_at' => now()])->save();

        return $apiKey->refresh();
    }

    public function touchLastUsed(ApiKey $apiKey): void
    {
        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    public function findBySecret(string $secret): ?ApiKey
    {
        if (! str_starts_with($secret, self::PREFIX)) {
            return null;
        }

        $suffix = substr($secret, strlen(self::PREFIX));
        if (! preg_match('/^([a-f0-9]{12})/', $suffix, $matches)) {
            return null;
        }

        $lookupPrefix = self::PREFIX.$matches[1];

        $candidates = ApiKey::query()
            ->where('prefix', $lookupPrefix)
            ->whereNull('revoked_at')
            ->get();

        foreach ($candidates as $apiKey) {
            if ($this->verify($secret, $apiKey)) {
                return $apiKey;
            }
        }

        return null;
    }

    /**
     * @param  list<string>|Collection<int, string>  $scopeNames
     */
    public function syncScopes(ApiKey $apiKey, array|Collection $scopeNames): void
    {
        $names = $scopeNames instanceof Collection ? $scopeNames->all() : $scopeNames;

        if ($names === []) {
            return;
        }

        $scopeIds = ApiScope::query()->whereIn('name', $names)->pluck('id');
        $apiKey->scopes()->sync($scopeIds);
    }
}
