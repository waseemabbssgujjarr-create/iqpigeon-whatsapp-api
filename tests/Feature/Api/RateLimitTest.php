<?php

namespace Tests\Feature\Api;

use Database\Seeders\ApiScopeSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use CreatesApiPartners;

    public function test_partner_api_rate_limit_returns_429(): void
    {
        $this->seed(ApiScopeSeeder::class);

        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $subscription = $partner->subscriptions()->firstOrFail();
        $plan = $subscription->plan;
        $features = $plan->feature_json ?? [];
        $features['rate_limit_per_minute'] = 2;
        $plan->forceFill(['feature_json' => $features])->save();

        RateLimiter::clear('partner-api:'.$partner->id);

        $headers = $this->withBearer($secret);

        $this->getJson('/api/v1/me', $headers)->assertOk();
        $this->getJson('/api/v1/me', $headers)->assertOk();

        $this->getJson('/api/v1/me', $headers)
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limit_exceeded');
    }
}
