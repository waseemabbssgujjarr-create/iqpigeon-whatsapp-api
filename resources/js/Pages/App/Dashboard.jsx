import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import FirstApiRequestGuide from '../../components/FirstApiRequestGuide';
import StepStatusBadge from '../../components/integration/StepStatusBadge';
import { copyText } from '../../components/ui/copyText';
import IntegrationHealthPanel from '../../components/integration/IntegrationHealthPanel';
import AppLayout from '../../Layouts/AppLayout';

function PrerequisiteBanner({ subscriptionReady }) {
    if (subscriptionReady) {
        return null;
    }

    return (
        <div className="mb-8 rounded-xl border border-amber-500/40 bg-amber-500/10 p-5">
            <p className="font-medium text-amber-100">Activate your subscription first</p>
            <p className="mt-1 text-sm text-amber-200/80">
                Choose a plan and complete checkout before connecting WhatsApp or creating API keys.
            </p>
            <Link
                href="/app/billing"
                className="mt-4 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
            >
                Go to billing
            </Link>
        </div>
    );
}

function WorkflowStep({ step, isCurrent, apiBaseUrl, connectionUuid }) {
    return (
        <li
            className={`rounded-xl border p-5 ${
                isCurrent ? 'border-violet-500/50 bg-violet-500/5' : 'border-slate-700 bg-slate-900/40'
            }`}
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex gap-4">
                    <span
                        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold ${
                            step.complete ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-slate-200'
                        }`}
                    >
                        {step.complete ? '✓' : step.number}
                    </span>
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="font-semibold text-white">{step.title}</h3>
                            <StepStatusBadge status={step.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-400">{step.description}</p>
                        {step.detail && <p className="mt-2 font-mono text-xs text-slate-500">{step.detail}</p>}
                        {step.key === 'webhook' && step.meta?.webhook_tested && (
                            <p className="mt-2 text-sm text-emerald-400">✓ Webhook test delivered successfully</p>
                        )}
                        {step.key === 'webhook' && step.complete && step.meta && !step.meta.webhook_tested && (
                            <p className="mt-2 text-sm text-amber-300">Webhook saved — run a test from the Webhooks page.</p>
                        )}
                    </div>
                </div>
                {step.href && step.action_label && !step.complete && (
                    <Link
                        href={step.href}
                        className="shrink-0 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
                    >
                        {step.action_label}
                    </Link>
                )}
                {step.href && step.complete && step.action_label && (
                    <Link href={step.href} className="shrink-0 text-sm text-violet-400 hover:underline">
                        {step.action_label}
                    </Link>
                )}
            </div>
            {step.key === 'first_message' && step.status !== 'not_started' && (
                <div className="mt-5 border-t border-slate-700/80 pt-5">
                    <FirstApiRequestGuide apiBaseUrl={apiBaseUrl} connectionId={connectionUuid} compact />
                </div>
            )}
        </li>
    );
}

function IntegrationReadyView({ whatsapp, crmIntegration, apiBaseUrl, connectionUuid, usageSummary }) {
    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [copied, setCopied] = useState(false);

    const copyExample = async () => {
        const el = document.querySelector('[data-first-api-sample]');
        if (el?.textContent) {
            await copyText(el.textContent);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    return (
        <div className="space-y-8">
            <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5">
                <p className="text-lg font-semibold text-emerald-100">Your CRM is ready</p>
                <p className="mt-1 text-sm text-emerald-200/80">Send messages from your CRM and receive inbound events on your webhook.</p>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-700 bg-slate-900/50 p-5">
                    <h2 className="text-sm font-medium uppercase tracking-wide text-slate-500">WhatsApp connection</h2>
                    {whatsapp ? (
                        <div className="mt-3 space-y-1 text-sm">
                            <p className="text-lg font-semibold text-white">{whatsapp.display_phone ?? 'Connected number'}</p>
                            <p className="capitalize text-slate-400">Status: {whatsapp.status}</p>
                            {whatsapp.waba_id && <p className="font-mono text-xs text-slate-500">WABA {whatsapp.waba_id}</p>}
                        </div>
                    ) : (
                        <p className="mt-3 text-sm text-slate-400">No active connection.</p>
                    )}
                    <Link href="/app/connections" className="mt-4 inline-block text-sm text-violet-400 hover:underline">
                        Manage WhatsApp
                    </Link>
                </section>

                <section className="rounded-xl border border-slate-700 bg-slate-900/50 p-5">
                    <h2 className="text-sm font-medium uppercase tracking-wide text-slate-500">CRM integration</h2>
                    <ul className="mt-3 space-y-2 text-sm text-slate-300">
                        <li>
                            API key:{' '}
                            {crmIntegration?.api_key ? (
                                <span>
                                    {crmIntegration.api_key.label} ({crmIntegration.api_key.prefix}…)
                                </span>
                            ) : (
                                <span className="text-amber-400">Not created</span>
                            )}
                        </li>
                        <li>
                            Webhook:{' '}
                            {crmIntegration?.webhook ? (
                                <span className="break-all font-mono text-xs">{crmIntegration.webhook.url}</span>
                            ) : (
                                <span className="text-amber-400">Not configured</span>
                            )}
                        </li>
                        <li>
                            Last webhook test:{' '}
                            {crmIntegration?.webhook?.last_test ? (
                                <span className={crmIntegration.webhook.last_test.reachable ? 'text-emerald-400' : 'text-amber-400'}>
                                    {crmIntegration.webhook.last_test.reachable ? 'Reachable' : crmIntegration.webhook.last_test.status}{' '}
                                    (HTTP {crmIntegration.webhook.last_test.http ?? '—'})
                                </span>
                            ) : (
                                <span className="text-slate-500">No test yet</span>
                            )}
                        </li>
                        <li>
                            Last API request:{' '}
                            {crmIntegration?.last_api_request ? (
                                <span>
                                    {crmIntegration.last_api_request.method} {crmIntegration.last_api_request.path} →{' '}
                                    {crmIntegration.last_api_request.status_code}
                                </span>
                            ) : (
                                <span className="text-slate-500">None yet</span>
                            )}
                        </li>
                    </ul>
                </section>
            </div>

            <div className="flex flex-wrap gap-3">
                <Link href="/app/build/send-messages" className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                    Send test message
                </Link>
                <Link href="/app/webhooks" className="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                    Test webhook
                </Link>
                <Link href="/app/build/code-examples" className="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                    View integration code
                </Link>
            </div>

            <div data-first-api-sample>
                <FirstApiRequestGuide apiBaseUrl={apiBaseUrl} connectionId={connectionUuid} />
            </div>

            {usageSummary && (
                <p className="text-center text-xs text-slate-500">
                    {usageSummary.messages} messages · {usageSummary.api_requests} API requests · {usageSummary.webhook_deliveries}{' '}
                    webhook deliveries
                </p>
            )}

            <div className="rounded-xl border border-slate-800 bg-slate-900/30">
                <button
                    type="button"
                    className="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-medium text-slate-300"
                    onClick={() => setAdvancedOpen((o) => !o)}
                >
                    Advanced details
                    <span>{advancedOpen ? '−' : '+'}</span>
                </button>
                {advancedOpen && connectionUuid && (
                    <div className="border-t border-slate-800 px-5 py-4 text-sm text-slate-400">
                        <p>
                            Connection ID (for <code className="text-slate-300">connection_id</code> in API requests):
                        </p>
                        <code className="mt-2 block break-all rounded bg-slate-950 p-3 font-mono text-xs text-violet-300">{connectionUuid}</code>
                    </div>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({
    workflow,
    integrationHealth,
    apiBaseUrl,
    connectionUuid,
    flashApiKeySecret,
    whatsapp,
    crmIntegration,
    usageSummary,
}) {
    const { flash } = usePage().props;
    const showWorkflow = workflow && !workflow.integration_complete;
    const currentStepKey = workflow?.steps?.find((s) => !s.complete && s.status !== 'not_started')?.key
        ?? workflow?.steps?.find((s) => !s.complete)?.key;

    return (
        <AppLayout hideTitle title="Home">
            {flash?.verified && (
                <div className="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    Your email address is verified.
                </div>
            )}

            {flashApiKeySecret && (
                <div className="mb-6 rounded-lg border-2 border-violet-500/50 bg-violet-500/10 p-4">
                    <p className="text-sm font-medium text-violet-100">Your API key (shown once)</p>
                    <p className="mt-1 text-xs text-violet-200/80">
                        Store this on your CRM server only. Never expose it in browser-side JavaScript.
                    </p>
                    <code className="mt-2 block break-all rounded bg-slate-950 px-3 py-2 text-sm text-white">{flashApiKeySecret}</code>
                </div>
            )}

            <header className="mb-8">
                <h1 className="text-2xl font-semibold text-white">{workflow?.headline ?? 'Home'}</h1>
                <p className="mt-2 max-w-2xl text-sm text-slate-400">
                    Connect WhatsApp to your CRM and start sending messages.
                </p>
                <p className="mt-3 text-sm font-medium text-slate-300">Connect IQPigeon to your CRM</p>
            </header>

            {integrationHealth && (
                <div className="mb-8">
                    <IntegrationHealthPanel health={integrationHealth} />
                </div>
            )}

            <PrerequisiteBanner subscriptionReady={workflow?.subscription_ready} />

            {showWorkflow ? (
                <ol className="space-y-4">
                    {workflow.steps.map((step) => (
                        <WorkflowStep
                            key={step.key}
                            step={step}
                            isCurrent={step.key === currentStepKey}
                            apiBaseUrl={apiBaseUrl}
                            connectionUuid={connectionUuid}
                        />
                    ))}
                </ol>
            ) : (
                <IntegrationReadyView
                    whatsapp={whatsapp}
                    crmIntegration={crmIntegration}
                    apiBaseUrl={apiBaseUrl}
                    connectionUuid={connectionUuid}
                    usageSummary={usageSummary}
                />
            )}
        </AppLayout>
    );
}
