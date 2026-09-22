<?php

namespace App\Services;

class WebhookSignatureService
{
    public function sign(string $secret, string $timestamp, string $rawBody): string
    {
        $payload = $timestamp.'.'.$rawBody;

        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $secret, string $timestamp, string $rawBody, string $providedSignature): bool
    {
        $expected = $this->sign($secret, $timestamp, $rawBody);

        return hash_equals($expected, $this->normalizeSignature($providedSignature));
    }

    /**
     * @return array<string, string>
     */
    public function headers(string $secret, string $rawBody, string $requestId, string $eventId): array
    {
        $timestamp = (string) time();
        $signature = $this->sign($secret, $timestamp, $rawBody);

        return [
            'X-IQP-Signature' => $signature,
            'X-IQP-Timestamp' => $timestamp,
            'X-IQP-Request-Id' => $requestId,
            'X-IQP-Event-Id' => $eventId,
        ];
    }

    private function normalizeSignature(string $signature): string
    {
        if (str_contains($signature, '=')) {
            $parts = explode('=', $signature, 2);

            return $parts[1] ?? $signature;
        }

        return $signature;
    }
}
