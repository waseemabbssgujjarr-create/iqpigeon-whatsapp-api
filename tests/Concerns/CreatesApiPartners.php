<?php

namespace Tests\Concerns;

use App\Enums\SubscriptionStatus;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ApiKeyService;
use Database\Seeders\ApiScopeSeeder;

trait CreatesApiPartners
{
    /**
     * @param  list<string>  $scopes
     * @return array{partner: Partner, secret: string}
     */
    protected function createActivePartnerWithApiKey(array $scopes = []): array
    {
        $this->seed(ApiScopeSeeder::class);

        if ($scopes === []) {
            $scopes = [
                'connections.read',
                'connections.write',
                'messages.send',
                'messages.read',
                'webhooks.read',
                'webhooks.write',
                'usage.read',
            ];
        }

        $partner = Partner::factory()->create();

        $plan = Plan::query()->firstOrCreate(
            ['slug' => 'test-plan'],
            [
                'name' => 'Test',
                'feature_json' => ['default_scopes' => $scopes],
            ],
        );

        Subscription::query()->create([
            'partner_id' => $partner->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_'.uniqid(),
            'stripe_status' => SubscriptionStatus::Active,
        ]);

        $generated = app(ApiKeyService::class)->generate($partner, 'Test', $scopes);

        return [
            'partner' => $partner->refresh(),
            'secret' => $generated['secret'],
        ];
    }

    protected function withBearer(string $secret): array
    {
        return [
            'Authorization' => 'Bearer '.$secret,
            'Accept' => 'application/json',
        ];
    }
}
