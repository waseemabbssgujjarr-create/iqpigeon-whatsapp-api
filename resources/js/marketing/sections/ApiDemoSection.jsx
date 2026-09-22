import { useEffect, useState } from 'react';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

const snippets = {
    curl: `curl -X POST https://whatsappapi.iqpigeon.com/api/v1/messages \\
  -H "Authorization: Bearer iqp_live_demo_xxxxxxxx" \\
  -H "Idempotency-Key: msg-001" \\
  -H "Content-Type: application/json" \\
  -d '{
    "connection_id": "conn_demo_uuid",
    "to": "+15551234567",
    "type": "text",
    "body": "Appointment confirmed for tomorrow."
  }'`,
    php: `$response = Http::withToken('iqp_live_demo_xxxxxxxx')
    ->withHeaders(['Idempotency-Key' => 'msg-001'])
    ->post('https://whatsappapi.iqpigeon.com/api/v1/messages', [
        'connection_id' => 'conn_demo_uuid',
        'to' => '+15551234567',
        'type' => 'text',
        'body' => 'Appointment confirmed for tomorrow.',
    ]);`,
    node: `const res = await fetch('https://whatsappapi.iqpigeon.com/api/v1/messages', {
  method: 'POST',
  headers: {
    Authorization: 'Bearer iqp_live_demo_xxxxxxxx',
    'Idempotency-Key': 'msg-001',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    connection_id: 'conn_demo_uuid',
    to: '+15551234567',
    type: 'text',
    body: 'Appointment confirmed for tomorrow.',
  }),
});`,
    python: `requests.post(
    "https://whatsappapi.iqpigeon.com/api/v1/messages",
    headers={
        "Authorization": "Bearer iqp_live_demo_xxxxxxxx",
        "Idempotency-Key": "msg-001",
    },
    json={
        "connection_id": "conn_demo_uuid",
        "to": "+15551234567",
        "type": "text",
        "body": "Appointment confirmed for tomorrow.",
    },
)`,
};

const stages = ['Sending request…', '200 OK', 'Message ID assigned', 'Queued', 'Sent', 'Delivered'];

export default function ApiDemoSection() {
    const [tab, setTab] = useState('curl');
    const [stage, setStage] = useState(0);
    const reduced = usePrefersReducedMotion();

    useEffect(() => {
        if (reduced) {
            setStage(stages.length - 1);
            return undefined;
        }
        const id = setInterval(() => setStage((s) => (s + 1) % stages.length), 2200);
        return () => clearInterval(id);
    }, [reduced]);

    return (
        <section className="py-24 sm:py-28" id="api-demo">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Your CRM. Our infrastructure.</h2>
                    <p className="mt-4 max-w-2xl text-slate-400">
                        Dot-notation scopes, idempotent POSTs, and tenant-isolated connections — illustrated with demo credentials only.
                    </p>
                </Reveal>
                <Reveal delay={100} className="mt-10 iqp-glow-border overflow-hidden rounded-2xl border border-slate-800 bg-[#0a0f18]">
                    <div className="flex flex-wrap gap-1 border-b border-slate-800 p-2" role="tablist">
                        {Object.keys(snippets).map((key) => (
                            <button
                                key={key}
                                type="button"
                                role="tab"
                                aria-selected={tab === key}
                                className={`rounded-lg px-4 py-2 font-mono text-xs uppercase ${
                                    tab === key ? 'bg-violet-600/30 text-white' : 'text-slate-500 hover:text-slate-300'
                                }`}
                                onClick={() => setTab(key)}
                            >
                                {key === 'node' ? 'Node.js' : key}
                            </button>
                        ))}
                    </div>
                    <pre className="max-h-64 overflow-auto p-6 font-mono text-xs leading-relaxed text-slate-300 sm:text-sm">
                        {snippets[tab]}
                    </pre>
                    <div className="border-t border-slate-800 bg-slate-950/60 px-6 py-4">
                        <p className="font-mono text-xs text-slate-500">POST /api/v1/messages</p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {stages.map((label, i) => (
                                <span
                                    key={label}
                                    className={`rounded-full px-3 py-1 font-mono text-[10px] uppercase tracking-wide ${
                                        i <= stage ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-slate-800 text-slate-600'
                                    }`}
                                >
                                    {label}
                                </span>
                            ))}
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
