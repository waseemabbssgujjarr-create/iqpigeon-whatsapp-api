import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function WhatsAppExperienceSection() {
    return (
        <SectionShell tone="lift" bridge>
            <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <SectionHeader
                    eyebrow="End-customer experience"
                    title="From CRM action to WhatsApp conversation."
                    description="Your users stay in the CRM. Their customers see WhatsApp — replies return as signed webhooks."
                />
                <Reveal className="grid gap-6 sm:grid-cols-[1.2fr_0.8fr]">
                    <div className="iqp-product-panel p-4">
                        <p className="font-mono text-[10px] text-slate-500">CRM dashboard (demo)</p>
                        <p className="mt-3 text-sm text-white">Lead: Jordan Lee</p>
                        <button type="button" className="mt-4 rounded-lg bg-violet-600/30 px-3 py-2 text-xs text-violet-100">
                            Send WhatsApp template
                        </button>
                        <p className="mt-4 font-mono text-[10px] text-emerald-400">→ webhook: message.delivered</p>
                    </div>
                    <div className="mx-auto w-full max-w-[220px] rounded-[1.75rem] border-4 border-slate-700 bg-slate-950 p-3 shadow-2xl">
                        <div className="rounded-2xl bg-slate-900 p-3">
                            <p className="text-[10px] text-emerald-400">WhatsApp</p>
                            <div className="mt-3 space-y-2 text-[11px]">
                                <div className="rounded-lg bg-emerald-700/30 p-2 text-slate-200">Hi Jordan — your viewing is confirmed.</div>
                                <div className="rounded-lg bg-slate-800 p-2 text-slate-400">Perfect, see you then</div>
                            </div>
                        </div>
                    </div>
                </Reveal>
            </div>
        </SectionShell>
    );
}
