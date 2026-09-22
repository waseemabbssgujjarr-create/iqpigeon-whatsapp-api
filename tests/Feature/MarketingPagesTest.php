<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function publicRoutes(): array
    {
        return [
            '/',
            '/pricing',
            '/features',
            '/developers',
            '/docs',
            '/contact',
            '/security',
            '/terms',
            '/privacy',
            '/login',
            '/signup',
        ];
    }

    public function test_marketing_and_auth_pages_load(): void
    {
        foreach ($this->publicRoutes() as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_home_includes_plan_pricing_props(): void
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Marketing/Home')
            ->has('plans', 1)
            ->where('plans.0.slug', 'platform'));
    }
}
