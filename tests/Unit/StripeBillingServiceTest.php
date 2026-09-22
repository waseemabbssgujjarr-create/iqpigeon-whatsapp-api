<?php

namespace Tests\Unit;

use App\Services\Billing\StripeBillingService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripeBillingServiceTest extends TestCase
{
    public function test_has_checkout_price_when_stripe_price_id_set(): void
    {
        Config::set('services.stripe.price_id', 'price_test_123');

        $this->assertTrue(app(StripeBillingService::class)->hasCheckoutPrice());
    }

    public function test_has_checkout_price_false_when_empty(): void
    {
        Config::set('services.stripe.price_id', '');

        $this->assertFalse(app(StripeBillingService::class)->hasCheckoutPrice());
    }
}
