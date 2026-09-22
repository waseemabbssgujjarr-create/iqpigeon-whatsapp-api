<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\AuditLogService;
use App\Services\Billing\StripeBillingService;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name', 'description', 'price_cents', 'currency', 'interval', 'feature_json', 'stripe_price_id']);

        $subscription = $partner?->activeSubscription()->with('plan')->first();

        return Inertia::render('App/Billing', [
            'partner' => $partner ? [
                'name' => $partner->name,
                'status' => $partner->status->value,
                'provisioning_status' => $partner->provisioning_status->value,
            ] : null,
            'subscription' => $subscription ? [
                'stripe_status' => $subscription->stripe_status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'plan' => $subscription->plan?->only(['name', 'slug', 'price_cents', 'currency', 'interval']),
            ] : null,
            'plans' => $plans,
            'stripeConfigured' => app(StripeBillingService::class)->isConfigured(),
            'checkoutNotice' => $request->query('checkout') === 'returned'
                ? 'Payment is processing. Your plan activates after Stripe confirms payment (usually within a minute).'
                : ($request->query('checkout') === 'cancelled' ? 'Checkout was cancelled. You can try again anytime.' : null),
        ]);
    }

    public function checkout(Request $request, StripeBillingService $billing, AuditLogService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        if ($partner === null || $partner->owner_user_id !== $user->id) {
            abort(403);
        }

        if (! $billing->isConfigured()) {
            return back()->withErrors(['plan' => 'Billing is not configured yet. Contact support.']);
        }

        if (! $billing->hasCheckoutPrice()) {
            return back()->withErrors(['plan' => 'Billing price is not configured. Set STRIPE_PRICE_ID on the server.']);
        }

        $plan = Plan::query()->where('slug', $validated['plan'])->where('is_active', true)->firstOrFail();

        $session = $billing->createCheckoutSession($partner, $plan, $user);

        $audit->log('billing.checkout_started', $partner, user: $user, subject: $plan);

        return redirect()->away($session->url);
    }

    public function portal(Request $request, StripeBillingService $billing): RedirectResponse
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        if ($partner === null || $partner->owner_user_id !== $user->id) {
            abort(403);
        }

        if (! $billing->isConfigured() || ! $partner->stripe_id) {
            return back()->withErrors(['portal' => 'No billing account found. Complete checkout first.']);
        }

        $url = $billing->createPortalSession($partner, route('app.billing'));

        return redirect()->away($url);
    }
}
