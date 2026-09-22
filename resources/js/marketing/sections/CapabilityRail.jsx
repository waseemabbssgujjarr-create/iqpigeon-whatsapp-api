const items = [
    'WhatsApp Business Platform',
    'Embedded Signup',
    'REST API',
    'Webhooks',
    'API keys & scopes',
    'Usage metering',
];

export default function CapabilityRail() {
    const doubled = [...items, ...items];

    return (
        <section className="border-y border-slate-700/30 py-5" aria-label="Platform capabilities">
            <div className="overflow-hidden">
                <div className="iqp-ticker-track flex w-max gap-12 whitespace-nowrap px-4">
                    {doubled.map((label, i) => (
                        <span key={`${label}-${i}`} className="flex items-center gap-3 text-sm text-slate-400">
                            <span className="h-1 w-1 rounded-full bg-violet-400/80" aria-hidden="true" />
                            {label}
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}
