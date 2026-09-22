<?php

namespace App\Support;

class WebhookUrlValidator
{
    /**
     * Reject SSRF-prone webhook targets (private networks, localhost, non-http(s)).
     */
    public function isAllowed(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || strlen($url) > 2048) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['https', 'http'], true)) {
            return false;
        }

        if ($scheme === 'http' && ! app()->environment(['local', 'testing'])) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        $blockedHosts = ['localhost', 'metadata.google.internal', 'metadata.google'];
        if ($host === '' || in_array($host, $blockedHosts, true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (str_starts_with($host, '169.254.')) {
                return false;
            }

            return $this->isPublicIp($host);
        }

        if (str_contains($host, '@')) {
            return false;
        }

        return true;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
