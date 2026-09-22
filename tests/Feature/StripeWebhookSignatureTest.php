<?php

namespace Tests\Feature;

use App\Models\StripeEvent;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripeWebhookSignatureTest extends TestCase
{
    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['id' => 'evt_test', 'type' => 'ping'], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(400);
    }

    public function test_stripe_webhook_accepts_valid_signature_and_is_idempotent(): void
    {
        $secret = 'test_webhook_secret_'.bin2hex(random_bytes(16));
        Config::set('services.stripe.webhook_secret', $secret);

        $payload = json_encode([
            'id' => 'evt_test_valid_1',
            'object' => 'event',
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_test']],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $v1 = hash_hmac('sha256', $signedPayload, $secret);
        $signature = 't='.$timestamp.',v1='.$v1;

        $response = $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertOk();

        $this->assertDatabaseHas('stripe_events', [
            'stripe_event_id' => 'evt_test_valid_1',
        ]);

        $responseAgain = $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $responseAgain->assertOk();
        $this->assertSame(1, StripeEvent::query()->where('stripe_event_id', 'evt_test_valid_1')->count());
    }
}
