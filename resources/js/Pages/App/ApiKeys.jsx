import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { copyText } from '../../components/ui/copyText';
import AppLayout from '../../Layouts/AppLayout';

const recommendedScopes = [
    'messages.send',
    'messages.read',
    'connections.read',
    'webhooks.read',
    'usage.read',
];

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

    const applyRecommended = () => {
        const available = recommendedScopes.filter((name) => scopes?.some((s) => s.name === name));
        form.setData('scopes', available);
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
            <p className="-mt-4 mb-6 max-w-2xl text-sm text-slate-400">
                Your CRM uses an API key to authenticate requests to IQPigeon. Create a key on your server — never expose it in browser
                JavaScript.
            </p>

            {flashSecret && (
                <div className="mb-6 rounded-lg border-2 border-violet-500/50 bg-violet-500/10 p-4">
                    <p className="text-sm font-semibold text-violet-100">Copy your API key now — it will not be shown again.</p>
                    <p className="mt-1 text-xs text-violet-200/80">Keep this key on your server. Never expose it in browser-side JavaScript.</p>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <code className="flex-1 break-all rounded bg-slate-950 px-3 py-2 text-sm text-white">{flashSecret}</code>
                        <button
                            type="button"
                            onClick={() => copyText(flashSecret)}
                            className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
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
                    className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
                >
                    Create API key
                </button>
            </div>

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <form onSubmit={submit} className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-xl">
                        <h2 className="text-lg font-semibold text-white">Create API key</h2>
                        <label className="mt-4 block text-sm text-slate-400">
                            Key name
                            <input
                                className="mt-1 w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-white"
                                placeholder="Production CRM"
                                value={form.data.label}
                                onChange={(e) => form.setData('label', e.target.value)}
                                required
                            />
                        </label>
                        <div className="mt-3">
                            <button type="button" onClick={applyRecommended} className="text-xs text-violet-400 hover:underline">
                                Use recommended CRM scopes
                            </button>
                        </div>
                        <fieldset className="mt-4">
                            <legend className="text-sm font-medium text-slate-300">Scopes</legend>
                            <div className="mt-2 max-h-48 space-y-2 overflow-y-auto">
                                {scopes?.map((s) => (
                                    <label key={s.id} className="flex items-start gap-2 text-sm text-slate-400">
                                        <input
                                            type="checkbox"
                                            checked={form.data.scopes.includes(s.name)}
                                            onChange={() => toggleScope(s.name)}
                                        />
                                        <span>
                                            <code className="text-violet-300">{s.name}</code>
                                            <span className="block text-slate-500">{s.description}</span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>
                        <label className="mt-4 block text-sm text-slate-400">
                            Expires (optional)
                            <input
                                type="datetime-local"
                                className="mt-1 w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-white"
                                value={form.data.expires_at}
                                onChange={(e) => form.setData('expires_at', e.target.value)}
                            />
                        </label>
                        <div className="mt-6 flex justify-end gap-2">
                            <button type="button" onClick={() => setOpen(false)} className="rounded-lg px-4 py-2 text-sm text-slate-400">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing || form.data.scopes.length === 0}
                                className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                Create API key
                            </button>
                        </div>
                    </form>
                </div>
            )}

            <div className="overflow-hidden rounded-xl border border-slate-700 bg-slate-900/50">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-800/80 text-left text-slate-400">
                        <tr>
                            <th className="px-4 py-3">Name</th>
                            <th className="px-4 py-3">Key</th>
                            <th className="px-4 py-3">Scopes</th>
                            <th className="px-4 py-3">Created</th>
                            <th className="px-4 py-3">Last used</th>
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
                                    <tr key={key.uuid} className="border-t border-slate-800">
                                        <td className="px-4 py-3 font-medium text-white">{key.label}</td>
                                        <td className="px-4 py-3 font-mono text-xs text-slate-400">{key.prefix}…</td>
                                        <td className="px-4 py-3">
                                            <div className="flex max-w-xs flex-wrap gap-1">
                                                {key.scopes?.map((s) => (
                                                    <span key={s} className="rounded bg-slate-800 px-1.5 py-0.5 text-xs text-slate-300">
                                                        {s}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-400">{key.created_at ? new Date(key.created_at).toLocaleDateString() : '—'}</td>
                                        <td className="px-4 py-3 text-slate-400">
                                            {key.last_used_at ? new Date(key.last_used_at).toLocaleString() : 'Never'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={status === 'Active' ? 'text-emerald-400' : 'text-amber-400'}>{status}</span>
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
                                                    className="text-sm text-red-400 hover:underline"
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
                                <td colSpan={7} className="px-4 py-12 text-center text-slate-500">
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
