import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

function Stat({ label, value, sub }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-2 text-2xl font-bold text-[#0f172a]">{value ?? '—'}</div>
            {sub != null && <div className="mt-1 text-xs text-slate-500">{sub}</div>}
        </div>
    );
}

export default function Usage({ usage, filters }) {
    const [from, setFrom] = useState(filters?.from ?? '');
    const [to, setTo] = useState(filters?.to ?? '');

    const apply = (e) => {
        e.preventDefault();
        router.get('/app/usage', { from, to }, { preserveState: true });
    };

    return (
        <AppLayout title="Usage">
            <form onSubmit={apply} className="mb-6 flex flex-wrap items-end gap-3">
                <label className="text-sm text-slate-600">
                    From
                    <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="ml-2 rounded border px-2 py-1" />
                </label>
                <label className="text-sm text-slate-600">
                    To
                    <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="ml-2 rounded border px-2 py-1" />
                </label>
                <button type="submit" className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                    Apply
                </button>
            </form>

            {!usage ? (
                <p className="text-slate-600">No partner account linked.</p>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Stat label="API requests" value={usage.api_requests} sub={`${usage.api_requests_failed} failed`} />
                        <Stat label="Messages" value={usage.messages} sub={`${usage.messages_failed} failed`} />
                        <Stat
                            label="Webhook deliveries"
                            value={usage.webhook_deliveries}
                            sub={`${usage.webhook_deliveries_failed} failed`}
                        />
                    </div>
                    {usage.usage_records?.length > 0 && (
                        <section className="mt-8">
                            <h2 className="mb-3 text-lg font-semibold">Metered usage records</h2>
                            <ul className="rounded-xl border border-slate-200 bg-white divide-y text-sm">
                                {usage.usage_records.map((r, i) => (
                                    <li key={i} className="flex justify-between px-4 py-3">
                                        <span>{r.metric}</span>
                                        <span className="font-mono">{r.quantity}</span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </>
            )}
        </AppLayout>
    );
}
