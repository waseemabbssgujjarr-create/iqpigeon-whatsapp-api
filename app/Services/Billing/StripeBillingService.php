<?php

namespace App\Services\Billing;

use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeBillingService
{
    public function isConfigured(): bool
    {
        return (string) Config::get('services.stripe.secret') !== '';
    }

    public function ensureStripeCustomer(Partner $partner, User $user): Partner
    {
        $this->bootstrapStripe();

        if ($partner->stripe_id) {
            return $partner;
        }

        $partner->createAsStripeCustomer([
            'email' => $user->email,
            'name' => $partner->name,
            'metadata' => [
                'partner_id' => (string) $partner->id,
            ],
        ]);

        return $partner->refresh();
    }

    public function createCheckoutSession(Partner $partner, Plan $plan, User $user): Session
    {
        $this->bootstrapStripe();
        $partner = $this->ensureStripeCustomer($partner, $user);

        $lineItem = $plan->stripe_price_id
            ? ['price' => $plan->stripe_price_id, 'quantity' => 1]
            : [
                'price_data' => [
                    'currency' => $plan->currency,
                    'unit_amount' => $plan->price_cents,
                    'recurring' => ['interval' => $plan->interval === 'year' ? 'year' : 'month'],
                    'product_data' => [
                        'name' => $plan->name,
                        'description' => (string) ($plan->description ?? ''),
                    ],
                ],
                'quantity' => 1,
            ];

        $session = Session::create([
            'customer' => $partner->stripe_id,
            'mode' => 'subscription',
            'line_items' => [$lineItem],
            'success_url' => route('app.billing').'?checkout=returned',
            'cancel_url' => route('app.billing').'?checkout=cancelled',
            'metadata' => [
                'partner_id' => (string) $partner->id,
                'plan_id' => (string) $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'partner_id' => (string) $partner->id,
                    'plan_id' => (string) $plan->id,
                ],
            ],
        ]);

        $partner->forceFill([
            'provisioning_status' => ProvisioningStatus::CheckoutPending,
        ])->save();

        return $session;
    }

    public function createPortalSession(Partner $partner, string $returnUrl): string
    {
        $this->bootstrapStripe();

        if (! $partner->stripe_id) {
            throw new \RuntimeException('No Stripe customer on file.');
        }

        return $partner->billingPortalUrl($returnUrl);
    }

    private function bootstrapStripe(): void
    {
        Stripe::setApiKey((string) Config::get('services.stripe.secret'));
    }
}
