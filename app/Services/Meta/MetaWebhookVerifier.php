<?php

namespace App\Services\Meta;

use Illuminate\Http\Request;

class MetaWebhookVerifier
{
    public function verifySubscription(Request $request): bool
    {
        $mode = (string) $request->query('hub_mode', '');
        $token = (string) $request->query('hub_verify_token', '');
        $challenge = (string) $request->query('hub_challenge', '');

        if ($mode !== 'subscribe' || $challenge === '') {
            return false;
        }

        $expected = (string) config('services.meta.webhook_verify_token', '');

        return $expected !== '' && hash_equals($expected, $token);
    }

    public function subscriptionChallenge(Request $request): ?string
    {
        return $this->verifySubscription($request)
            ? (string) $request->query('hub_challenge', '')
            : null;
    }

    public function verifySignature(Request $request): bool
    {
        $signatureHeader = (string) $request->header('X-Hub-Signature-256', '');
        $appSecret = (string) config('services.meta.app_secret', '');

        if ($signatureHeader === '' || $appSecret === '') {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $provided = substr($signatureHeader, 7);
        $expected = hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $provided);
    }
}
