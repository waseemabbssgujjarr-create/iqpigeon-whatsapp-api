import { useMemo, useState } from 'react';

const tabs = ['curl', 'php', 'javascript', 'python'];

function buildSamples({ baseUrl, connectionId }) {
    const conn = connectionId || 'YOUR_CONNECTION_UUID';
    const body = JSON.stringify(
        {
            connection_id: conn,
            to: '+15551234567',
            type: 'text',
            body: 'Hello from your CRM via IQPigeon',
        },
        null,
        2,
    );

    return {
        curl: `curl -X POST "${baseUrl}/messages" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: msg-001" \\
  -d '${body.replace(/'/g, "'\\''")}'`,
        php: `$response = Http::withToken('YOUR_API_KEY')
    ->withHeaders(['Idempotency-Key' => 'msg-001'])
    ->post('${baseUrl}/messages', ${body});`,
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

export default function FirstApiRequestGuide({ apiBaseUrl, connectionId }) {
    const [tab, setTab] = useState('curl');
    const samples = useMemo(() => buildSamples({ baseUrl: apiBaseUrl, connectionId }), [apiBaseUrl, connectionId]);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(samples[tab]);
        } catch {
            /* ignore */
        }
    };

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 className="text-sm font-semibold text-[#0f172a]">First API request — POST /api/v1/messages</h3>
            <p className="mt-2 text-sm text-slate-600">
                Call this from your CRM backend. Replace <code className="text-xs">YOUR_API_KEY</code> and{' '}
                <code className="text-xs">connection_id</code>. Requires scopes <code className="text-xs">messages.send</code> and an
                active subscription.
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
                {tabs.map((key) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setTab(key)}
                        className={`rounded-lg px-3 py-1.5 text-xs font-medium capitalize ${
                            tab === key ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600'
                        }`}
                    >
                        {key === 'javascript' ? 'JavaScript' : key}
                    </button>
                ))}
                <button type="button" onClick={copy} className="ml-auto rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700">
                    Copy
                </button>
            </div>
            <pre className="mt-3 max-h-64 overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-relaxed text-slate-200">{samples[tab]}</pre>
            <p className="mt-3 text-xs text-slate-500">
                Success responses include message metadata; errors return <code>success: false</code>, an <code>error.code</code>, and{' '}
                <code>request_id</code> for support.
            </p>
        </div>
    );
}
