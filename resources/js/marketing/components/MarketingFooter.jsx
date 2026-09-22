import { Link } from '@inertiajs/react';

const columns = [
    {
        title: 'Product',
        links: [
            { href: '/developers', label: 'API' },
            { href: '/features', label: 'Connections' },
            { href: '/features#webhooks', label: 'Webhooks' },
            { href: '/features#usage', label: 'Usage' },
            { href: '/pricing', label: 'Pricing' },
        ],
    },
    {
        title: 'Developers',
        links: [
            { href: '/docs', label: 'Docs' },
            { href: '/docs', label: 'Quickstart' },
            { href: '/docs', label: 'API Reference' },
            { href: '/developers', label: 'Examples' },
        ],
    },
    {
        title: 'Company',
        links: [
            { href: '/contact', label: 'About' },
            { href: '/contact', label: 'Contact' },
            { href: '/security', label: 'Security' },
            { href: '/terms', label: 'Terms' },
            { href: '/privacy', label: 'Privacy' },
        ],
    },
];

export default function MarketingFooter() {
    return (
        <footer className="border-t border-slate-800/80 bg-slate-950/40 backdrop-blur-sm">
            <div className="iqp-container grid gap-10 py-16 md:grid-cols-4">
                <div>
                    <p className="text-lg font-semibold text-white">IQPigeon WhatsApp API</p>
                    <p className="mt-3 text-sm leading-relaxed text-slate-400">
                        WhatsApp infrastructure for CRM platforms — API, webhooks, and Embedded Signup without taking over
                        your customers&apos; Meta billing.
                    </p>
                </div>
                {columns.map((col) => (
                    <div key={col.title}>
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{col.title}</h3>
                        <ul className="mt-4 space-y-2">
                            {col.links.map((link) => (
                                <li key={`${col.title}-${link.label}`}>
                                    <Link
                                        href={link.href}
                                        className="text-sm text-slate-400 transition hover:text-violet-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-400"
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
            <div className="border-t border-slate-800/60 py-6 text-center text-sm text-slate-500">
                © {new Date().getFullYear()} IQPigeon
            </div>
        </footer>
    );
}
