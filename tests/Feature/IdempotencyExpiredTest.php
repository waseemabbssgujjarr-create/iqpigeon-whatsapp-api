<?php

namespace Tests\Feature;

use App\Models\ApiIdempotencyKey;
use App\Services\IdempotencyService;
use Illuminate\Http\Request;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class IdempotencyExpiredTest extends TestCase
{
    use CreatesApiPartners;

    public function test_expired_idempotency_record_allows_new_request_with_same_key(): void
    {
        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.write']);

        $idempotencyKey = 'expired-idem-key';
        $request = Request::create('/api/v1/connections', 'POST', ['external_ref' => 'old-ref']);
        $requestHash = app(IdempotencyService::class)->hashRequest($request);

        ApiIdempotencyKey::query()->create([
            'partner_id' => $partner->id,
            'idempotency_key' => $idempotencyKey,
            'request_method' => 'POST',
            'request_path' => '/api/v1/connections',
            'request_hash' => $requestHash,
            'response_status' => 201,
            'response_body' => ['success' => true, 'data' => ['connection' => ['id' => 'old']]],
            'expires_at' => now()->subHour(),
        ]);

        $headers = array_merge($this->withBearer($secret), [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $this->postJson('/api/v1/connections', ['external_ref' => 'new-ref-after-expiry'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.connection.external_ref', 'new-ref-after-expiry');
    }
}
