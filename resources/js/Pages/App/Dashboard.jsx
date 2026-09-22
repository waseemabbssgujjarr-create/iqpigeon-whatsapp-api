import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

function Card({ title, children, href }) {
    const inner = (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm h-full">
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
                    <code className="mt-2 block break-all rounded bg-white px-3 py-2 text-sm">{flashApiKeySecret}</code>
                </div>
            )}

            {showChecklist && (
                <section className="mb-8 rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-6">
                    <h2 className="text-lg font-semibold text-[#0f172a]">Getting started</h2>
                    <ol className="mt-4 space-y-2">
                        {checklist?.map((step) => (
                            <li key={step.key} className="flex items-center gap-3 text-sm">
                                <span
                                    className={`flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                        step.complete ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600'
                                    }`}
                                >
                                    {step.complete ? '✓' : '·'}
                                </span>
                                {step.href && !step.complete ? (
                                    <Link href={step.href} className="text-violet-700 hover:underline">
                                        {step.label}
                                    </Link>
                                ) : (
                                    <span className={step.complete ? 'text-slate-600 line-through' : 'text-slate-800'}>
                                        {step.label}
                                    </span>
                                )}
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
                    {apiKeys?.active_count ?? 0} active
                </Card>
                <Card title="WhatsApp" href="/app/connections">
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

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <Card title="Webhooks" href="/app/webhooks">
                    {webhook ? webhook.url : 'No endpoint configured'}
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
                        'No usage yet'
                    )}
                </Card>
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
