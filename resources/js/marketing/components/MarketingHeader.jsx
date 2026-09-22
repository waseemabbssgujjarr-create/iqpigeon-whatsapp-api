import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const nav = [
    { href: '/features', label: 'Features' },
    { href: '/developers', label: 'Developers' },
    { href: '/docs', label: 'Docs' },
    { href: '/pricing', label: 'Pricing' },
    { href: '/contact', label: 'Contact' },
];

export default function MarketingHeader() {
    const { url } = usePage();
    const [scrolled, setScrolled] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 24);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        document.body.style.overflow = menuOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [menuOpen]);

    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape') {
                setMenuOpen(false);
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    return (
        <header
            className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
                scrolled ? 'iqp-glass-strong py-3 shadow-lg shadow-black/20' : 'bg-transparent py-5'
            }`}
        >
            <div className="iqp-container flex max-w-[1400px] items-center justify-between">
                <Link href="/" className="group flex items-center gap-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-400">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-600/20 ring-1 ring-violet-400/40">
                        <span className="text-sm font-bold text-violet-300">IQ</span>
                    </span>
                    <span className="text-sm font-semibold tracking-tight text-white sm:text-base">
                        IQPigeon <span className="text-violet-400">WhatsApp API</span>
                    </span>
                </Link>

                <nav className="hidden items-center gap-8 md:flex" aria-label="Primary">
                    {nav.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="iqp-nav-link text-sm font-medium"
                            aria-current={url.startsWith(item.href) ? 'page' : undefined}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="hidden items-center gap-3 md:flex">
                    <Link href="/login" className="iqp-btn-ghost rounded-lg px-4 py-2 text-sm font-medium text-slate-200">
                        Log in
                    </Link>
                    <Link href="/signup" className="iqp-btn-primary rounded-lg px-4 py-2 text-sm font-semibold text-white">
                        Start building
                    </Link>
                </div>

                <button
                    type="button"
                    className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-600/50 text-slate-200 md:hidden"
                    aria-expanded={menuOpen}
                    aria-controls="mobile-nav"
                    aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                    onClick={() => setMenuOpen((o) => !o)}
                >
                    <span className="sr-only">{menuOpen ? 'Close' : 'Menu'}</span>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        {menuOpen ? (
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        ) : (
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        )}
                    </svg>
                </button>
            </div>

            <div
                id="mobile-nav"
                className={`fixed inset-0 z-40 bg-slate-950/95 backdrop-blur-xl transition-opacity duration-300 md:hidden ${
                    menuOpen ? 'pointer-events-auto opacity-100' : 'pointer-events-none opacity-0'
                }`}
                aria-hidden={!menuOpen}
            >
                <div className="flex h-full flex-col px-6 pb-10 pt-24">
                    <nav className="flex flex-col gap-2" aria-label="Mobile">
                        {nav.map((item, i) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`iqp-mobile-nav-enter rounded-xl px-4 py-4 text-2xl font-semibold text-white ${menuOpen ? 'is-open' : ''}`}
                                style={{ transitionDelay: menuOpen ? `${80 + i * 50}ms` : '0ms' }}
                                onClick={() => setMenuOpen(false)}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <div className="mt-auto flex flex-col gap-3">
                        <Link
                            href="/signup"
                            className="iqp-btn-primary rounded-xl px-5 py-4 text-center text-base font-semibold text-white"
                            onClick={() => setMenuOpen(false)}
                        >
                            Start building
                        </Link>
                        <Link
                            href="/login"
                            className="iqp-btn-ghost rounded-xl px-5 py-4 text-center text-base font-medium text-slate-200"
                            onClick={() => setMenuOpen(false)}
                        >
                            Log in
                        </Link>
                    </div>
                </div>
            </div>
        </header>
    );
}
