<?php

namespace Tests\Feature\Meta;

use App\Jobs\ProcessMetaWebhookJob;
use App\Models\SystemEvent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.meta.webhook_verify_token', 'verify-token-test');
        Config::set('services.meta.app_secret', 'meta-app-secret-test');
    }

    public function test_get_subscription_challenge_echoes_hub_challenge(): void
    {
        $this->get('/webhooks/meta?hub_mode=subscribe&hub_verify_token=verify-token-test&hub_challenge=12345')
            ->assertOk()
            ->assertSee('12345');
    }

    public function test_post_with_invalid_signature_is_forbidden(): void
    {
        $payload = json_encode(['object' => 'whatsapp_business_account'], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/webhooks/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
            ],
            $payload,
        )->assertForbidden();
    }

    public function test_post_with_valid_signature_dispatches_processing_job(): void
    {
        Bus::fake([ProcessMetaWebhookJob::class]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'entry-1', 'changes' => []]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret-test');

        $this->call(
            'POST',
            '/webhooks/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload,
        )->assertOk()
            ->assertSee('EVENT_RECEIVED');

        Bus::assertDispatched(ProcessMetaWebhookJob::class);
    }

    public function test_duplicate_external_id_does_not_double_dispatch(): void
    {
        Bus::fake([ProcessMetaWebhookJob::class]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'entry-dup', 'changes' => []]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'meta-app-secret-test');
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ];

        $this->call('POST', '/webhooks/meta', [], [], [], $headers, $payload)->assertOk();

        $event = SystemEvent::query()->where('source', 'meta')->firstOrFail();
        $event->forceFill(['processed_at' => now()])->save();

        $this->call('POST', '/webhooks/meta', [], [], [], $headers, $payload)->assertOk();

        Bus::assertDispatchedTimes(ProcessMetaWebhookJob::class, 1);
    }
}
