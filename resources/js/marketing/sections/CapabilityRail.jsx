const items = [
    'WhatsApp Business Platform',
    'Embedded Signup',
    'REST API',
    'Webhooks',
    'API Keys',
    'Idempotency',
    'Usage',
];

export default function CapabilityRail() {
    const doubled = [...items, ...items];

    return (
        <section className="border-y border-slate-800/80 bg-slate-950/40 py-4" aria-label="Platform capabilities">
            <div className="overflow-hidden">
                <div className="iqp-ticker-track flex w-max gap-10 whitespace-nowrap px-4">
                    {doubled.map((label, i) => (
                        <span
                            key={`${label}-${i}`}
                            className="flex items-center gap-3 font-mono text-xs uppercase tracking-wider text-slate-500"
                        >
                            <span className="h-1.5 w-1.5 rounded-full bg-violet-500/80" aria-hidden="true" />
                            {label}
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}
