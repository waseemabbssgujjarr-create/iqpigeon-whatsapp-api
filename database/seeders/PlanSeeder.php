<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'platform',
                'name' => 'Platform',
                'description' => 'IQPigeon WhatsApp API platform subscription (API access, dashboard, webhooks). Meta/WhatsApp messaging is billed separately by Meta to each connected business.',
                'stripe_price_id' => null,
                'price_cents' => 200,
                'currency' => 'usd',
                'interval' => 'month',
                'sort_order' => 10,
                'feature_json' => [
                    'default_scopes' => [
                        'messages.send',
                        'messages.read',
                        'connections.read',
                        'connections.write',
                        'templates.read',
                        'templates.write',
                        'media.read',
                        'media.write',
                        'webhooks.read',
                        'webhooks.write',
                        'usage.read',
                    ],
                    'rate_limit_per_minute' => 300,
                    'max_connections' => 15,
                ],
            ],
        ];

        Plan::query()->whereNotIn('slug', collect($plans)->pluck('slug'))->update(['is_active' => false]);

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['is_active' => true]),
            );
        }
    }
}
