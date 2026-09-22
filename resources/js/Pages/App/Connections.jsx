import { router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const statusColor = {
    active: 'text-emerald-600',
    pending: 'text-amber-600',
    error: 'text-red-600',
    disconnected: 'text-slate-500',
    revoked: 'text-slate-500',
};

export default function Connections({ connections, canConnect }) {
    return (
        <AppLayout title="WhatsApp connections">
            {canConnect && (
                <div className="mb-6 rounded-xl border border-violet-200 bg-violet-50 p-6">
                    <h2 className="font-semibold text-[#0f172a]">Connect WhatsApp Business</h2>
                    <p className="mt-2 text-sm text-slate-600">
                        Use Meta Embedded Signup or OAuth to link a phone number. Messaging charges remain on your Meta
                        business account.
                    </p>
                    <button
                        type="button"
                        onClick={() => router.post('/app/connections/start')}
                        className="mt-4 inline-block rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-medium text-white"
                    >
                        Start connection
                    </button>
                </div>
            )}

            <div className="space-y-4">
                {connections?.length ? (
                    connections.map((c) => (
                        <article key={c.uuid} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span className={`font-medium capitalize ${statusColor[c.connection_status] ?? ''}`}>
                                    {c.connection_status}
                                </span>
                                {c.connection_status !== 'active' && canConnect && (
                                    <button
                                        type="button"
                                        onClick={() => router.post('/app/connections/start')}
                                        className="text-sm text-violet-600 hover:underline"
                                    >
                                        Reconnect
                                    </button>
                                )}
                            </div>
                            {c.display_phone_number && (
                                <p className="mt-2 text-sm">
                                    Phone: <strong>{c.display_phone_number}</strong>
                                </p>
                            )}
                            {c.waba_id && (
                                <p className="text-xs text-slate-500 mt-1">WABA ID: {c.waba_id}</p>
                            )}
                            {c.connected_at && (
                                <p className="text-xs text-slate-400 mt-2">Connected {new Date(c.connected_at).toLocaleString()}</p>
                            )}
                        </article>
                    ))
                ) : (
                    <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                        No connections yet.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
