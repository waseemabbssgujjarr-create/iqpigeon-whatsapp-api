import { useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

const faqs = [
    { q: 'How does onboarding work?', a: 'Register, verify email, complete Stripe checkout, then activate via verified webhooks — not the browser return URL.' },
    { q: 'Does each business connect its own Meta account?', a: 'Yes — Embedded Signup binds each WABA; credentials are encrypted and never exposed via API.' },
    { q: 'Who pays Meta?', a: 'Each connected business pays Meta for WhatsApp messaging. IQPigeon bills the CRM platform subscription only.' },
    { q: 'How do webhooks work?', a: 'HTTPS endpoints receive signed JSON events with bounded retries on transient failures.' },
    { q: 'Can one CRM connect multiple businesses?', a: 'Yes — tenant-scoped connections and delivery history per partner.' },
    { q: 'How do API keys work?', a: 'iqp_live_* keys, hashed storage, shown once, dot-notation scopes.' },
    { q: 'How does retry handling work?', a: '408, 429, 5xx, timeouts, and connection errors retry with exponential backoff.' },
    { q: 'What happens when a webhook fails?', a: 'Delivery history shows attempts; exhausted retries mark dead for review.' },
];

export default function FaqSection() {
    const [open, setOpen] = useState(0);

    return (
        <SectionShell tone="ink" bridge id="faq">
            <SectionHeader title="FAQ" align="center" />
            <div className="mx-auto mt-12 max-w-3xl space-y-2">
                {faqs.map((item, i) => {
                    const expanded = open === i;
                    return (
                        <Reveal key={item.q} delay={i * 30}>
                            <div className="iqp-glass overflow-hidden rounded-xl">
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-medium text-white"
                                    aria-expanded={expanded}
                                    onClick={() => setOpen(expanded ? -1 : i)}
                                >
                                    {item.q}
                                    <span className="text-violet-400">{expanded ? '−' : '+'}</span>
                                </button>
                                <div className={`grid transition-all duration-300 ${expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'}`}>
                                    <div className="overflow-hidden">
                                        <p className="px-5 pb-4 text-sm leading-relaxed text-slate-400">{item.a}</p>
                                    </div>
                                </div>
                            </div>
                        </Reveal>
                    );
                })}
            </div>
        </SectionShell>
    );
}
