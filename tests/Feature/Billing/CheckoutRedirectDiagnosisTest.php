<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

/**
 * Documents HTTP behavior for POST /app/billing/checkout (Inertia client).
 */
class CheckoutRedirectDiagnosisTest extends TestCase
{
    use CreatesDashboardUsers;
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
        ];
    }

    public function test_inertia_checkout_success_renders_handoff_page_with_stripe_url(): void
    {
        ['user' => $user] = $this->createVerifiedOwner();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        Config::set('services.stripe.price_id', 'price_test_checkout');

        $stripeUrl = 'https://checkout.stripe.com/c/pay/cs_test_example';

        $mock = Mockery::mock(StripeBillingService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('hasCheckoutPrice')->andReturn(true);
        $mock->shouldReceive('createCheckoutSession')->andReturn(
            \Stripe\Checkout\Session::constructFrom([
                'object' => 'checkout.session',
                'id' => 'cs_test_example',
                'url' => $stripeUrl,
            ]),
        );
        $this->app->instance(StripeBillingService::class, $mock);

        $plan = Plan::query()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post('/app/billing/checkout', ['plan' => $plan->slug]);

        $response->assertOk();
        $response->assertJsonPath('component', 'App/CheckoutRedirect');
        $response->assertJsonPath('props.redirectUrl', $stripeUrl);
    }

    public function test_missing_plan_slug_returns_validation_error(): void
    {
        ['user' => $user] = $this->createVerifiedOwner();
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post('/app/billing/checkout', []);

        $response->assertSessionHasErrors('plan');
    }

    public function test_stripe_api_failure_returns_back_with_plan_error(): void
    {
        ['user' => $user] = $this->createVerifiedOwner();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        Config::set('services.stripe.price_id', 'price_test');

        $mock = Mockery::mock(StripeBillingService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('hasCheckoutPrice')->andReturn(true);
        $mock->shouldReceive('createCheckoutSession')->andThrow(new \RuntimeException('Stripe simulated failure'));
        $this->app->instance(StripeBillingService::class, $mock);

        $plan = Plan::query()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post('/app/billing/checkout', ['plan' => $plan->slug]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('plan');
    }

    /**
     * Production (54ca521) used redirect()->away — Inertia XHR does not perform full browser navigation.
     */
    public function test_legacy_external_302_lacks_inertia_location_header(): void
    {
        $response = response('', 302, ['Location' => 'https://checkout.stripe.com/c/pay/example']);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-Inertia-Location'));
    }
}
