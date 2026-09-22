<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use CreatesApiPartners;

    public function test_reused_idempotency_key_with_different_body_returns_409(): void
    {
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.write']);

        $headers = array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'idem-test-1',
        ]);

        $first = $this->postJson('/api/v1/connections', ['external_ref' => 'a'], $headers);
        $first->assertCreated();

        $second = $this->postJson('/api/v1/connections', ['external_ref' => 'b'], $headers);

        $second->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_key_mismatch');
    }

    public function test_reused_idempotency_key_with_same_body_replays_response(): void
    {
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.write']);

        $headers = array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'idem-test-2',
        ]);

        $payload = ['external_ref' => 'same-ref'];

        $first = $this->postJson('/api/v1/connections', $payload, $headers);
        $first->assertCreated();

        $second = $this->postJson('/api/v1/connections', $payload, $headers);

        $second->assertCreated()
            ->assertJsonPath('data.connection.id', $first->json('data.connection.id'));
    }
}
