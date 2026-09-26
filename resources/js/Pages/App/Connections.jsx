import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Layouts/AppLayout';
import { launchCoexistenceEmbeddedSignup } from '../../lib/metaEmbeddedSignup';

const statusLabel = {
    active: { text: 'Connected', className: 'text-emerald-400' },
    pending: { text: 'Setup in progress', className: 'text-amber-400' },
    meta_linked: { text: 'Meta linked — one step left', className: 'text-amber-300' },
    coexistence_linked: { text: 'Connected to WhatsApp Business App', className: 'text-emerald-300' },
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
    const { errors, flash, metaEmbeddedSignup } = usePage().props;
    const statusMessage = typeof flash?.status === 'string' ? flash.status : null;
    const connectError = typeof errors?.connect === 'string' ? errors.connect : null;
    const [pendingAction, setPendingAction] = useState(null);
    const [registrationPin, setRegistrationPin] = useState({});

    const active = connections?.filter((c) => c.connection_status === 'active') ?? [];
    const drafts = connections?.filter((c) => ['pending', 'error'].includes(c.connection_status)) ?? [];
    const inactive = connections?.filter((c) => ['disconnected', 'revoked'].includes(c.connection_status)) ?? [];

    const isBusy = pendingAction !== null;

    useEffect(() => {
        const payload = flash?.coexistence_onboarding;
        if (!payload?.connection_uuid || !payload?.session_token || !metaEmbeddedSignup?.app_id) {
            return;
        }

        let cancelled = false;

        (async () => {
            setPendingAction('coexistence-sdk');
            try {
                const { code, embeddedEvent } = await launchCoexistenceEmbeddedSignup({
                    appId: metaEmbeddedSignup.app_id,
                    graphVersion: metaEmbeddedSignup.graph_version,
                    // v4 standard Login for Business config + featureType extras (see docs/COEXISTENCE-ONBOARDING.md).
                    configId: metaEmbeddedSignup.config_id_standard,
                    oauthRedirectUri: metaEmbeddedSignup.oauth_redirect_uri,
                });

                if (cancelled) {
                    return;
                }

                router.post(
                    `/app/connections/${payload.connection_uuid}/embedded-signup/complete`,
                    {
                        code,
                        session_token: payload.session_token,
                        embedded_signup_event: embeddedEvent ?? null,
                    },
                    { onFinish: () => setPendingAction(null) },
                );
            } catch (error) {
                if (!cancelled) {
                    setPendingAction(null);
                    router.reload({ only: ['flash', 'errors'] });
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [flash?.coexistence_onboarding, metaEmbeddedSignup?.app_id]);

    const postOnboarding = (url, actionKey) => {
        if (isBusy) {
            return;
        }

        setPendingAction(actionKey);
        router.post(url, {}, {
            onFinish: () => setPendingAction(null),
        });
    };

    return (
        <AppLayout title="Connect WhatsApp">
            <p className="-mt-4 mb-4 max-w-2xl text-sm text-slate-400">
                Link a WhatsApp Business number through Meta. Your CRM sends messages through IQPigeon — Meta bills messaging on your Meta
                account.
            </p>
            <div className="mb-8 max-w-3xl rounded-xl border border-slate-700 bg-slate-900/40 p-4 text-sm text-slate-300">
                <p className="font-medium text-white">Two supported paths</p>
                <ul className="mt-2 list-inside list-disc space-y-1 text-slate-400">
                    <li>
                        <strong className="text-slate-200">Existing WhatsApp Business app number (Coexistence)</strong> — keep using the
                        mobile app where Meta marks the number eligible. If Meta shows a number as <em>Ineligible</em>, that decision comes
                        from Meta (region, account type, display name, or policy) — not because IQPigeon blocked it.
                    </li>
                    <li>
                        <strong className="text-slate-200">New number / standard Cloud API</strong> — add or register a number for API-only
                        sending (includes Meta test 555 numbers).
                    </li>
                </ul>
            </div>

            {(connectError || statusMessage || isBusy) && (
                <div className="mb-6 space-y-3">
                    {statusMessage && (
                        <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                            {statusMessage}
                        </div>
                    )}
                    {connectError && (
                        <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                            <p className="font-medium">We couldn&apos;t start WhatsApp setup.</p>
                            <p className="mt-1 text-red-200/90">{connectError}</p>
                            <p className="mt-2 text-xs text-red-200/70">
                                Please check your connection settings and try again.
                            </p>
                        </div>
                    )}
                    {isBusy && (
                        <p className="text-sm text-violet-300" role="status">
                            Opening secure Meta signup…
                        </p>
                    )}
                </div>
            )}

            {canConnect && (
                <div className="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-violet-500/30 bg-violet-500/5 p-6">
                    <div className="max-w-xl">
                        <h2 className="font-semibold text-white">Connect a WhatsApp Business number</h2>
                        <p className="mt-1 text-sm text-slate-400">
                            Choose coexistence for an existing Business app line, or standard setup for a new Cloud API number.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            disabled={isBusy}
                            onClick={() => postOnboarding('/app/connections/start-coexistence', 'coexistence')}
                            className="rounded-lg border border-violet-400/50 bg-slate-950 px-5 py-2.5 text-sm font-semibold text-violet-100 hover:bg-violet-500/10 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {pendingAction === 'coexistence' || pendingAction === 'coexistence-sdk'
                                ? 'Opening Meta (Business app)…'
                                : 'Connect existing Business app number'}
                        </button>
                        <button
                            type="button"
                            disabled={isBusy}
                            onClick={() => postOnboarding('/app/connections/start', 'start')}
                            className="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {pendingAction === 'start' ? 'Opening secure Meta signup…' : 'Set up new Cloud API number'}
                        </button>
                    </div>
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
                                        disabled={isBusy}
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
                            const awaitingRegistration = c.meta_linked_awaiting_registration === true;
                            const coexistencePendingLinked =
                                c.is_coexistence &&
                                c.connection_status === 'pending' &&
                                c.phone_number_id &&
                                !c.can_register_cloud_api;
                            const st = awaitingRegistration
                                ? statusLabel.meta_linked
                                : coexistencePendingLinked
                                  ? statusLabel.coexistence_linked
                                  : (statusLabel[c.connection_status] ?? statusLabel.pending);
                            const continueKey = `continue-${c.uuid}`;

                            return (
                                <article key={c.uuid} className="rounded-xl border border-amber-500/20 bg-slate-900/50 p-5">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="max-w-xl">
                                            <p className="font-medium text-white">
                                                {c.display_phone_number ?? `Draft started ${formatWhen(c.created_at)}`}
                                            </p>
                                            <p className={`mt-1 text-sm ${st.className}`}>{st.text}</p>
                                            {coexistencePendingLinked && (
                                                <p className="mt-2 text-sm text-slate-300">
                                                    This number uses WhatsApp Business App coexistence. No Cloud API PIN registration
                                                    is required — refresh this page if it has not moved to Active yet.
                                                </p>
                                            )}
                                            {awaitingRegistration && (
                                                <div className="mt-3 space-y-1 text-sm text-slate-300">
                                                    <p>
                                                        Meta authorized this WhatsApp Business number. Register it for Cloud API
                                                        sending with your 6-digit WhatsApp Business two-step verification PIN.
                                                    </p>
                                                    <p className="text-xs text-slate-500">
                                                        Use your existing PIN if two-step verification is already enabled, or choose a
                                                        new 6-digit PIN to establish it for this number. IQPigeon does not store your
                                                        PIN.
                                                    </p>
                                                    {c.waba_id && (
                                                        <p className="font-mono text-xs text-slate-500">WABA {c.waba_id}</p>
                                                    )}
                                                    {c.phone_number_id && (
                                                        <p className="font-mono text-xs text-slate-500">
                                                            Phone number ID {c.phone_number_id}
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                            {c.connection_status === 'pending' && !c.can_resume_setup && !awaitingRegistration && (
                                                <p className="mt-2 text-sm text-amber-300">Setup expired. Start a new connection.</p>
                                            )}
                                            {c.setup_hint &&
                                                (c.can_resume_setup || c.can_sync_from_meta || c.can_register_cloud_api) && (
                                                <p className="mt-2 text-sm text-slate-400">{c.setup_hint}</p>
                                            )}
                                            <p className="mt-2 text-xs text-slate-500">Last updated {formatWhen(c.updated_at)}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {c.can_register_cloud_api && (
                                                <form
                                                    className="flex flex-wrap items-end gap-2"
                                                    onSubmit={(e) => {
                                                        e.preventDefault();
                                                        if (isBusy) {
                                                            return;
                                                        }
                                                        const pin = registrationPin[c.uuid] ?? '';
                                                        setPendingAction(`register-${c.uuid}`);
                                                        router.post(
                                                            `/app/connections/${c.uuid}/register`,
                                                            { pin },
                                                            {
                                                                onFinish: () => {
                                                                    setPendingAction(null);
                                                                    setRegistrationPin((prev) => {
                                                                        const next = { ...prev };
                                                                        delete next[c.uuid];
                                                                        return next;
                                                                    });
                                                                },
                                                            },
                                                        );
                                                    }}
                                                >
                                                    <label className="text-xs text-slate-400">
                                                        WhatsApp Business 2-step PIN (6 digits)
                                                        <input
                                                            type="password"
                                                            inputMode="numeric"
                                                            autoComplete="off"
                                                            maxLength={6}
                                                            pattern="\d{6}"
                                                            required
                                                            value={registrationPin[c.uuid] ?? ''}
                                                            onChange={(e) =>
                                                                setRegistrationPin((prev) => ({
                                                                    ...prev,
                                                                    [c.uuid]: e.target.value.replace(/\D/g, '').slice(0, 6),
                                                                }))
                                                            }
                                                            className="mt-1 block w-32 rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 font-mono text-sm text-white"
                                                        />
                                                    </label>
                                                    <button
                                                        type="submit"
                                                        disabled={isBusy}
                                                        className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        {pendingAction === `register-${c.uuid}`
                                                            ? 'Registering…'
                                                            : 'Register for sending'}
                                                    </button>
                                                </form>
                                            )}
                                            {c.can_sync_from_meta && (
                                                <button
                                                    type="button"
                                                    disabled={isBusy}
                                                    onClick={() => postOnboarding(`/app/connections/${c.uuid}/rehydrate`, `sync-${c.uuid}`)}
                                                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {pendingAction === `sync-${c.uuid}` ? 'Syncing from Meta…' : 'Sync from Meta'}
                                                </button>
                                            )}
                                            {c.can_resume_setup && (
                                                <button
                                                    type="button"
                                                    disabled={isBusy}
                                                    onClick={() => postOnboarding(`/app/connections/${c.uuid}/continue`, continueKey)}
                                                    className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {pendingAction === continueKey
                                                        ? 'Opening secure Meta signup…'
                                                        : 'Continue setup'}
                                                </button>
                                            )}
                                            <button
                                                type="button"
                                                disabled={isBusy}
                                                onClick={() => {
                                                    if (confirm('Remove this draft connection?')) {
                                                        router.delete(`/app/connections/${c.uuid}`);
                                                    }
                                                }}
                                                className="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800 disabled:opacity-60"
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
