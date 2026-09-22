import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

export default function HeroComposition() {
    const reduced = usePrefersReducedMotion();

    return (
        <div className="relative mx-auto w-full max-w-[640px] lg:max-w-none">
            <div className="iqp-product-panel iqp-float-panel relative z-10 overflow-hidden p-1">
                <div className="flex items-center gap-2 border-b border-slate-700/50 px-4 py-2">
                    <span className="h-2.5 w-2.5 rounded-full bg-red-500/70" />
                    <span className="h-2.5 w-2.5 rounded-full bg-amber-500/70" />
                    <span className="h-2.5 w-2.5 rounded-full bg-emerald-500/70" />
                    <span className="ml-2 font-mono text-[10px] text-slate-500">Acme CRM — outbound</span>
                </div>
                <div className="grid gap-3 p-4 sm:grid-cols-2">
                    <div className="rounded-lg bg-slate-950/80 p-3 font-mono text-[10px] text-slate-400">
                        <p className="text-emerald-400">POST /api/v1/messages</p>
                        <p className="mt-2 text-slate-500">Authorization: Bearer iqp_live_demo••••</p>
                        <p className="mt-1">{`{ "to": "+1…", "type": "text" }`}</p>
                    </div>
                    <div className="rounded-lg border border-slate-700/50 bg-slate-900/60 p-3">
                        <p className="font-mono text-[10px] text-cyan-400">WhatsApp · delivered</p>
                        <p className="mt-2 text-xs text-slate-300">Appointment confirmed for tomorrow at 10:00.</p>
                    </div>
                </div>
            </div>
            <div className="absolute -right-4 top-16 z-20 w-[42%] max-w-[180px] rounded-2xl border border-slate-700/60 bg-slate-950 p-3 shadow-xl sm:-right-8">
                <p className="text-[10px] font-medium text-emerald-400">WhatsApp</p>
                <div className="mt-2 space-y-2">
                    <div className="rounded-lg bg-emerald-600/20 px-2 py-1.5 text-[10px] text-slate-200">Your tour is booked ✓</div>
                    <div className="rounded-lg bg-slate-800 px-2 py-1.5 text-[10px] text-slate-400">Thanks!</div>
                </div>
            </div>
            <svg className="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true">
                <defs>
                    <linearGradient id="hero-path" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop stopColor="#8b5cf6" />
                        <stop stopColor="#22d3ee" />
                    </linearGradient>
                </defs>
                <path d="M80 220 Q200 120 320 80" fill="none" stroke="url(#hero-path)" strokeWidth="1.5" opacity="0.5" />
                <path d="M320 180 Q200 260 80 300" fill="none" stroke="url(#hero-path)" strokeWidth="1.5" opacity="0.4" />
                {!reduced && (
                    <circle r="4" fill="#8b5cf6">
                        <animateMotion dur="4s" repeatCount="indefinite" path="M80 220 Q200 120 320 80" />
                    </circle>
                )}
            </svg>
            <p className="mt-4 text-center font-mono text-[10px] uppercase tracking-widest text-slate-600">Product composition (illustrative)</p>
        </div>
    );
}
