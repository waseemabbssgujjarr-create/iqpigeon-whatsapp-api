import { useState } from 'react';
import Reveal from '../components/Reveal';

const cases = {
    'Sales CRM': 'Route hot leads to WhatsApp sequences while your CRM owns the pipeline UI.',
    'Support CRM': 'Ticket updates and agent replies flow through scoped API keys per workspace.',
    'Healthcare CRM': 'Appointment reminders with idempotent sends and webhook delivery history.',
    'Real Estate CRM': 'Listing alerts and tour confirmations without sharing Meta credentials across tenants.',
    Education: 'Class notifications with per-school Embedded Signup and isolated WABAs.',
    'Automation Platform': 'One integration layer — your users bring their own Meta billing.',
};

export default function UseCasesSection() {
    const [active, setActive] = useState('Sales CRM');
    const keys = Object.keys(cases);

    return (
        <section className="py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Built for CRM builders</h2>
                </Reveal>
                <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_1.2fr]">
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-1">
                        {keys.map((name) => (
                            <button
                                key={name}
                                type="button"
                                className={`rounded-xl border px-4 py-3 text-left text-sm transition ${
                                    active === name
                                        ? 'border-violet-500/60 bg-violet-500/10 text-white'
                                        : 'border-slate-800 text-slate-400 hover:border-slate-600'
                                }`}
                                onClick={() => setActive(name)}
                                onMouseEnter={() => setActive(name)}
                                onFocus={() => setActive(name)}
                            >
                                {name}
                            </button>
                        ))}
                    </div>
                    <Reveal className="iqp-glow-border flex min-h-[200px] items-center rounded-2xl iqp-glass p-8">
                        <div>
                            <p className="font-mono text-xs uppercase tracking-widest text-cyan-400">Workflow</p>
                            <p className="mt-4 text-lg leading-relaxed text-slate-200">{cases[active]}</p>
                        </div>
                    </Reveal>
                </div>
            </div>
        </section>
    );
}
