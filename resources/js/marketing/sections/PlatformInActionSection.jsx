import { useEffect, useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

const steps = ['API request', 'Message created', 'Queue', 'Meta', 'Delivered', 'Webhook received'];

export default function PlatformInActionSection() {
    const [active, setActive] = useState(0);
    const reduced = usePrefersReducedMotion();

    useEffect(() => {
        if (reduced) {
            return undefined;
        }
        const id = setInterval(() => setActive((i) => (i + 1) % steps.length), 2000);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <SectionShell tone="navy" bridge id="platform">
            <SectionHeader
                eyebrow="Interactive example"
                title="Everything your CRM needs between the API and WhatsApp."
                description="Simulated dashboard flow — not live production data."
            />
            <Reveal delay={100} className="mt-12">
                <div className="iqp-glow-border overflow-hidden rounded-2xl border border-slate-700/40 bg-slate-950/80">
                    <div className="flex items-center gap-2 border-b border-slate-800 px-5 py-3">
                        <span className="font-mono text-xs text-slate-500">IQPigeon — message lifecycle (demo)</span>
                    </div>
                    <div className="grid gap-6 p-6 lg:grid-cols-[1fr_280px] lg:p-10">
                        <div className="space-y-4 font-mono text-xs">
                            {steps.map((step, i) => (
                                <div
                                    key={step}
                                    className={`rounded-lg border px-4 py-3 transition ${
                                        i === active
                                            ? 'border-violet-500/50 bg-violet-500/10 text-white'
                                            : 'border-slate-800 text-slate-500'
                                    }`}
                                >
                                    {i + 1}. {step}
                                </div>
                            ))}
                        </div>
                        <div className="iqp-product-panel p-4">
                            <p className="text-[10px] uppercase tracking-widest text-cyan-400">Live panel (demo)</p>
                            <p className="mt-4 font-mono text-sm text-emerald-300">msg_{active + 1}…</p>
                            <p className="mt-2 text-xs text-slate-400">Status: {steps[active]}</p>
                        </div>
                    </div>
                </div>
            </Reveal>
        </SectionShell>
    );
}
