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
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'For CRMs launching their first WhatsApp integration.',
                'stripe_price_id' => null,
                'price_cents' => 9900,
                'currency' => 'usd',
                'interval' => 'month',
                'sort_order' => 10,
                'feature_json' => [
                    'default_scopes' => [
                        'messages.send',
                        'messages.read',
                        'connections.read',
                        'connections.write',
                        'webhooks.read',
                        'usage.read',
                    ],
                    'rate_limit_per_minute' => 60,
                    'max_connections' => 3,
                ],
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'description' => 'Higher API limits for growing CRM integrations.',
                'stripe_price_id' => null,
                'price_cents' => 24900,
                'currency' => 'usd',
                'interval' => 'month',
                'sort_order' => 20,
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
            [
                'slug' => 'scale',
                'name' => 'Scale',
                'description' => 'Production CRM workloads with generous limits.',
                'stripe_price_id' => null,
                'price_cents' => 59900,
                'currency' => 'usd',
                'interval' => 'month',
                'sort_order' => 30,
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
                    'rate_limit_per_minute' => 1000,
                    'max_connections' => 50,
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Custom limits and sales-assisted onboarding.',
                'stripe_price_id' => null,
                'price_cents' => 0,
                'currency' => 'usd',
                'interval' => 'month',
                'sort_order' => 40,
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
                    'rate_limit_per_minute' => 2000,
                    'max_connections' => null,
                    'contact_sales' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
