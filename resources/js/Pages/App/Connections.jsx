import { router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const statusLabel = {
    active: { text: 'Connected', className: 'text-emerald-400' },
    pending: { text: 'Setup in progress', className: 'text-amber-400' },
    error: { text: 'Needs attention', className: 'text-red-400' },
    disconnected: { text: 'Removed', className: 'text-slate-500' },
    revoked: { text: 'Revoked', className: 'text-slate-500' },
};

function formatWhen(iso) {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString();
}

export default function Connections({ connections, canConnect }) {
    const active = connections?.filter((c) => c.connection_status === 'active') ?? [];
    const drafts = connections?.filter((c) => ['pending', 'error'].includes(c.connection_status)) ?? [];
    const inactive = connections?.filter((c) => ['disconnected', 'revoked'].includes(c.connection_status)) ?? [];

    return (
        <AppLayout title="Connect WhatsApp">
            <p className="-mt-4 mb-8 max-w-2xl text-sm text-slate-400">
                Link a WhatsApp Business number through Meta. Your CRM sends messages through IQPigeon — Meta bills messaging on your Meta
                account.
            </p>

            {canConnect && (
                <div className="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-violet-500/30 bg-violet-500/5 p-6">
                    <div>
                        <h2 className="font-semibold text-white">Connect a WhatsApp Business number</h2>
                        <p className="mt-1 text-sm text-slate-400">Opens Meta signup to link your phone number.</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.post('/app/connections/start')}
                        className="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500"
                    >
                        + Connect another WhatsApp number
                    </button>
                </div>
            )}

            {active.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-sm font-medium uppercase tracking-wide text-slate-500">Active numbers</h2>
                    <div className="space-y-3">
                        {active.map((c) => (
                            <article key={c.uuid} className="rounded-xl border border-emerald-500/20 bg-slate-900/50 p-5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-lg font-semibold text-white">{c.display_phone_number ?? 'WhatsApp connected'}</p>
                                        <p className={`mt-1 text-sm ${statusLabel.active.className}`}>{statusLabel.active.text}</p>
                                        {c.waba_id && <p className="mt-2 font-mono text-xs text-slate-500">WABA {c.waba_id}</p>}
                                        <p className="mt-2 text-xs text-slate-500">Last updated {formatWhen(c.updated_at)}</p>
                                    </div>
                                    <button
                                        type="button"
                                        className="text-sm text-red-400 hover:underline"
                                        onClick={() => {
                                            if (confirm('Disconnect this WhatsApp number from IQPigeon?')) {
                                                router.delete(`/app/connections/${c.uuid}`);
                                            }
                                        }}
                                    >
                                        Disconnect
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            )}

            {drafts.length > 0 && (
                <section className="mb-8">
                    <h2 className="mb-3 text-sm font-medium uppercase tracking-wide text-slate-500">Incomplete setup</h2>
                    <div className="space-y-3">
                        {drafts.map((c) => {
                            const st = statusLabel[c.connection_status] ?? statusLabel.pending;
                            return (
                                <article key={c.uuid} className="rounded-xl border border-amber-500/20 bg-slate-900/50 p-5">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="max-w-xl">
                                            <p className="font-medium text-white">
                                                {c.display_phone_number ?? `Draft started ${formatWhen(c.created_at)}`}
                                            </p>
                                            <p className={`mt-1 text-sm ${st.className}`}>{st.text}</p>
                                            {c.setup_hint && <p className="mt-2 text-sm text-slate-400">{c.setup_hint}</p>}
                                            <p className="mt-2 text-xs text-slate-500">Last updated {formatWhen(c.updated_at)}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {c.can_resume_setup && (
                                                <button
                                                    type="button"
                                                    onClick={() => router.post(`/app/connections/${c.uuid}/continue`)}
                                                    className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white"
                                                >
                                                    Continue setup
                                                </button>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (confirm('Remove this draft connection?')) {
                                                        router.delete(`/app/connections/${c.uuid}`);
                                                    }
                                                }}
                                                className="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                </section>
            )}

            {active.length === 0 && drafts.length === 0 && (
                <p className="rounded-xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">
                    No WhatsApp numbers connected yet. Use the button above to start Meta signup.
                </p>
            )}

            {inactive.length > 0 && (
                <details className="mt-8 rounded-xl border border-slate-800 bg-slate-900/30">
                    <summary className="cursor-pointer px-5 py-3 text-sm text-slate-400">Past connections ({inactive.length})</summary>
                    <ul className="divide-y divide-slate-800 border-t border-slate-800 px-5">
                        {inactive.map((c) => (
                            <li key={c.uuid} className="py-3 text-sm text-slate-500">
                                {c.display_phone_number ?? c.uuid.slice(0, 8)} — {c.connection_status} · {formatWhen(c.disconnected_at)}
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </AppLayout>
    );
}
