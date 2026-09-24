import { useMemo, useState } from 'react';
import { copyText } from './ui/copyText';

const tabs = ['curl', 'php', 'javascript', 'python'];

const sampleResponse = `{
  "success": true,
  "data": {
    "message": {
      "id": "msg_…",
      "status": "queued",
      "connection_id": "…"
    }
  },
  "request_id": "req_…"
}`;

function buildSamples({ baseUrl, connectionId }) {
    const conn = connectionId || 'YOUR_CONNECTION_UUID';
    const bodyObj = {
        connection_id: conn,
        to: '+15551234567',
        type: 'text',
        body: 'Hello from your CRM via IQPigeon',
    };
    const body = JSON.stringify(bodyObj, null, 2);

    return {
        curl: `curl -X POST "${baseUrl}/messages" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: msg-001" \\
  -d '${body.replace(/'/g, "'\\''")}'`,
        php: `$response = Http::withToken('YOUR_API_KEY')
    ->withHeaders(['Idempotency-Key' => 'msg-001'])
    ->post('${baseUrl}/messages', ${body.replace(/\n/g, '\n    ')});`,
        javascript: `const res = await fetch('${baseUrl}/messages', {
  method: 'POST',
  headers: {
    Authorization: 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json',
    'Idempotency-Key': 'msg-001',
  },
  body: JSON.stringify(${body}),
});`,
        python: `requests.post(
    "${baseUrl}/messages",
    headers={
        "Authorization": "Bearer YOUR_API_KEY",
        "Content-Type": "application/json",
        "Idempotency-Key": "msg-001",
    },
    json=${body},
)`,
    };
}

export default function FirstApiRequestGuide({ apiBaseUrl, connectionId, compact = false }) {
    const [tab, setTab] = useState('curl');
    const [copied, setCopied] = useState(false);
    const samples = useMemo(() => buildSamples({ baseUrl: apiBaseUrl, connectionId }), [apiBaseUrl, connectionId]);

    const copy = async () => {
        try {
            await copyText(samples[tab]);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            /* ignore */
        }
    };

    return (
        <div className="rounded-xl border border-slate-700 bg-slate-900/60 p-5 shadow-sm">
            <h3 className="text-base font-semibold text-white">Send your first message</h3>
            <p className="mt-2 text-sm text-slate-400">
                Your CRM calls IQPigeon with your API key; we deliver the message to WhatsApp. Replace{' '}
                <code className="rounded bg-slate-800 px-1 text-xs text-violet-300">YOUR_API_KEY</code> with the key you created.
                {connectionId ? (
                    <> Your connection ID is already filled in below.</>
                ) : (
                    <> Connect WhatsApp first so we can fill in your connection ID.</>
                )}
            </p>

            {!compact && (
                <div className="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                    <span className="rounded bg-slate-800 px-2 py-1 text-slate-300">Your CRM</span>
                    <span>↓ POST /api/v1/messages</span>
                    <span className="rounded bg-violet-600/20 px-2 py-1 text-violet-200">IQPigeon</span>
                    <span>↓</span>
                    <span className="rounded bg-emerald-600/20 px-2 py-1 text-emerald-200">WhatsApp</span>
                </div>
            )}

            <div className="mt-4 flex flex-wrap gap-2">
                {tabs.map((key) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setTab(key)}
                        className={`rounded-lg px-3 py-1.5 text-xs font-medium capitalize ${
                            tab === key ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                        }`}
                    >
                        {key === 'javascript' ? 'JavaScript' : key}
                    </button>
                ))}
                <button
                    type="button"
                    onClick={copy}
                    className="ml-auto rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-500"
                >
                    {copied ? 'Copied' : 'Copy code'}
                </button>
            </div>
            <pre className="mt-3 max-h-72 overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-200 ring-1 ring-slate-800">
                {samples[tab]}
            </pre>

            {!compact && (
                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                    <div>
                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Expected success response</p>
                        <pre className="mt-2 max-h-40 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-emerald-200/90 ring-1 ring-slate-800">
                            {sampleResponse}
                        </pre>
                    </div>
                    <p className="text-xs leading-relaxed text-slate-500">
                        Requires an active subscription, API key with <code className="text-slate-400">messages.send</code>, and an active
                        WhatsApp connection. Errors return <code className="text-slate-400">success: false</code> with{' '}
                        <code className="text-slate-400">error.code</code> and <code className="text-slate-400">request_id</code>.
                    </p>
                </div>
            )}
        </div>
    );
}
