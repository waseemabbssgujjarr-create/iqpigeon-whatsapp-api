import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

function formatMoney(cents, currency) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
    }).format((cents || 0) / 100);
}

export default function Billing({ partner, subscription, plans, stripeConfigured, checkoutNotice }) {
    const checkout = useForm({ plan: '' });

    const startCheckout = (slug) => {
        checkout.setData('plan', slug);
        checkout.post('/app/billing/checkout');
    };

    return (
        <AppLayout title="Billing">
            <p className="mb-6 text-sm text-slate-600">
                Your Stripe subscription pays for the <strong>IQPigeon API platform</strong> only. Meta/WhatsApp
                messaging charges are billed separately by Meta to each connected business.
            </p>
            {checkoutNotice && (
                <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {checkoutNotice}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                    <h2 className="text-sm font-medium uppercase tracking-wide text-slate-500">Current subscription</h2>
                    {subscription ? (
                        <div className="mt-4 space-y-2">
                            <p className="text-xl font-semibold text-[#0f172a]">
                                {subscription.plan?.name ?? 'Plan'}
                            </p>
                            <p className="text-sm text-slate-600">
                                Status:{' '}
                                <span className="font-medium capitalize">{subscription.stripe_status.replace('_', ' ')}</span>
                            </p>
                            {subscription.ends_at && (
                                <p className="text-sm text-slate-600">Ends: {new Date(subscription.ends_at).toLocaleDateString()}</p>
                            )}
                            {partner && (
                                <p className="text-sm text-slate-600">
                                    Account: <span className="capitalize">{partner.provisioning_status.replace('_', ' ')}</span>
                                </p>
                            )}
                            <button
                                type="button"
                                onClick={() => router.post('/app/billing/portal')}
                                disabled={!stripeConfigured}
                                className="mt-4 w-full rounded-lg bg-[#0f172a] px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
                            >
                                Manage in Stripe
                            </button>
                        </div>
                    ) : (
                        <p className="mt-4 text-sm text-slate-600">No active subscription yet. Choose a plan below.</p>
                    )}
                </section>

                <section className="lg:col-span-2">
                    <h2 className="mb-4 text-lg font-semibold text-[#0f172a]">Plans</h2>
                    {!stripeConfigured && (
                        <p className="mb-4 text-sm text-amber-700">
                            Stripe is not configured in this environment. Set STRIPE_KEY, STRIPE_SECRET, and STRIPE_PRICE_ID to enable checkout.
                        </p>
                    )}
                    <div className="grid gap-4 md:grid-cols-2">
                        {plans?.map((plan) => (
                            <article
                                key={plan.slug}
                                className="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                            >
                                <h3 className="text-lg font-semibold text-[#0f172a]">{plan.name}</h3>
                                <p className="mt-1 text-2xl font-bold text-violet-700">
                                    {formatMoney(plan.price_cents, plan.currency)}
                                    <span className="text-sm font-normal text-slate-500"> / {plan.interval}</span>
                                </p>
                                <p className="mt-3 flex-1 text-sm text-slate-600">{plan.description}</p>
                                <button
                                    type="button"
                                    disabled={!stripeConfigured || checkout.processing}
                                    onClick={() => startCheckout(plan.slug)}
                                    className="mt-4 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-50"
                                >
                                    Subscribe
                                </button>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
