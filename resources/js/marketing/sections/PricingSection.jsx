import { Link } from '@inertiajs/react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { formatMoney } from '../utils/formatMoney';

export default function PricingSection({ plans = [] }) {
    const plan = plans.find((p) => p.slug === 'platform') || plans[0];

    return (
        <SectionShell tone="grid" bridge id="pricing">
            <SectionHeader title="Simple platform pricing" align="center" description="One package — price from your plan catalog & Stripe Price ID." />
            {plan ? (
                <Reveal delay={100} className="mx-auto mt-12 max-w-lg lg:max-w-xl">
                    <div className="iqp-glow-border rounded-3xl iqp-glass-strong p-10 text-center lg:p-12">
                        <h3 className="text-2xl font-semibold text-white">{plan.name}</h3>
                        <p className="mt-6 font-mono text-5xl font-bold text-violet-300">
                            {formatMoney(plan.price_cents, plan.currency)}
                            <span className="text-lg font-normal text-slate-500"> / {plan.interval}</span>
                        </p>
                        {plan.price_cents <= 500 && (
                            <p className="mt-3 text-sm text-amber-200/90">Testing price — production uses your configured Stripe price.</p>
                        )}
                        <ul className="mt-8 space-y-2 text-left text-sm text-slate-400">
                            <li>Platform API access</li>
                            <li>Dashboard &amp; developer tools</li>
                            <li>Partner webhooks &amp; usage</li>
                        </ul>
                        <Link href="/signup" className="iqp-btn-primary mt-10 inline-flex w-full justify-center rounded-xl py-3.5 text-sm font-semibold text-white">
                            Start building
                        </Link>
                        <p className="mt-8 text-xs leading-relaxed text-slate-500">
                            Meta/WhatsApp messaging charges are separate and billed by Meta to each connected business.
                        </p>
                    </div>
                </Reveal>
            ) : (
                <p className="mt-8 text-center text-slate-500">Plans are configured in the dashboard catalog.</p>
            )}
        </SectionShell>
    );
}
