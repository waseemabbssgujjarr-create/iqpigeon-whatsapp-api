import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';

function copyText(text) {
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text);
    }
}

export default function ApiKeys({ apiKeys, scopes, flashSecret }) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        label: '',
        scopes: [],
        expires_at: '',
    });

    const toggleScope = (name) => {
        const next = form.data.scopes.includes(name)
            ? form.data.scopes.filter((s) => s !== name)
            : [...form.data.scopes, name];
        form.setData('scopes', next);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post('/app/api-keys', {
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
        });
    };

    return (
        <AppLayout title="API keys">
            {flashSecret && (
                <div className="mb-6 rounded-lg border-2 border-violet-400 bg-violet-50 p-4">
                    <p className="text-sm font-semibold text-violet-900">Copy your API key now — it will not be shown again.</p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <code className="flex-1 break-all rounded bg-white px-3 py-2 text-sm">{flashSecret}</code>
                        <button
                            type="button"
                            onClick={() => copyText(flashSecret)}
                            className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white"
                        >
                            Copy
                        </button>
                    </div>
                </div>
            )}

            <div className="mb-4 flex justify-end">
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700"
                >
                    Create API key
                </button>
            </div>

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <form onSubmit={submit} className="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                        <h2 className="text-lg font-semibold text-[#0f172a]">New API key</h2>
                        <label className="mt-4 block text-sm text-slate-600">
                            Label
                            <input
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                                value={form.data.label}
                                onChange={(e) => form.setData('label', e.target.value)}
                                required
                            />
                        </label>
                        <fieldset className="mt-4">
                            <legend className="text-sm font-medium text-slate-700">Scopes</legend>
                            <div className="mt-2 max-h-48 space-y-2 overflow-y-auto">
                                {scopes?.map((s) => (
                                    <label key={s.id} className="flex items-start gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.data.scopes.includes(s.name)}
                                            onChange={() => toggleScope(s.name)}
                                        />
                                        <span>
                                            <code className="text-violet-700">{s.name}</code>
                                            <span className="block text-slate-500">{s.description}</span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                        <label className="mt-4 block text-sm text-slate-600">
                            Expires (optional)
                            <input
                                type="datetime-local"
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                                value={form.data.expires_at}
                                onChange={(e) => form.setData('expires_at', e.target.value)}
                            />
                        </label>
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={() => setOpen(false)} className="rounded-lg px-4 py-2 text-sm text-slate-600">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing || form.data.scopes.length === 0}
                                className="rounded-lg bg-[#0f172a] px-4 py-2 text-sm text-white disabled:opacity-50"
                            >
                                Create
                            </button>
                        </div>
                    </form>
                </div>
            )}

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Label</th>
                            <th className="px-4 py-3">Prefix</th>
                            <th className="px-4 py-3">Scopes</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {apiKeys?.length ? (
                            apiKeys.map((key) => {
                                const revoked = !!key.revoked_at;
                                const expired = key.expires_at && new Date(key.expires_at) < new Date();
                                const status = revoked ? 'Revoked' : expired ? 'Expired' : 'Active';
                                return (
                                    <tr key={key.uuid} className="border-t border-slate-100">
                                        <td className="px-4 py-3 font-medium">{key.label}</td>
                                        <td className="px-4 py-3 font-mono text-xs">{key.prefix}…</td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                {key.scopes?.map((s) => (
                                                    <span key={s} className="rounded bg-slate-100 px-1.5 py-0.5 text-xs">
                                                        {s}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={
                                                    status === 'Active'
                                                        ? 'text-emerald-600'
                                                        : 'text-amber-600'
                                                }
                                            >
                                                {status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {!revoked && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        if (window.confirm('Revoke this API key? This cannot be undone.')) {
                                                            router.delete(`/app/api-keys/${key.uuid}`);
                                                        }
                                                    }}
                                                    className="text-sm text-red-600 hover:underline"
                                                >
                                                    Revoke
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan={5} className="px-4 py-12 text-center text-slate-500">
                                    No API keys yet. Create one after your subscription is active.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
