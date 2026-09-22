import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

export default function Webhooks({ endpoints, recentDeliveries, flashSecret }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ url: '', events: ['*'] });

    return (
        <AppLayout title="Webhooks">
            {flashSecret && (
                <div className="mb-6 rounded-lg border border-violet-300 bg-violet-50 p-4">
                    <p className="text-sm font-semibold">Endpoint secret (shown once)</p>
                    <code className="mt-2 block break-all text-sm">{flashSecret}</code>
                </div>
            )}

            <div className="mb-4 flex justify-end">
                <button type="button" onClick={() => setOpen(true)} className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                    Add endpoint
                </button>
            </div>

            {open && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/app/webhooks', { onSuccess: () => setOpen(false) });
                    }}
                    className="mb-6 rounded-xl border bg-white p-4"
                >
                    <label className="block text-sm">
                        HTTPS URL
                        <input
                            className="mt-1 w-full rounded border px-3 py-2"
                            value={form.data.url}
                            onChange={(e) => form.setData('url', e.target.value)}
                            placeholder="https://example.com/webhooks/iqpigeon"
                            required
                        />
                    </label>
                    <div className="mt-3 flex gap-2">
                        <button type="submit" className="rounded bg-[#0f172a] px-3 py-2 text-sm text-white">
                            Save
                        </button>
                        <button type="button" onClick={() => setOpen(false)} className="text-sm text-slate-500">
                            Cancel
                        </button>
                    </div>
                </form>
            )}

            <div className="overflow-hidden rounded-xl border bg-white shadow-sm">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-slate-500">
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
                                <tr key={ep.id} className="border-t">
                                    <td className="max-w-xs truncate px-4 py-3 font-mono text-xs">{ep.url}</td>
                                    <td className="px-4 py-3">{ep.is_active ? 'Active' : 'Disabled'}</td>
                                    <td className="px-4 py-3">{ep.deliveries_count}</td>
                                    <td className="px-4 py-3 text-right space-x-2">
                                        <button
                                            type="button"
                                            className="text-violet-600 text-xs"
                                            onClick={() => router.post(`/app/webhooks/${ep.id}/test`)}
                                        >
                                            Test
                                        </button>
                                        <button
                                            type="button"
                                            className="text-xs text-slate-600"
                                            onClick={() =>
                                                router.patch(`/app/webhooks/${ep.id}`, { is_active: !ep.is_active })
                                            }
                                        >
                                            {ep.is_active ? 'Disable' : 'Enable'}
                                        </button>
                                        <button
                                            type="button"
                                            className="text-xs text-red-600"
                                            onClick={() => {
                                                if (confirm('Delete endpoint?')) router.delete(`/app/webhooks/${ep.id}`);
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
                                    No webhook endpoints configured.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {recentDeliveries?.length > 0 && (
                <section className="mt-8">
                    <h2 className="mb-2 text-lg font-semibold">Recent deliveries</h2>
                    <ul className="rounded-xl border bg-white divide-y text-sm">
                        {recentDeliveries.map((d) => (
                            <li key={d.id} className="flex flex-wrap justify-between gap-2 px-4 py-2">
                                <span>{d.event_type}</span>
                                <span className="capitalize">{d.status}</span>
                                <span className="text-slate-500">HTTP {d.response_status ?? '—'}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
