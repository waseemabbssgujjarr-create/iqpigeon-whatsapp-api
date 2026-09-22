import { Link, usePage } from '@inertiajs/react';

const nav = [
    { href: '/app', label: 'Overview' },
    { href: '/app/billing', label: 'Billing' },
    { href: '/app/api-keys', label: 'API Keys' },
    { href: '/app/connections', label: 'Connections' },
    { href: '/app/webhooks', label: 'Webhooks' },
    { href: '/app/usage', label: 'Usage' },
    { href: '/app/settings', label: 'Settings' },
];

export default function AppLayout({ title, children }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="border-b border-slate-200 bg-[#0f172a] text-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
                    <Link href="/app" className="text-lg font-semibold tracking-tight">
                        <span className="text-violet-300">IQ</span>Pigeon
                    </Link>
                    <nav className="flex flex-wrap gap-1 text-sm">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="rounded-md px-3 py-2 text-slate-200 hover:bg-white/10 hover:text-white"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <div className="flex items-center gap-3 text-sm text-slate-300">
                        <span>{auth?.user?.email}</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="rounded-md border border-white/20 px-3 py-1.5 hover:bg-white/10"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-8">
                {flash?.status && (
                    <div className="mb-6 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-900">
                        {flash.status}
                    </div>
                )}
                {title && <h1 className="mb-6 text-2xl font-semibold text-[#0f172a]">{title}</h1>}
                {children}
            </main>
        </div>
    );
}
