<?php

namespace Tests\Feature;

use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class WebhookSsrfValidationTest extends TestCase
{
    use CreatesApiPartners;

    public function test_private_webhook_url_is_rejected(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['webhooks.write']);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'https://127.0.0.1/hook',
            'events' => ['message.sent'],
        ], $this->withBearer($secret))
            ->assertStatus(400);
    }
}
