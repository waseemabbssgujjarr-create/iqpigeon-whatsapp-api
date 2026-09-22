import { Link } from '@inertiajs/react';
import Reveal from '../components/Reveal';
import { formatMoney } from '../utils/formatMoney';

export default function PricingSection({ plans = [] }) {
    const plan = plans.find((p) => p.slug === 'platform') || plans[0];

    if (!plan) {
        return (
            <section className="py-24" id="pricing">
                <div className="mx-auto max-w-lg px-4 text-center text-slate-400">
                    <p>Pricing is configured in the dashboard. Contact us for access.</p>
                    <Link href="/signup" className="iqp-btn-primary mt-6 inline-block rounded-xl px-6 py-3 text-sm font-semibold text-white">
                        Create account
                    </Link>
                </div>
            </section>
        );
    }

    const isLowTestPrice = plan.price_cents <= 500;

    return (
        <section className="py-24 sm:py-28" id="pricing">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-center text-3xl font-semibold text-white sm:text-4xl">Simple platform pricing</h2>
                    <p className="mx-auto mt-4 max-w-xl text-center text-slate-400">
                        One subscription for API access. Swap Stripe prices in production without redesigning this page.
                    </p>
                </Reveal>
                <Reveal delay={100} className="mx-auto mt-12 max-w-md">
                    <div className="iqp-glow-border rounded-2xl iqp-glass-strong p-8 text-center">
                        <h3 className="text-xl font-semibold text-white">{plan.name || 'IQPigeon WhatsApp API'}</h3>
                        <p className="mt-4 font-mono text-4xl font-bold text-violet-300">
                            {formatMoney(plan.price_cents, plan.currency)}
                            <span className="text-base font-normal text-slate-500"> / {plan.interval || 'month'}</span>
                        </p>
                        {isLowTestPrice && (
                            <p className="mt-2 text-sm text-amber-200/90">Testing price — production billing uses your configured Stripe Price ID.</p>
                        )}
                        <p className="mt-4 text-sm leading-relaxed text-slate-400">{plan.description}</p>
                        <Link href="/signup" className="iqp-btn-primary mt-8 inline-flex w-full justify-center rounded-xl px-6 py-3 text-sm font-semibold text-white">
                            Start building
                        </Link>
                        <p className="mt-6 text-xs leading-relaxed text-slate-500">
                            Meta/WhatsApp messaging charges are separate and remain associated with each connected business&apos;s own Meta
                            billing arrangement.
                        </p>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
