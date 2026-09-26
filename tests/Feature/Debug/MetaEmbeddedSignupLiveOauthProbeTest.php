<?php

namespace Tests\Feature\Debug;

use App\Models\Partner;
use App\Services\ConnectionOnboardingService;
use App\Support\MetaEmbeddedSignupExtras;
use Database\Seeders\ApiScopeSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class MetaEmbeddedSignupLiveOauthProbeTest extends TestCase
{
    use CreatesDashboardUsers;

    public function test_oauth_meta_start_location_matches_direct_authorization_url_for_coexistence(): void
    {
        config([
            'services.meta.app_id' => '552479924130015',
            'services.meta.es_config_id' => '1647730086942089',
            'services.meta.es_config_id_coexistence' => '97624893834457',
            'services.meta.graph_version' => 'v25.0',
        ]);

        $this->seed(ApiScopeSeeder::class);
        $this->seed(PlanSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalSubscription($partner);

        $result = app(ConnectionOnboardingService::class)->startOnboarding($partner, onboardingSource: 'coexistence');
        $token = $result['session_token'];

        $response = $this->get('/oauth/meta/start?token='.urlencode($token));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('97624893834457', $location);
        $this->assertStringContainsString('whatsapp_business_app_onboarding', urldecode($location));
        $this->assertStringContainsString('sessionInfoVersion', urldecode($location));
        $this->assertStringNotContainsString(
            'extras='.urlencode('{"version":"v4"}'),
            $location,
        );

        $expectedFragment = MetaEmbeddedSignupExtras::encodeBusinessAppCoexistence();
        $this->assertStringContainsString(urlencode($expectedFragment), $location);
    }

    public function test_live_oauth_probe_route_requires_auth(): void
    {
        $this->get(route('debug.meta-embedded-signup.live-oauth-probe'))->assertRedirect();
    }

    public function test_live_oauth_probe_renders_for_authenticated_user(): void
    {
        $this->seed(ApiScopeSeeder::class);
        $this->seed(PlanSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalSubscription($partner);

        $response = $this->actingAs($user)->get(route('debug.meta-embedded-signup.live-oauth-probe'));
        $response->assertOk();
        $response->assertSee('Live OAuth probe', false);
    }

    private function seedOperationalSubscription(Partner $partner): void
    {
        $plan = \App\Models\Plan::query()->where('slug', 'platform')->firstOrFail();
        \App\Models\Subscription::query()->create([
            'partner_id' => $partner->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => 'sub_probe_'.uniqid(),
            'stripe_status' => \App\Enums\SubscriptionStatus::Active,
        ]);
    }
}
