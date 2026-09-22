import { useEffect, useState } from 'react';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

const steps = ['CRM', 'IQPigeon', 'Embedded Signup', 'Business', 'WABA', 'Phone', 'Connected'];

export default function EmbeddedSignupSection() {
    const reduced = usePrefersReducedMotion();
    const [idx, setIdx] = useState(0);

    useEffect(() => {
        if (reduced) {
            setIdx(steps.length - 1);
            return undefined;
        }
        const id = setInterval(() => setIdx((i) => (i + 1) % steps.length), 1800);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <section className="py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Let every business connect its own WhatsApp.</h2>
                    <p className="mt-4 max-w-2xl text-slate-400">
                        Embedded Signup keeps OAuth state safe and stores Meta credentials encrypted — each business retains its own WABA
                        relationship.
                    </p>
                </Reveal>
                <Reveal delay={100} className="mt-10 grid gap-8 lg:grid-cols-2">
                    <div className="iqp-glow-border rounded-2xl iqp-glass p-6">
                        <div className="flex items-center gap-2 border-b border-slate-700/60 pb-3">
                            <span className="h-3 w-3 rounded-full bg-red-500/70" />
                            <span className="h-3 w-3 rounded-full bg-amber-500/70" />
                            <span className="h-3 w-3 rounded-full bg-emerald-500/70" />
                            <span className="ml-2 font-mono text-xs text-slate-500">onboarding — mock UI</span>
                        </div>
                        <p className="mt-6 text-lg font-medium text-white">{steps[idx]}</p>
                        <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-800">
                            <div
                                className="h-full bg-gradient-to-r from-violet-600 to-cyan-500 transition-all duration-500"
                                style={{ width: `${((idx + 1) / steps.length) * 100}%` }}
                            />
                        </div>
                    </div>
                    <ol className="space-y-3">
                        {steps.map((step, i) => (
                            <li
                                key={step}
                                className={`rounded-lg border px-4 py-3 font-mono text-sm ${
                                    i === idx ? 'border-violet-500/50 bg-violet-500/10 text-white' : 'border-slate-800 text-slate-500'
                                }`}
                            >
                                {i + 1}. {step}
                            </li>
                        ))}
                    </ol>
                </Reveal>
            </div>
        </section>
    );
}
