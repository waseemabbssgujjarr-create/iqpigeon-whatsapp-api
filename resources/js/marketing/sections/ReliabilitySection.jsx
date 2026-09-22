import Reveal from '../components/Reveal';

const pipeline = ['Request', 'Authentication', 'Validation', 'Idempotency', 'Queue', 'Meta', 'Webhook', 'Delivery'];

const branches = [
    { code: '429', action: 'retry with backoff' },
    { code: '408', action: 'retry with backoff' },
    { code: '5xx', action: 'retry with backoff' },
    { code: '4xx', action: 'failed (no retry)' },
];

export default function ReliabilitySection() {
    return (
        <section className="py-24 sm:py-28" id="reliability">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Reliability pipeline</h2>
                    <p className="mt-4 max-w-2xl text-slate-400">
                        Partner webhooks use bounded retries, persisted attempt history, and clear final states — product behavior,
                        not simulated monitoring.
                    </p>
                </Reveal>
                <Reveal delay={100} className="mt-10 overflow-x-auto pb-2">
                    <div className="flex min-w-max gap-2">
                        {pipeline.map((step, i) => (
                            <div key={step} className="flex items-center gap-2">
                                <div className="rounded-lg border border-violet-500/30 bg-slate-900/80 px-3 py-2 font-mono text-xs text-slate-200">
                                    {step}
                                </div>
                                {i < pipeline.length - 1 && <span className="text-violet-500">→</span>}
                            </div>
                        ))}
                    </div>
                </Reveal>
                <Reveal delay={160} className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {branches.map((b) => (
                        <div key={b.code} className="iqp-glass rounded-xl p-4">
                            <p className="font-mono text-lg text-violet-300">{b.code}</p>
                            <p className="mt-1 text-sm text-slate-400">→ {b.action}</p>
                        </div>
                    ))}
                </Reveal>
            </div>
        </section>
    );
}
