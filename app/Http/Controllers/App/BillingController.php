<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\AuditLogService;
use App\Services\Billing\StripeBillingService;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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

    public function checkout(Request $request, StripeBillingService $billing, AuditLogService $audit): HttpResponse|RedirectResponse|Response
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        if ($partner === null || $partner->owner_user_id !== $user->id) {
            abort(403);
        }

        Log::info('billing.checkout.attempt', [
            'partner_id' => $partner->id,
            'plan_slug' => $validated['plan'],
            'user_id' => $user->id,
            'stripe_configured' => $billing->isConfigured(),
            'price_id_configured' => $billing->hasCheckoutPrice(),
        ]);

        if (! $billing->isConfigured()) {
            return back()->withErrors(['plan' => 'Unable to start checkout. Please try again.']);
        }

        if (! $billing->hasCheckoutPrice()) {
            return back()->withErrors(['plan' => 'Unable to start checkout. Please try again.']);
        }

        $plan = Plan::query()->where('slug', $validated['plan'])->where('is_active', true)->firstOrFail();

        try {
            $session = $billing->createCheckoutSession($partner, $plan, $user);
        } catch (ApiErrorException $e) {
            report($e);
            Log::warning('billing.checkout.stripe_failed', [
                'partner_id' => $partner->id,
                'plan_slug' => $plan->slug,
                'stripe_code' => $e->getStripeCode(),
                'http_status' => $e->getHttpStatus(),
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['plan' => 'Unable to start checkout. Please try again.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('billing.checkout.unexpected_failure', [
                'partner_id' => $partner->id,
                'plan_slug' => $plan->slug,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['plan' => 'Unable to start checkout. Please try again.']);
        }

        if (empty($session->url)) {
            Log::error('billing.checkout.missing_session_url', [
                'partner_id' => $partner->id,
                'session_id' => $session->id ?? null,
            ]);

            return back()->withErrors(['plan' => 'Unable to start checkout. Please try again.']);
        }

        $audit->log('billing.checkout_started', $partner, user: $user, subject: $plan);

        return Inertia::render('App/CheckoutRedirect', [
            'redirectUrl' => $session->url,
        ]);
    }

    public function portal(Request $request, StripeBillingService $billing): HttpResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        if ($partner === null || $partner->owner_user_id !== $user->id) {
            abort(403);
        }

        if (! $billing->isConfigured() || ! $partner->stripe_id) {
            return back()->withErrors(['portal' => 'No billing account found. Complete checkout first.']);
        }

        try {
            $url = $billing->createPortalSession($partner, route('app.billing'));
        } catch (ApiErrorException $e) {
            report($e);

            return back()->withErrors(['portal' => 'Could not open the Stripe billing portal. Please try again.']);
        }

        return Inertia::render('App/CheckoutRedirect', [
            'redirectUrl' => $url,
        ]);
    }
}
