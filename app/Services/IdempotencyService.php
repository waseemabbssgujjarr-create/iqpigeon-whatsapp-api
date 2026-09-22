<?php

namespace App\Services;

use App\Models\ApiIdempotencyKey;
use App\Models\Partner;
use Illuminate\Http\Request;

class IdempotencyService
{
    public const TTL_HOURS = 24;

    public function hashRequest(Request $request): string
    {
        $body = $request->getContent();
        $canonical = strtoupper($request->method())."\n".$request->path()."\n".$body;

        return hash('sha256', $canonical);
    }

    public function find(Partner $partner, string $idempotencyKey): ?ApiIdempotencyKey
    {
        return ApiIdempotencyKey::query()
            ->where('partner_id', $partner->id)
            ->where('idempotency_key', $idempotencyKey)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function begin(Partner $partner, string $idempotencyKey, Request $request, string $requestHash): ApiIdempotencyKey
    {
        ApiIdempotencyKey::query()
            ->where('partner_id', $partner->id)
            ->where('idempotency_key', $idempotencyKey)
            ->where('expires_at', '<=', now())
            ->delete();

        return ApiIdempotencyKey::query()->create([
            'partner_id' => $partner->id,
            'idempotency_key' => $idempotencyKey,
            'request_method' => strtoupper($request->method()),
            'request_path' => '/'.ltrim($request->path(), '/'),
            'request_hash' => $requestHash,
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    public function complete(Partner $partner, string $idempotencyKey, int $status, ?array $responseBody): void
    {
        ApiIdempotencyKey::query()
            ->where('partner_id', $partner->id)
            ->where('idempotency_key', $idempotencyKey)
            ->update([
                'response_status' => $status,
                'response_body' => $responseBody,
            ]);
    }
}
