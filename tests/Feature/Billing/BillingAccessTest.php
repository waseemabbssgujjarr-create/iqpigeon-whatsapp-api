<?php

namespace Tests\Feature\Billing;

use App\Models\Partner;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class BillingAccessTest extends TestCase
{
    use CreatesDashboardUsers;
    use RefreshDatabase;

    public function test_owner_can_view_billing_page(): void
    {
        ['user' => $user] = $this->createVerifiedOwner();

        $this->actingAs($user)->get('/app/billing')->assertOk();
    }

    public function test_non_owner_cannot_start_checkout(): void
    {
        ['user' => $owner, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $plan = \App\Models\Plan::query()->where('is_active', true)->first();

        $member = User::factory()->create(['partner_id' => $partner->id]);

        $this->actingAs($member)
            ->post('/app/billing/checkout', ['plan' => $plan->slug])
            ->assertForbidden();
    }

    public function test_checkout_redirects_to_stripe_when_configured(): void
    {
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();

        $this->seed(\Database\Seeders\PlanSeeder::class);
        Config::set('services.stripe.price_id', 'price_test_checkout');

        $mock = Mockery::mock(StripeBillingService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('hasCheckoutPrice')->andReturn(true);
        $mock->shouldReceive('createCheckoutSession')->andReturn(
            \Stripe\Checkout\Session::constructFrom([
                'object' => 'checkout.session',
                'url' => 'https://checkout.stripe.test/c/pay_test',
            ]),
        );
        $this->app->instance(StripeBillingService::class, $mock);

        $plan = \App\Models\Plan::query()->where('is_active', true)->first();

        $this->actingAs($user)
            ->post('/app/billing/checkout', ['plan' => $plan->slug])
            ->assertRedirect('https://checkout.stripe.test/c/pay_test');
    }

    public function test_checkout_blocked_when_stripe_price_id_missing(): void
    {
        ['user' => $user] = $this->createVerifiedOwner();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        Config::set('services.stripe.secret', 'sk_test_fake');
        Config::set('services.stripe.key', 'pk_test_fake');
        Config::set('services.stripe.price_id', '');

        $plan = \App\Models\Plan::query()->where('is_active', true)->first();

        $this->actingAs($user)
            ->post('/app/billing/checkout', ['plan' => $plan->slug])
            ->assertRedirect()
            ->assertSessionHasErrors('plan');
    }
}
