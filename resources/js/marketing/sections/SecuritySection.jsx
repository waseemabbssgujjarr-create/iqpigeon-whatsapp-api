import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

const layers = [
    'API Key',
    'Hash storage',
    'Scope enforcement',
    'Request validation',
    'Idempotency',
    'Encrypted credentials',
    'Webhook signature',
    'SSRF protection',
    'Audit trail',
];

export default function SecuritySection() {
    return (
        <SectionShell tone="ink" bridge id="security">
            <SectionHeader title="Security stack" description="Layered controls — no compliance certifications claimed." />
            <div className="mx-auto mt-14 max-w-xl">
                {layers.map((layer, i) => (
                    <Reveal key={layer} delay={i * 50}>
                        <div className="relative flex items-center gap-4 border-l-2 border-violet-500/40 py-4 pl-6">
                            <span className="font-mono text-xs text-violet-400">{String(i + 1).padStart(2, '0')}</span>
                            <span className="text-lg text-slate-200">{layer}</span>
                            {i < layers.length - 1 && (
                                <span className="absolute -bottom-1 left-[11px] text-violet-500/50" aria-hidden="true">
                                    ↓
                                </span>
                            )}
                        </div>
                    </Reveal>
                ))}
            </div>
        </SectionShell>
    );
}
