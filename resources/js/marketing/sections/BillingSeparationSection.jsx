import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function BillingSeparationSection() {
    return (
        <SectionShell tone="glow" bridge id="billing">
            <SectionHeader
                title="One platform fee. Your customers keep their Meta relationship."
                align="center"
            />
            <Reveal delay={80} className="mt-14 grid gap-6 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
                <div className="iqp-glow-border rounded-2xl iqp-glass p-8 lg:p-10">
                    <h3 className="font-mono text-sm uppercase tracking-widest text-violet-400">IQPigeon</h3>
                    <ul className="mt-6 space-y-3 text-slate-300">
                        <li>Platform subscription (Stripe)</li>
                        <li>API &amp; dashboard</li>
                        <li>API keys &amp; webhooks</li>
                        <li>Usage &amp; infrastructure</li>
                    </ul>
                </div>
                <div className="flex flex-col items-center justify-center px-4 py-4 lg:py-0">
                    <div className="h-16 w-px bg-gradient-to-b from-transparent via-violet-500/50 to-transparent lg:h-full lg:min-h-[200px]" aria-hidden="true" />
                    <p className="mt-2 font-mono text-[10px] uppercase tracking-widest text-slate-600">billing boundary</p>
                </div>
                <div className="rounded-2xl border border-cyan-500/25 bg-slate-900/50 p-8 lg:p-10">
                    <h3 className="font-mono text-sm uppercase tracking-widest text-cyan-400">Connected business / Meta</h3>
                    <ul className="mt-6 space-y-3 text-slate-300">
                        <li>WABA &amp; phone number</li>
                        <li>Meta billing for messaging</li>
                        <li>Direct Meta commercial terms</li>
                    </ul>
                    <p className="mt-6 text-sm text-slate-500">IQPigeon does not pay or markup Meta messaging fees.</p>
                </div>
            </Reveal>
        </SectionShell>
    );
}
