import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import { GuideIntro, RequestIdNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function ApiReference({ apiBaseUrl }) {
    const routes = [
        { method: 'GET', path: '/me', scope: '—', note: 'Auth check' },
        { method: 'GET', path: '/connections', scope: 'connections.read', note: 'List connections' },
        { method: 'POST', path: '/connections', scope: 'connections.write', note: 'Start onboarding (Idempotency-Key)' },
        { method: 'GET', path: '/connections/{id}', scope: 'connections.read', note: 'Verify connection status' },
        { method: 'DELETE', path: '/connections/{id}', scope: 'connections.write', note: 'Disconnect' },
        { method: 'POST', path: '/messages', scope: 'messages.send', note: 'Send message (Idempotency-Key)' },
        { method: 'GET', path: '/messages', scope: 'messages.read', note: 'List recent messages' },
        { method: 'GET', path: '/messages/{id}', scope: 'messages.read', note: 'Message detail' },
        { method: 'GET', path: '/webhooks', scope: 'webhooks.read', note: 'List webhook endpoints' },
        { method: 'POST', path: '/webhooks', scope: 'webhooks.write', note: 'Create endpoint (Idempotency-Key)' },
        { method: 'GET', path: '/usage', scope: 'usage.read', note: 'Usage metrics' },
    ];

    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="API reference">
                <GuideIntro what="REST API under /api/v1." when="Implementing CRM backend integrations." />

                <DocBlock title="Base URL" code={apiBaseUrl} copyable />

                <div className="overflow-x-auto rounded-xl border border-slate-700">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-800 text-slate-400">
                            <tr>
                                <th className="px-3 py-2 text-left">Method</th>
                                <th className="px-3 py-2 text-left">Path</th>
                                <th className="px-3 py-2 text-left">Scope</th>
                                <th className="px-3 py-2 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            {routes.map((r) => (
                                <tr key={r.method + r.path} className="border-t border-slate-800 text-slate-300">
                                    <td className="px-3 py-2 font-mono text-xs">{r.method}</td>
                                    <td className="px-3 py-2 font-mono text-xs text-violet-300">{r.path}</td>
                                    <td className="px-3 py-2 font-mono text-xs">{r.scope}</td>
                                    <td className="px-3 py-2 text-slate-400">{r.note}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <p className="mt-4 text-sm text-slate-400">
                    All routes require <code className="text-slate-300">Authorization: Bearer</code> and active partner (
                    middleware partner.active).
                </p>

                <Link href="/docs" className="mt-6 inline-block rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">
                    Public marketing docs
                </Link>
                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
