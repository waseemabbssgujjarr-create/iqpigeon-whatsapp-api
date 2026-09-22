<?php

namespace Tests\Feature;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Jobs\ProvisionPartnerJob;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripeWebhookLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function sign(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $v1 = hash_hmac('sha256', $signedPayload, $secret);

        return 't='.$timestamp.',v1='.$v1;
    }

    public function test_checkout_completed_dispatches_provisioning(): void
    {
        Bus::fake([ProvisionPartnerJob::class]);

        $secret = 'test_secret_'.bin2hex(random_bytes(8));
        Config::set('services.stripe.webhook_secret', $secret);

        $partner = Partner::factory()->inactive()->create();

        $payload = json_encode([
            'id' => 'evt_checkout_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test',
                    'client_reference_id' => (string) $partner->uuid,
                    'metadata' => ['partner_id' => (string) $partner->id],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->sign($payload, $secret),
            ],
            $payload,
        )->assertOk();

        Bus::assertDispatched(ProvisionPartnerJob::class);

        $this->assertSame(
            ProvisioningStatus::PaymentConfirmed,
            $partner->fresh()->provisioning_status,
        );
    }

    public function test_checkout_completed_ignored_when_client_reference_mismatch(): void
    {
        Bus::fake([ProvisionPartnerJob::class]);

        $secret = 'test_secret_'.bin2hex(random_bytes(8));
        Config::set('services.stripe.webhook_secret', $secret);

        $partner = Partner::factory()->inactive()->create();

        $payload = json_encode([
            'id' => 'evt_checkout_bad_ref',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_bad',
                    'client_reference_id' => 'wrong-reference',
                    'metadata' => ['partner_id' => (string) $partner->id],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->sign($payload, $secret),
            ],
            $payload,
        )->assertOk();

        Bus::assertNotDispatched(ProvisionPartnerJob::class);
        $this->assertSame(ProvisioningStatus::Registered, $partner->fresh()->provisioning_status);
    }

    public function test_subscription_deleted_suspends_partner(): void
    {
        $secret = 'test_secret_'.bin2hex(random_bytes(8));
        Config::set('services.stripe.webhook_secret', $secret);

        $partner = Partner::factory()->create(['stripe_id' => 'cus_test_1']);
        $plan = Plan::query()->create([
            'slug' => 'test-plan-'.uniqid(),
            'name' => 'Test Plan',
            'price_cents' => 1000,
            'currency' => 'usd',
            'interval' => 'month',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = json_encode([
            'id' => 'evt_sub_del_1',
            'object' => 'event',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_del_test',
                    'customer' => 'cus_test_1',
                    'status' => 'canceled',
                    'metadata' => ['partner_id' => (string) $partner->id, 'plan_id' => (string) $plan->id],
                    'items' => ['data' => [['price' => ['id' => 'price_test'], 'quantity' => 1]]],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->sign($payload, $secret),
            ],
            $payload,
        )->assertOk();

        $partner->refresh();
        $this->assertSame(PartnerStatus::Suspended, $partner->status);
        $this->assertSame(ProvisioningStatus::Cancelled, $partner->provisioning_status);
        $this->assertDatabaseHas('stripe_events', ['stripe_event_id' => 'evt_sub_del_1']);
    }
}
