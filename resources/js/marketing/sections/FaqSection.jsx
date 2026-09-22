import { useState } from 'react';
import Reveal from '../components/Reveal';

const faqs = [
    {
        q: 'How does onboarding work?',
        a: 'Register your CRM organization, complete Stripe checkout, and activate via verified webhooks. Then create API keys, register webhook URLs, and start Embedded Signup sessions for each business.',
    },
    {
        q: 'Does each business connect its own Meta account?',
        a: 'Yes. Embedded Signup and OAuth bind each WhatsApp Business Account to the connecting business. Credentials are encrypted and never returned by the API.',
    },
    {
        q: 'Who pays Meta?',
        a: 'The connected business maintains its own Meta commercial relationship for WhatsApp messaging. Your IQPigeon subscription covers platform API access only.',
    },
    {
        q: 'How do webhooks work?',
        a: 'Register HTTPS endpoints with scoped API keys. Events are signed (HMAC) and delivered asynchronously with retries for transient failures.',
    },
    {
        q: 'Can one CRM connect multiple businesses?',
        a: 'Yes. Each connection is tenant-scoped to your partner account with isolated credentials and delivery history.',
    },
    {
        q: 'How do API keys work?',
        a: 'Keys use the iqp_live_* prefix, are stored hashed, shown once at creation, and enforce dot-notation scopes on every request.',
    },
    {
        q: 'How does retry handling work?',
        a: 'Partner webhook delivery retries connection errors, timeouts, 408, 429, and 5xx with bounded exponential backoff. Permanent 4xx responses are not retried.',
    },
    {
        q: 'What happens when a webhook fails?',
        a: 'Attempts are recorded in delivery history. Retryable errors schedule queue retries; exhausted attempts mark the delivery dead for operator review.',
    },
];

export default function FaqSection() {
    const [open, setOpen] = useState(0);

    return (
        <section className="py-24 sm:py-28" id="faq">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-center text-3xl font-semibold text-white">FAQ</h2>
                </Reveal>
                <div className="mt-10 space-y-2">
                    {faqs.map((item, i) => {
                        const expanded = open === i;
                        return (
                            <Reveal key={item.q} delay={i * 40}>
                                <div className="iqp-glass overflow-hidden rounded-xl">
                                    <button
                                        type="button"
                                        className="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-medium text-white"
                                        aria-expanded={expanded}
                                        onClick={() => setOpen(expanded ? -1 : i)}
                                    >
                                        {item.q}
                                        <span className="text-violet-400" aria-hidden="true">
                                            {expanded ? '−' : '+'}
                                        </span>
                                    </button>
                                    <div
                                        className={`grid transition-all duration-300 ease-out ${
                                            expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
                                        }`}
                                    >
                                        <div className="overflow-hidden">
                                            <p className="px-5 pb-4 text-sm leading-relaxed text-slate-400">{item.a}</p>
                                        </div>
                                    </div>
                                </div>
                            </Reveal>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
