import { Link, usePage } from '@inertiajs/react';
import FirstApiRequestGuide from '../../components/FirstApiRequestGuide';
import AppLayout from '../../Layouts/AppLayout';

function Card({ title, children, href }) {
    const inner = (
        <div className="h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 className="text-sm font-medium uppercase tracking-wide text-slate-500">{title}</h3>
            <div className="mt-3 text-sm text-slate-800">{children}</div>
        </div>
    );

    return href ? (
        <Link href={href} className="block hover:opacity-95">
            {inner}
        </Link>
    ) : (
        inner
    );
}

export default function Dashboard({
    showChecklist,
    checklist,
    onboardingSummary,
    apiBaseUrl,
    connectionUuid,
    flashApiKeySecret,
    account,
    plan,
    subscription,
    apiKeys,
    connection,
    webhook,
    usageSummary,
    recentActivity,
}) {
    const { flash } = usePage().props;

    return (
        <AppLayout title="Dashboard">
            {flash?.verified && (
                <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Your email address is verified.
                </div>
            )}
            {flashApiKeySecret && (
                <div className="mb-6 rounded-lg border border-violet-300 bg-violet-50 p-4">
                    <p className="text-sm font-medium text-violet-900">Your API key (shown once)</p>
                    <p className="mt-1 text-xs text-violet-800">
                        This is the credential your CRM uses when calling the IQPigeon API (
                        <code className="rounded bg-white px-1">Authorization: Bearer …</code>
                        ). It is not a Meta token. Store it server-side only.
                    </p>
                    <code className="mt-2 block break-all rounded bg-white px-3 py-2 text-sm">{flashApiKeySecret}</code>
                </div>
            )}

            <section className="mb-8 rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h2 className="text-lg font-semibold text-[#0f172a]">How this platform works</h2>
                <p className="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
                    Your CRM calls <strong>IQPigeon</strong> with an API key. Each WhatsApp Business number is a{' '}
                    <strong>connection</strong>. Outbound messages go CRM → IQPigeon → Meta → WhatsApp. Inbound messages and status
                    events are delivered to your CRM <strong>webhook URL</strong>.
                </p>
            </section>

            {showChecklist && (
                <section className="mb-8 rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-6">
                    <h2 className="text-lg font-semibold text-[#0f172a]">Getting started</h2>
                    {onboardingSummary && (
                        <p className="mt-2 text-sm text-slate-700">
                            <span className="font-medium">{onboardingSummary.headline}</span>
                            {onboardingSummary.remaining > 0 && (
                                <> — {onboardingSummary.remaining} step{onboardingSummary.remaining === 1 ? '' : 's'} remaining.</>
                            )}
                        </p>
                    )}
                    <ol className="mt-6 space-y-4">
                        {checklist?.map((step) => (
                            <li key={step.key} className="rounded-lg border border-slate-200/80 bg-white/80 p-4">
                                <div className="flex flex-wrap items-start gap-3">
                                    <span
                                        className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                            step.complete ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600'
                                        }`}
                                    >
                                        {step.complete ? '✓' : '·'}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className={`font-medium ${step.complete ? 'text-slate-500 line-through' : 'text-slate-900'}`}>
                                            {step.title || step.label}
                                        </p>
                                        {step.description && <p className="mt-1 text-sm text-slate-600">{step.description}</p>}
                                        {!step.complete && step.href && step.action_label && (
                                            <Link
                                                href={step.href}
                                                className="mt-3 inline-flex rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700"
                                            >
                                                {step.action_label}
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ol>
                </section>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card title="Account" href="/app/settings">
                    Email {account?.email_verified ? 'verified' : 'not verified'}
                    <br />
                    Partner: {account?.partner_status ?? '—'}
                </Card>
                <Card title="Plan" href="/app/billing">
                    {plan?.name ?? 'No plan'}
                    {subscription && (
                        <>
                            <br />
                            <span className="capitalize">{subscription.stripe_status.replace('_', ' ')}</span>
                        </>
                    )}
                </Card>
                <Card title="API keys" href="/app/api-keys">
                    {apiKeys?.active_count ?? 0} active — CRM Bearer token
                </Card>
                <Card title="WhatsApp connection" href="/app/connections">
                    {connection ? (
                        <>
                            {connection.status}
                            {connection.display_phone && <br />}
                            {connection.display_phone}
                        </>
                    ) : (
                        'Not connected'
                    )}
                </Card>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card title="Webhooks" href="/app/webhooks">
                    {webhook ? webhook.url : 'Add the URL where IQPigeon delivers WhatsApp events to your CRM.'}
                </Card>
                <Card title="Usage" href="/app/usage">
                    {usageSummary ? (
                        <>
                            API requests: {usageSummary.api_requests}
                            <br />
                            Messages: {usageSummary.messages}
                            <br />
                            Webhook deliveries: {usageSummary.webhook_deliveries}
                        </>
                    ) : (
                        'Metrics appear after your first API traffic.'
                    )}
                </Card>
            </div>

            <div className="mt-8">
                <FirstApiRequestGuide apiBaseUrl={apiBaseUrl} connectionId={connectionUuid} />
            </div>

            {recentActivity?.length > 0 && (
                <section className="mt-8">
                    <h2 className="mb-3 text-lg font-semibold">Recent API activity</h2>
                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <table className="min-w-full text-sm">
                            <thead className="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th className="px-4 py-2">Method</th>
                                    <th className="px-4 py-2">Path</th>
                                    <th className="px-4 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentActivity.map((row, i) => (
                                    <tr key={i} className="border-t border-slate-100">
                                        <td className="px-4 py-2 font-mono">{row.method}</td>
                                        <td className="px-4 py-2 font-mono">{row.path}</td>
                                        <td className="px-4 py-2">{row.status_code}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
