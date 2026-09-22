<?php

namespace Tests\Feature\Webhooks;

use App\Enums\WebhookDeliveryStatus;
use App\Exceptions\PartnerWebhookRetryableException;
use App\Jobs\DeliverPartnerWebhookJob;
use App\Models\Partner;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\PartnerWebhookDeliveryExecutor;
use Database\Seeders\ApiScopeSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class PartnerWebhookDeliveryTest extends TestCase
{
    use CreatesApiPartners;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('webhooks.delivery.max_attempts', 5);
        Config::set('webhooks.delivery.backoff_seconds', [1, 2, 3, 4]);
        Config::set('webhooks.delivery.timeout_seconds', 5);
    }

    public function test_delivery_marks_success_when_partner_endpoint_returns_200(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('ok', 200),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/inbound', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        $this->runAttempt($delivery, 1);

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::Delivered, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
    }

    public function test_timeout_is_retried_then_succeeds(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::sequence()
                ->pushStatus(500)
                ->push('ok', 200),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/retry', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        try {
            $this->runAttempt($delivery, 1);
        } catch (PartnerWebhookRetryableException) {
            // expected
        }

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::Pending, $delivery->status);
        $this->assertNotNull($delivery->next_retry_at);

        $this->runAttempt($delivery, 2);
        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::Delivered, $delivery->status);
        $this->assertSame(2, $delivery->attempt_count);
    }

    public function test_5xx_is_retried(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('error', 502),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/502', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        $this->expectException(PartnerWebhookRetryableException::class);
        $this->runAttempt($delivery, 1);

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::Pending, $delivery->status);
        $this->assertSame(502, $delivery->response_status);
    }

    public function test_429_is_retried(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('slow down', 429),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/429', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        $this->expectException(PartnerWebhookRetryableException::class);
        $this->runAttempt($delivery, 1);
    }

    public function test_connection_failure_is_retried(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/timeout', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        $this->expectException(PartnerWebhookRetryableException::class);
        $this->runAttempt($delivery, 1);

        $delivery->refresh();
        $this->assertNull($delivery->response_status);
        $this->assertSame(WebhookDeliveryStatus::Pending, $delivery->status);
    }

    public function test_permanent_4xx_is_not_retried(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('bad request', 400),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/400', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        $this->runAttempt($delivery, 1);

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::Failed, $delivery->status);
        $this->assertSame(400, $delivery->response_status);
        $this->assertNull($delivery->next_retry_at);
    }

    public function test_maximum_attempts_marks_dead(): void
    {
        Config::set('webhooks.delivery.max_attempts', 3);

        Http::fake([
            'https://hooks.example.test/*' => Http::response('error', 500),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/max', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $this->runAttempt($delivery, $attempt);
            } catch (PartnerWebhookRetryableException) {
                if ($attempt === 3) {
                    $this->fail('Should not retry on final attempt.');
                }
            }
            $delivery->refresh();
        }

        $this->assertSame(WebhookDeliveryStatus::Dead, $delivery->status);
        $this->assertSame(3, $delivery->attempt_count);
    }

    public function test_disabled_endpoint_is_skipped(): void
    {
        Http::fake();

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/disabled', isActive: false);

        (new DeliverPartnerWebhookJob(
            $partner->id,
            'message.sent',
            (string) Str::uuid(),
            ['hello' => 'world'],
        ))->handle(app(\App\Support\WebhookUrlValidator::class));

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::query()->count());
    }

    public function test_duplicate_fan_out_does_not_create_duplicate_deliveries(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('ok', 200),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/dedupe', isActive: true);
        $eventId = (string) Str::uuid();

        $job = new DeliverPartnerWebhookJob($partner->id, 'message.sent', $eventId, ['a' => 1]);
        $job->handle(app(\App\Support\WebhookUrlValidator::class));
        $job->handle(app(\App\Support\WebhookUrlValidator::class));

        $this->assertSame(1, WebhookDelivery::query()->count());
    }

    public function test_delivered_delivery_is_not_sent_again_on_retry_job(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('ok', 200),
        ]);

        $partner = $this->partnerWithEndpoint('https://hooks.example.test/once', isActive: true);
        $delivery = $this->dispatchAndGetDelivery($partner);
        $this->runAttempt($delivery, 1);

        Http::fake([
            'https://hooks.example.test/*' => Http::response('should not hit', 500),
        ]);

        $this->runAttempt($delivery->fresh(), 2);
        Http::assertNothingSent();
    }

    public function test_ssrf_webhook_url_rejected_on_store(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['webhooks.write']);

        $headers = array_merge($this->withBearer($secret), [
            'Idempotency-Key' => 'ssrf-'.uniqid(),
        ]);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'https://169.254.169.254/latest/meta-data',
            'events' => ['message.sent'],
        ], $headers)->assertStatus(422);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'https://partner.internal/hooks',
            'events' => ['message.sent'],
        ], array_merge($headers, ['Idempotency-Key' => 'ssrf-internal-'.uniqid()]))
            ->assertStatus(422);
    }

    private function dispatchAndGetDelivery(Partner $partner): WebhookDelivery
    {
        Queue::fake();

        (new DeliverPartnerWebhookJob(
            $partner->id,
            'message.sent',
            (string) Str::uuid(),
            ['hello' => 'world'],
        ))->handle(app(\App\Support\WebhookUrlValidator::class));

        return WebhookDelivery::query()->firstOrFail();
    }

    private function runAttempt(WebhookDelivery $delivery, int $queueAttemptNumber): void
    {
        app(PartnerWebhookDeliveryExecutor::class)->attempt(
            $delivery->fresh(),
            $queueAttemptNumber,
        );
    }

    private function partnerWithEndpoint(string $url, bool $isActive): Partner
    {
        ['partner' => $partner] = $this->createActivePartnerWithApiKey(['webhooks.read']);

        $partner->forceFill(['webhook_secret' => Str::random(32)])->save();

        WebhookEndpoint::query()->create([
            'partner_id' => $partner->id,
            'url' => $url,
            'secret' => Str::random(32),
            'events' => ['message.sent'],
            'is_active' => $isActive,
        ]);

        return $partner->refresh();
    }
}
