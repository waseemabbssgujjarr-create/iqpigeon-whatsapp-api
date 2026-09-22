import { useState } from 'react';
import Reveal from '../components/Reveal';

const modes = {
    connect: {
        title: 'CONNECT',
        steps: ['CRM', 'Embedded Signup', 'Business', 'WABA'],
    },
    build: {
        title: 'BUILD',
        steps: ['CRM', 'API', 'Queue', 'WhatsApp'],
    },
    receive: {
        title: 'RECEIVE',
        steps: ['WhatsApp', 'Meta webhook', 'IQPigeon', 'CRM webhook'],
    },
};

export default function ArchitectureSection() {
    const [active, setActive] = useState('build');
    const flow = modes[active];

    return (
        <section className="py-24 sm:py-28" id="architecture">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                        One infrastructure layer between your CRM and WhatsApp.
                    </h2>
                </Reveal>
                <div className="mt-12 grid gap-8 lg:grid-cols-[280px_1fr]">
                    <div className="flex flex-row gap-2 lg:flex-col" role="tablist" aria-label="Architecture flows">
                        {Object.entries(modes).map(([key, mode]) => (
                            <button
                                key={key}
                                type="button"
                                role="tab"
                                aria-selected={active === key}
                                className={`rounded-xl border px-4 py-3 text-left font-mono text-sm transition ${
                                    active === key
                                        ? 'border-violet-500/60 bg-violet-500/10 text-white shadow-[0_0_24px_rgb(139_92_246_/_0.15)]'
                                        : 'border-slate-700/60 text-slate-400 hover:border-slate-500'
                                }`}
                                onClick={() => setActive(key)}
                                onMouseEnter={() => setActive(key)}
                                onFocus={() => setActive(key)}
                            >
                                {mode.title}
                            </button>
                        ))}
                    </div>
                    <Reveal className="iqp-glow-border rounded-2xl iqp-glass p-8">
                        <p className="font-mono text-xs uppercase tracking-widest text-cyan-400/90">{flow.title} flow</p>
                        <div className="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:flex-wrap sm:justify-center">
                            {flow.steps.map((step, i) => (
                                <div key={step} className="flex items-center gap-4">
                                    <div className="rounded-lg border border-slate-600/50 bg-slate-900/80 px-4 py-3 font-mono text-sm text-slate-200">
                                        {step}
                                    </div>
                                    {i < flow.steps.length - 1 && (
                                        <span className="hidden text-violet-400 sm:inline" aria-hidden="true">
                                            →
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                        <svg className="mx-auto mt-10 h-24 w-full max-w-md opacity-70" viewBox="0 0 320 80" aria-hidden="true">
                            <path
                                d="M10 40 H310"
                                stroke="url(#arch-grad)"
                                strokeWidth="2"
                                fill="none"
                                strokeDasharray="320"
                                strokeDashoffset="320"
                                style={{ animation: 'iqp-flow-dash 2s ease forwards' }}
                            />
                            <defs>
                                <linearGradient id="arch-grad" x1="0" y1="0" x2="1" y2="0">
                                    <stop stopColor="#8b5cf6" />
                                    <stop stopColor="#22d3ee" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </Reveal>
                </div>
            </div>
        </section>
    );
}
