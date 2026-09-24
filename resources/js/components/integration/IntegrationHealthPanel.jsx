export default function IntegrationHealthPanel({ health, compact = false }) {
    if (!health?.checks?.length) {
        return null;
    }

    return (
        <section className={`rounded-xl border ${health.ready ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-slate-700 bg-slate-900/50'} p-5`}>
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-white">Integration status</h2>
                <span className={`text-sm font-medium ${health.ready ? 'text-emerald-300' : 'text-slate-400'}`}>{health.headline}</span>
            </div>
            <ul className={`mt-4 space-y-2 ${compact ? 'text-sm' : ''}`}>
                {health.checks.map((check) => (
                    <li key={check.key} className="flex flex-wrap items-start justify-between gap-2 text-slate-300">
                        <span>
                            {check.ok ? '✓' : '○'} {check.label}
                        </span>
                        {!check.ok && check.detail && <span className="text-xs text-slate-500">{check.detail}</span>}
                    </li>
                ))}
            </ul>
        </section>
    );
}
