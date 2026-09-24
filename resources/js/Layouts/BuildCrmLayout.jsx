import { Link, usePage } from '@inertiajs/react';
import { buildNav } from '../components/build/buildNav';
import IntegrationHealthPanel from '../components/integration/IntegrationHealthPanel';

export default function BuildCrmLayout({ title, children }) {
    const { url } = usePage();
    const health = usePage().props.integrationHealth;

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <div className="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-8 lg:flex-row">
                <aside className="lg:w-56 shrink-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Build your CRM</p>
                    <nav className="mt-3 space-y-1">
                        {buildNav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`block rounded-lg px-3 py-2 text-sm ${
                                    url.startsWith(item.href) ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <div className="mt-8 space-y-2 text-sm">
                        <Link href="/app/api-keys" className="block text-violet-400 hover:underline">
                            Manage API keys →
                        </Link>
                        <Link href="/app/webhooks" className="block text-violet-400 hover:underline">
                            Manage webhooks →
                        </Link>
                    </div>
                </aside>
                <div className="min-w-0 flex-1">
                    {health && (
                        <div className="mb-8">
                            <IntegrationHealthPanel health={health} compact />
                        </div>
                    )}
                    {title && <h1 className="mb-6 text-2xl font-semibold text-white">{title}</h1>}
                    {children}
                </div>
            </div>
        </div>
    );
}
