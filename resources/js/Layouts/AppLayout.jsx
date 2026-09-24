import { Link, usePage } from '@inertiajs/react';
import { buildNav } from '../components/build/buildNav';

const primaryNav = [
    { href: '/app', label: 'Home', match: (url) => url === '/app' },
    { href: '/app/connections', label: 'Connect WhatsApp', match: (url) => url.startsWith('/app/connections') },
];

const accountNav = [
    { href: '/app/usage', label: 'Usage', match: (url) => url.startsWith('/app/usage') },
    { href: '/app/billing', label: 'Billing', match: (url) => url.startsWith('/app/billing') },
    { href: '/app/settings', label: 'Settings', match: (url) => url.startsWith('/app/settings') },
];

function NavLink({ item, url }) {
    const active = item.match(url);
    return (
        <Link
            href={item.href}
            className={`rounded-md px-3 py-2 text-sm transition-colors ${
                active ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'
            }`}
        >
            {item.label}
        </Link>
    );
}

export default function AppLayout({ title, children, hideTitle = false }) {
    const { url } = usePage();
    const { auth, flash } = usePage().props;
    const inBuild = url.startsWith('/app/build');

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <header className="border-b border-slate-800 bg-[#0f172a]">
                <div className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap items-center gap-4">
                        <Link href="/app" className="text-lg font-semibold tracking-tight text-white">
                            <span className="text-violet-400">IQ</span>Pigeon
                        </Link>
                        <nav className="flex flex-wrap items-center gap-1">
                            {primaryNav.map((item) => (
                                <NavLink key={item.href} item={item} url={url} />
                            ))}
                            <Link
                                href="/app/build/getting-started"
                                className={`rounded-md px-3 py-2 text-sm ${
                                    inBuild ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                Build your CRM
                            </Link>
                            {accountNav.map((item) => (
                                <NavLink key={item.href} item={item} url={url} />
                            ))}
                        </nav>
                    </div>
                    <div className="flex items-center gap-3 text-sm text-slate-400">
                        <span className="max-w-[200px] truncate">{auth?.user?.email}</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="shrink-0 rounded-md border border-slate-600 px-3 py-1.5 text-slate-200 hover:bg-white/5"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
                {inBuild && (
                    <div className="border-t border-slate-800/80 bg-slate-900/50">
                        <div className="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 py-2 text-xs">
                            {buildNav.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`whitespace-nowrap rounded px-2 py-1 ${
                                        url.startsWith(item.href) ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </header>

            <main className={inBuild ? '' : 'mx-auto max-w-6xl px-4 py-8'}>
                {flash?.status && (
                    <div className={`mb-6 rounded-lg border border-violet-500/40 bg-violet-500/10 px-4 py-3 text-sm text-violet-100 ${inBuild ? 'mx-auto max-w-6xl mt-6 px-4' : ''}`}>
                        {flash.status}
                    </div>
                )}
                {!hideTitle && !inBuild && title && <h1 className="mb-6 text-2xl font-semibold text-white">{title}</h1>}
                {children}
            </main>
        </div>
    );
}
