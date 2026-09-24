<?php

namespace App\Services;

use App\Models\Partner;
use Illuminate\Support\Str;

class CrmReturnUrlService
{
    /**
     * @return list<string>
     */
    public function allowedOrigins(Partner $partner): array
    {
        $fromPartner = data_get($partner->metadata, 'allowed_return_urls', []);
        $fromConfig = config('services.iqpigeon.crm_return_url_allowlist', []);

        $merged = array_merge(
            is_array($fromPartner) ? $fromPartner : [],
            is_array($fromConfig) ? $fromConfig : [],
        );

        return array_values(array_unique(array_filter(array_map(
            static fn ($url) => is_string($url) ? rtrim(trim($url), '/') : null,
            $merged,
        ))));
    }

    public function isAllowed(Partner $partner, string $returnUrl): bool
    {
        $returnUrl = rtrim(trim($returnUrl), '/');

        if ($returnUrl === '' || ! filter_var($returnUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($returnUrl, PHP_URL_SCHEME);
        if (! in_array($scheme, ['https', 'http'], true)) {
            return false;
        }

        if ($scheme === 'http' && ! app()->environment('local', 'testing')) {
            return false;
        }

        foreach ($this->allowedOrigins($partner) as $allowed) {
            if ($returnUrl === $allowed || Str::startsWith($returnUrl, $allowed.'/')) {
                return true;
            }
        }

        return false;
    }
}
