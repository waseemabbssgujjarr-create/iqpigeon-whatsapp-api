import { useEffect, useState } from 'react';
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

    const requests = 1240 + (tick % 5) * 3;
    const webhooks = 892 + (tick % 4) * 2;
    const queued = 12 + (tick % 3);
    const failed = 3 + (tick % 2);
    const success = (99.2 + (tick % 3) * 0.05).toFixed(1);

    return (
        <section className="py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-3xl font-semibold text-white sm:text-4xl">Observability</h2>
                        <p className="mt-2 text-sm text-amber-200/80">Example dashboard — demo data only, not live production metrics.</p>
                    </div>
                </Reveal>
                <Reveal delay={80} className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {[
                        { label: 'Requests (24h)', value: requests.toLocaleString() },
                        { label: 'Webhooks', value: webhooks.toLocaleString() },
                        { label: 'Queued', value: String(queued) },
                        { label: 'Failed', value: String(failed) },
                        { label: 'Success rate', value: `${success}%` },
                    ].map((m) => (
                        <div key={m.label} className="iqp-glass rounded-xl p-5">
                            <p className="text-xs uppercase tracking-wide text-slate-500">{m.label}</p>
                            <p className="mt-2 font-mono text-2xl font-semibold text-white">{m.value}</p>
                        </div>
                    ))}
                </Reveal>
                <Reveal delay={120} className="mt-6 iqp-glass rounded-xl p-6">
                    <p className="font-mono text-xs uppercase text-slate-500">Recent activity (example)</p>
                    <ul className="mt-4 space-y-2 font-mono text-xs text-slate-400">
                        <li>POST /api/v1/messages · 202 · partner_acme</li>
                        <li>POST partner webhook · delivered · message.sent</li>
                        <li>GET /api/v1/usage · 200 · usage.read</li>
                        <li>POST /api/v1/webhooks · 201 · webhooks.write</li>
                    </ul>
                </Reveal>
            </div>
        </section>
    );
}
