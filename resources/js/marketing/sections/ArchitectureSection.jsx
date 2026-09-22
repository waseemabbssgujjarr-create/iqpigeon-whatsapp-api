import { useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

const nodes = [
    'CRM PLATFORM',
    'IQPigeon API',
    'Authentication',
    'Validation',
    'Idempotency',
    'Queue',
    'Meta / WhatsApp',
    'Partner Webhook',
    'CRM',
];

const callouts = ['API keys', 'Usage', 'Retry', 'Audit', 'Webhook signing'];

export default function ArchitectureSection() {
    const [active, setActive] = useState(2);

    return (
        <SectionShell tone="ink" bridge id="architecture">
            <SectionHeader
                title="One infrastructure layer between your CRM and WhatsApp."
                description="Click a stage to highlight the path. CONNECT · BUILD · RECEIVE flows share this core pipeline."
            />
            <div className="mt-14 grid gap-10 xl:grid-cols-[1fr_320px]">
                <Reveal className="iqp-glow-border overflow-x-auto rounded-2xl iqp-glass p-8 lg:p-12">
                    <div className="flex min-w-[280px] flex-col items-center gap-2">
                        {nodes.map((node, i) => (
                            <button
                                key={node}
                                type="button"
                                onClick={() => setActive(i)}
                                className={`w-full max-w-md rounded-xl border px-6 py-4 font-mono text-sm transition ${
                                    active === i
                                        ? 'border-violet-500/60 bg-violet-500/15 text-white shadow-[0_0_32px_rgb(139_92_246_/_0.2)]'
                                        : 'border-slate-700/50 text-slate-400 hover:border-slate-500'
                                }`}
                            >
                                {node}
                            </button>
                        ))}
                    </div>
                </Reveal>
                <Reveal delay={80} className="space-y-3">
                    <p className="font-mono text-xs uppercase tracking-widest text-cyan-400">Side systems</p>
                    {callouts.map((c) => (
                        <div key={c} className="rounded-lg border border-slate-700/50 bg-slate-950/60 px-4 py-3 text-sm text-slate-300">
                            {c}
                        </div>
                    ))}
                </Reveal>
            </div>
        </SectionShell>
    );
}
