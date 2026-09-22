<?php

namespace App\Services\Billing;

use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeBillingService
{
    public function isConfigured(): bool
    {
        return (string) Config::get('services.stripe.secret') !== ''
            && (string) Config::get('services.stripe.key') !== '';
    }

    public function hasCheckoutPrice(): bool
    {
        return (string) Config::get('services.stripe.price_id') !== '';
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

        $priceId = (string) Config::get('services.stripe.price_id', '');

        if ($priceId === '') {
            throw new \RuntimeException('STRIPE_PRICE_ID is not configured.');
        }

        Log::info('billing.stripe.checkout.creating', [
            'partner_id' => $partner->id,
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
            'price_id_set' => true,
            'stripe_customer_present' => (bool) $partner->stripe_id,
        ]);

        $session = Session::create([
            'customer' => $partner->stripe_id,
            'mode' => 'subscription',
            'client_reference_id' => (string) $partner->uuid,
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
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

        Log::info('billing.stripe.checkout.created', [
            'partner_id' => $partner->id,
            'plan_slug' => $plan->slug,
            'session_id' => $session->id ?? null,
            'session_url_present' => ! empty($session->url),
        ]);

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
