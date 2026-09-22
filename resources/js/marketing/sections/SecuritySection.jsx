import Reveal from '../components/Reveal';

const layers = [
    'API key hashing',
    'Scope enforcement',
    'Idempotency',
    'Encrypted credentials',
    'Webhook signatures',
    'SSRF protection',
    'Audit trail',
];

export default function SecuritySection() {
    return (
        <section className="py-24 sm:py-28" id="security">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Security layers</h2>
                    <p className="mt-4 text-slate-400">Defense in depth for multi-tenant CRM integrations — no compliance claims beyond what the product implements.</p>
                </Reveal>
                <div className="mt-12 space-y-3">
                    {layers.map((layer, i) => (
                        <Reveal key={layer} delay={i * 60}>
                            <div
                                className="iqp-glass flex items-center gap-4 rounded-xl px-6 py-4"
                                style={{ marginLeft: `${Math.min(i * 8, 48)}px` }}
                            >
                                <span className="font-mono text-xs text-violet-400">{String(i + 1).padStart(2, '0')}</span>
                                <span className="text-slate-200">{layer}</span>
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}
