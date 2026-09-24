import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { copyText } from '../../components/ui/copyText';
import AppLayout from '../../Layouts/AppLayout';

function eventsFromToggles(incoming, statuses) {
    if (incoming && statuses) {
        return ['*'];
    }
    if (incoming) {
        return ['messages'];
    }
    if (statuses) {
        return ['messages'];
    }

    return ['*'];
}

export default function Webhooks({ endpoints, recentDeliveries, flashSecret, lastWebhookTest }) {
    const [showForm, setShowForm] = useState(!endpoints?.length);
    const [incoming, setIncoming] = useState(true);
    const [statuses, setStatuses] = useState(true);
    const [securityOpen, setSecurityOpen] = useState(false);

    const form = useForm({ url: '', events: ['*'], test_after_save: true });

    const submit = (e) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            events: eventsFromToggles(incoming, statuses),
            test_after_save: true,
        }));
        form.post('/app/webhooks', {
            onSuccess: () => {
                setShowForm(false);
                form.reset();
                setIncoming(true);
                setStatuses(true);
            },
        });
    };

    return (
        <AppLayout title="Webhooks">
            <p className="-mt-4 mb-6 max-w-2xl text-sm text-slate-400">
                Tell us where to send incoming WhatsApp messages and delivery updates for your CRM.
            </p>

            {flashSecret && (
                <div className="mb-6 rounded-lg border border-violet-500/40 bg-violet-500/10 p-4">
                    <p className="text-sm font-semibold text-violet-100">Webhook signing secret (shown once)</p>
                    <p className="mt-1 text-xs text-violet-200/80">Store this in your CRM as the webhook secret for signature verification.</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        <code className="flex-1 break-all rounded bg-slate-950 px-3 py-2 text-sm text-white">{flashSecret}</code>
                        <button
                            type="button"
                            onClick={() => copyText(flashSecret)}
                            className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white"
                        >
                            Copy secret
                        </button>
                    </div>
                </div>
            )}

            {lastWebhookTest?.reachable && (
                <div className="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    ✓ Webhook reachable (HTTP {lastWebhookTest.http ?? '—'})
                </div>
            )}

            {(showForm || !endpoints?.length) && (
                <form onSubmit={submit} className="mb-8 rounded-xl border border-slate-700 bg-slate-900/50 p-6">
                    <h2 className="font-semibold text-white">Connect your CRM</h2>
                    <label className="mt-4 block text-sm text-slate-400">
                        Webhook URL
                        <input
                            className="mt-1 w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 font-mono text-sm text-white"
                            value={form.data.url}
                            onChange={(e) => form.setData('url', e.target.value)}
                            placeholder="https://your-crm.com/webhooks/iqpigeon"
                            required
                        />
                    </label>
                    <fieldset className="mt-4">
                        <legend className="text-sm font-medium text-slate-300">Events</legend>
                        <div className="mt-2 space-y-2 text-sm text-slate-400">
                            <label className="flex items-center gap-2">
                                <input type="checkbox" checked={incoming} onChange={(e) => setIncoming(e.target.checked)} />
                                Incoming messages
                            </label>
                            <label className="flex items-center gap-2">
                                <input type="checkbox" checked={statuses} onChange={(e) => setStatuses(e.target.checked)} />
                                Message status updates
                            </label>
                        </div>
                    </fieldset>
                    <div className="mt-6 flex flex-wrap gap-2">
                        <button type="submit" disabled={form.processing} className="rounded-lg bg-violet-600 px-5 py-2 text-sm font-semibold text-white">
                            Save &amp; test webhook
                        </button>
                        {endpoints?.length > 0 && (
                            <button type="button" onClick={() => setShowForm(false)} className="text-sm text-slate-400">
                                Cancel
                            </button>
                        )}
                    </div>
                </form>
            )}

            {endpoints?.length > 0 && !showForm && (
                <div className="mb-4 flex justify-end">
                    <button
                        type="button"
                        onClick={() => setShowForm(true)}
                        className="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800"
                    >
                        Add another URL
                    </button>
                </div>
            )}

            <div className="overflow-hidden rounded-xl border border-slate-700 bg-slate-900/50">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-800/80 text-slate-400">
                        <tr>
                            <th className="px-4 py-2 text-left">URL</th>
                            <th className="px-4 py-2 text-left">Status</th>
                            <th className="px-4 py-2 text-left">Deliveries</th>
                            <th className="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {endpoints?.length ? (
                            endpoints.map((ep) => (
                                <tr key={ep.id} className="border-t border-slate-800">
                                    <td className="max-w-xs truncate px-4 py-3 font-mono text-xs text-slate-300">{ep.url}</td>
                                    <td className="px-4 py-3 text-slate-300">{ep.is_active ? 'Active' : 'Disabled'}</td>
                                    <td className="px-4 py-3 text-slate-400">{ep.deliveries_count}</td>
                                    <td className="space-x-2 px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            className="text-xs text-violet-400 hover:underline"
                                            onClick={() => router.post(`/app/webhooks/${ep.id}/test`)}
                                        >
                                            Test
                                        </button>
                                        <button
                                            type="button"
                                            className="text-xs text-slate-400 hover:underline"
                                            onClick={() => router.patch(`/app/webhooks/${ep.id}`, { is_active: !ep.is_active })}
                                        >
                                            {ep.is_active ? 'Disable' : 'Enable'}
                                        </button>
                                        <button
                                            type="button"
                                            className="text-xs text-red-400 hover:underline"
                                            onClick={() => {
                                                if (confirm('Delete this webhook URL?')) router.delete(`/app/webhooks/${ep.id}`);
                                            }}
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={4} className="px-4 py-10 text-center text-slate-500">
                                    No webhook URL configured yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {recentDeliveries?.length > 0 && (
                <section className="mt-8">
                    <h2 className="mb-2 text-lg font-semibold text-white">Recent deliveries</h2>
                    <ul className="divide-y divide-slate-800 rounded-xl border border-slate-700 bg-slate-900/50 text-sm">
                        {recentDeliveries.map((d) => (
                            <li key={d.id} className="flex flex-wrap justify-between gap-2 px-4 py-2 text-slate-300">
                                <span>{d.event_type}</span>
                                <span className="capitalize">{d.status}</span>
                                <span className="text-slate-500">HTTP {d.response_status ?? '—'}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <div className="mt-8 rounded-xl border border-slate-800 bg-slate-900/30">
                <button
                    type="button"
                    className="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-medium text-slate-300"
                    onClick={() => setSecurityOpen((o) => !o)}
                >
                    Developer security details
                    <span>{securityOpen ? '−' : '+'}</span>
                </button>
                {securityOpen && (
                    <div className="space-y-2 border-t border-slate-800 px-5 py-4 text-sm text-slate-400">
                        <p>
                            Requests include <code className="text-slate-300">X-IQP-Signature</code>,{' '}
                            <code className="text-slate-300">X-IQP-Timestamp</code>,{' '}
                            <code className="text-slate-300">X-IQP-Event-Id</code>, and{' '}
                            <code className="text-slate-300">X-IQP-Request-Id</code>.
                        </p>
                        <p>
                            Signature: <code className="text-slate-300">HMAC-SHA256(secret, timestamp + &quot;.&quot; + raw_body)</code>
                        </p>
                        <p>Validate the timestamp to protect against replay. Use the event ID for idempotent processing.</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
