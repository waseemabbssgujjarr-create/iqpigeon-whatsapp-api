import Reveal from '../components/Reveal';

export default function BillingSeparationSection() {
    return (
        <section className="py-24 sm:py-28" id="billing">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="max-w-3xl text-3xl font-semibold text-white sm:text-4xl">
                        One platform fee. Your customers keep their Meta relationship.
                    </h2>
                </Reveal>
                <Reveal delay={100} className="mt-12 grid gap-6 md:grid-cols-2">
                    <div className="iqp-glow-border rounded-2xl iqp-glass p-8">
                        <h3 className="font-mono text-sm uppercase tracking-widest text-violet-400">IQPigeon platform</h3>
                        <ul className="mt-6 space-y-3 text-slate-300">
                            <li>Stripe platform subscription</li>
                            <li>API infrastructure &amp; dashboard</li>
                            <li>API keys &amp; scopes</li>
                            <li>Partner webhooks &amp; delivery retries</li>
                            <li>Usage metering &amp; audit trail</li>
                        </ul>
                    </div>
                    <div className="rounded-2xl border border-cyan-500/20 bg-slate-900/40 p-8">
                        <h3 className="font-mono text-sm uppercase tracking-widest text-cyan-400">Connected business / Meta</h3>
                        <ul className="mt-6 space-y-3 text-slate-300">
                            <li>WhatsApp Business Account (WABA)</li>
                            <li>Phone number &amp; messaging limits</li>
                            <li>Meta billing for WhatsApp usage</li>
                            <li>Direct Meta commercial relationship</li>
                        </ul>
                        <p className="mt-6 text-sm text-slate-500">
                            IQPigeon does not pay, markup, or pool Meta messaging charges for your customers.
                        </p>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
