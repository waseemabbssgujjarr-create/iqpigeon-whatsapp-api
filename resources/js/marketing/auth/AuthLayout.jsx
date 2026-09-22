import { Link } from '@inertiajs/react';

export default function AuthLayout({ title, subtitle, children, footer }) {
    return (
        <div className="iqp-marketing iqp-auth-root min-h-screen">
            <div className="iqp-auth-bg iqp-dot-grid relative flex min-h-screen flex-col lg:flex-row">
                <div className="iqp-auth-glow pointer-events-none absolute inset-0" aria-hidden="true" />
                <aside className="relative flex flex-col justify-between px-6 py-10 lg:w-[44%] lg:px-12 lg:py-16 xl:px-16">
                    <Link href="/" className="inline-flex items-center gap-2 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cyan-400">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600/25 ring-1 ring-violet-400/40">
                            <span className="text-sm font-bold text-violet-200">IQ</span>
                        </span>
                        <span className="text-sm font-semibold tracking-tight sm:text-base">
                            IQPigeon <span className="text-violet-400">WhatsApp API</span>
                        </span>
                    </Link>
                    <div className="iqp-auth-panel-enter mt-12 max-w-lg lg:mt-0">
                        <p className="font-mono text-xs uppercase tracking-[0.2em] text-cyan-400/90">Developer platform</p>
                        <h1 className="mt-4 text-3xl font-semibold leading-tight text-white sm:text-4xl lg:text-[2.75rem]">
                            WhatsApp infrastructure for your CRM
                        </h1>
                        <p className="mt-5 text-base leading-relaxed text-slate-400">
                            Connect businesses with Embedded Signup, send messages through one API, and receive signed webhooks — while
                            each customer keeps its own Meta billing relationship.
                        </p>
                        <ul className="mt-8 space-y-3 font-mono text-xs text-slate-500">
                            <li className="flex items-center gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-violet-500" />
                                API keys · dot-notation scopes
                            </li>
                            <li className="flex items-center gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-cyan-400" />
                                Stripe platform subscription
                            </li>
                            <li className="flex items-center gap-2">
                                <span className="h-1.5 w-1.5 rounded-full bg-violet-500" />
                                Partner webhooks with retries
                            </li>
                        </ul>
                    </div>
                    <p className="mt-10 hidden text-xs text-slate-600 lg:block">© {new Date().getFullYear()} IQPigeon</p>
                </aside>
                <main className="relative flex flex-1 items-center justify-center px-4 pb-12 pt-4 lg:px-10 lg:py-16">
                    <div className="iqp-auth-card-enter iqp-glow-border w-full max-w-md rounded-2xl iqp-glass-strong p-8 shadow-2xl sm:p-10">
                        <header>
                            <h2 className="text-2xl font-semibold tracking-tight text-white">{title}</h2>
                            {subtitle && <p className="mt-2 text-sm leading-relaxed text-slate-400">{subtitle}</p>}
                        </header>
                        <div className="mt-8">{children}</div>
                        {footer && <div className="mt-8 border-t border-slate-700/60 pt-6 text-center text-sm text-slate-400">{footer}</div>}
                    </div>
                </main>
            </div>
        </div>
    );
}
