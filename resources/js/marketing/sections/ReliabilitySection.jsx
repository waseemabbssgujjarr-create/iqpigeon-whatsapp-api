import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function ReliabilitySection() {
    return (
        <SectionShell tone="grid" bridge id="reliability">
            <SectionHeader
                title="Reliability pipeline with retry branches"
                description="Partner webhook behavior — retryable errors re-enter the queue until attempts are exhausted."
            />
            <Reveal delay={80} className="mt-12 overflow-x-auto">
                <div className="min-w-[720px]">
                    <div className="flex flex-wrap items-center justify-center gap-2 font-mono text-xs">
                        {['Request', 'Auth', 'Validation', 'Idempotency', 'Queue', 'Meta', 'Webhook', 'Delivered'].map((s, i, arr) => (
                            <span key={s} className="flex items-center gap-2">
                                <span className="rounded-lg border border-violet-500/30 bg-slate-900 px-3 py-2 text-slate-200">{s}</span>
                                {i < arr.length - 1 && <span className="text-violet-500">→</span>}
                            </span>
                        ))}
                    </div>
                    <div className="relative mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            { c: '408', a: 'retry → queue' },
                            { c: '429', a: 'retry → queue' },
                            { c: '5xx', a: 'retry → queue' },
                            { c: '4xx', a: 'failed (final)' },
                        ].map((b) => (
                            <div key={b.c} className="iqp-glass rounded-xl p-4">
                                <p className="font-mono text-lg text-violet-300">{b.c}</p>
                                <p className="mt-1 text-sm text-slate-400">{b.a}</p>
                            </div>
                        ))}
                    </div>
                    <p className="mt-8 text-center font-mono text-[10px] text-slate-600">timeout · connection errors follow the same retry path (demo diagram)</p>
                </div>
            </Reveal>
        </SectionShell>
    );
}
