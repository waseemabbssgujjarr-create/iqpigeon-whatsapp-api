import { useEffect, useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

const snippets = {
    curl: `curl -X POST .../api/v1/messages \\
  -H "Authorization: Bearer iqp_live_demo_xxxxxxxx" \\
  -H "Idempotency-Key: msg-001" \\
  -d '{"connection_id":"conn_demo","to":"+15551234567","type":"text","body":"Hello"}'`,
    php: `Http::withToken('iqp_live_demo_xxxxxxxx')
  ->post('.../api/v1/messages', [...]);`,
    node: `fetch('.../api/v1/messages', { method: 'POST', headers: {...} });`,
    python: `requests.post(".../api/v1/messages", json={...})`,
};

const stages = ['Request', '200 OK', 'Queued', 'Sent', 'Delivered', 'Webhook'];

export default function ApiDemoSection() {
    const [tab, setTab] = useState('curl');
    const [stage, setStage] = useState(0);
    const reduced = usePrefersReducedMotion();

    useEffect(() => {
        if (reduced) {
            return undefined;
        }
        const id = setInterval(() => setStage((s) => (s + 1) % stages.length), 2200);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <SectionShell tone="glow" bridge id="api-demo">
            <SectionHeader title="Your CRM. Our infrastructure." description="Demo credentials only — never use example keys in production." />
            <Reveal delay={80} className="mt-12 grid gap-6 lg:grid-cols-[2fr_3fr]">
                <div className="iqp-glow-border overflow-hidden rounded-2xl bg-[#0a0f18]">
                    <div className="flex flex-wrap gap-1 border-b border-slate-800 p-2" role="tablist">
                        {Object.keys(snippets).map((key) => (
                            <button
                                key={key}
                                type="button"
                                role="tab"
                                aria-selected={tab === key}
                                className={`rounded-lg px-3 py-2 font-mono text-xs uppercase ${
                                    tab === key ? 'bg-violet-600/30 text-white' : 'text-slate-500'
                                }`}
                                onClick={() => setTab(key)}
                            >
                                {key === 'node' ? 'Node.js' : key}
                            </button>
                        ))}
                    </div>
                    <pre className="max-h-72 overflow-auto p-6 font-mono text-xs leading-relaxed text-slate-300">{snippets[tab]}</pre>
                </div>
                <div className="iqp-product-panel flex flex-col justify-between p-6 lg:p-8">
                    <div>
                        <p className="font-mono text-xs text-slate-500">Response &amp; lifecycle (demo)</p>
                        <p className="mt-4 font-mono text-2xl text-emerald-400">{stages[stage]}</p>
                        <p className="mt-2 font-mono text-sm text-slate-400">msg_demo_{String(stage).padStart(2, '0')}</p>
                    </div>
                    <div className="mt-8 flex flex-wrap gap-2">
                        {stages.map((label, i) => (
                            <span
                                key={label}
                                className={`rounded-full px-3 py-1 font-mono text-[10px] uppercase ${
                                    i <= stage ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-slate-800 text-slate-600'
                                }`}
                            >
                                {label}
                            </span>
                        ))}
                    </div>
                </div>
            </Reveal>
        </SectionShell>
    );
}
