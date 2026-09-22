import { useEffect, useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

export default function ObservabilitySection() {
    const reduced = usePrefersReducedMotion();
    const [tick, setTick] = useState(0);

    useEffect(() => {
        if (reduced) {
            return undefined;
        }
        const id = setInterval(() => setTick((t) => t + 1), 4000);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <SectionShell tone="navy" bridge>
            <SectionHeader
                eyebrow="Demo dashboard"
                title="Observability for operators and integrators."
                description="Example metrics and activity — not live production statistics."
            />
            <Reveal delay={100} className="mt-12 iqp-glow-border overflow-hidden rounded-2xl border border-slate-700/40">
                <div className="grid gap-px bg-slate-800/50 lg:grid-cols-4">
                    {[
                        { l: 'Requests (24h)', v: 1240 + (tick % 5) * 3 },
                        { l: 'Webhook events', v: 892 + (tick % 4) * 2 },
                        { l: 'Message status', v: 'delivered' },
                        { l: 'Errors (demo)', v: 3 + (tick % 2) },
                    ].map((m) => (
                        <div key={m.l} className="bg-slate-950/90 p-6">
                            <p className="text-xs uppercase text-slate-500">{m.l}</p>
                            <p className="mt-2 font-mono text-2xl text-white">{m.v}</p>
                        </div>
                    ))}
                </div>
                <div className="grid gap-6 bg-slate-950/80 p-6 lg:grid-cols-2 lg:p-8">
                    <div>
                        <p className="font-mono text-xs text-slate-500">Recent activity (example)</p>
                        <ul className="mt-4 space-y-2 font-mono text-xs text-slate-400">
                            <li>POST /api/v1/messages · 202</li>
                            <li>webhook · message.sent · delivered</li>
                            <li>GET /api/v1/usage · 200</li>
                        </ul>
                    </div>
                    <div>
                        <p className="font-mono text-xs text-slate-500">Latency / state (demo)</p>
                        <div className="mt-4 h-24 rounded-lg bg-slate-900/80 bg-gradient-to-r from-violet-600/20 via-cyan-500/10 to-transparent" />
                    </div>
                </div>
            </Reveal>
        </SectionShell>
    );
}
