import { useEffect, useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

const steps = ['Business', 'WhatsApp account', 'WABA', 'Phone number', 'Connected'];

export default function EmbeddedSignupSection() {
    const [idx, setIdx] = useState(0);
    const reduced = usePrefersReducedMotion();

    useEffect(() => {
        if (reduced) {
            return undefined;
        }
        const id = setInterval(() => setIdx((i) => (i + 1) % steps.length), 2000);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <SectionShell tone="lift" bridge>
            <div className="grid items-center gap-12 lg:grid-cols-2">
                <SectionHeader title="Let every business connect its own WhatsApp." description="Embedded Signup modal flow (simulated UI)." />
                <Reveal className="iqp-glow-border rounded-2xl iqp-glass p-2 lg:p-3">
                    <div className="rounded-xl bg-slate-950 p-6 lg:p-10">
                        <div className="flex gap-2">
                            <span className="h-3 w-3 rounded-full bg-red-500/60" />
                            <span className="h-3 w-3 rounded-full bg-amber-500/60" />
                            <span className="h-3 w-3 rounded-full bg-emerald-500/60" />
                        </div>
                        <p className="mt-8 text-2xl font-semibold text-white">{steps[idx]}</p>
                        <div className="mt-6 h-2 overflow-hidden rounded-full bg-slate-800">
                            <div className="h-full bg-gradient-to-r from-violet-600 to-cyan-500 transition-all duration-500" style={{ width: `${((idx + 1) / steps.length) * 100}%` }} />
                        </div>
                        <ol className="mt-8 space-y-2 font-mono text-xs text-slate-500">
                            {steps.map((s, i) => (
                                <li key={s} className={i === idx ? 'text-violet-300' : ''}>
                                    {i + 1}. {s}
                                </li>
                            ))}
                        </ol>
                    </div>
                </Reveal>
            </div>
        </SectionShell>
    );
}
