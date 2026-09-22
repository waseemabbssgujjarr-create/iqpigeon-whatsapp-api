import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function DeveloperExperienceSection() {
    return (
        <SectionShell tone="grid" bridge id="developer-workspace">
            <SectionHeader
                eyebrow="Developer workspace"
                title="One API key. One endpoint. One webhook."
                description="Demo console — credentials are placeholders only."
                align="center"
            />
            <Reveal delay={80} className="mx-auto mt-12 max-w-4xl">
                <div className="iqp-glow-border grid gap-0 overflow-hidden rounded-2xl border border-slate-700/50 lg:grid-cols-2">
                    <div className="bg-slate-950 p-6 font-mono text-xs text-slate-300">
                        <p className="text-violet-400">POST /api/v1/messages</p>
                        <p className="mt-4 text-emerald-400">200 OK</p>
                        <p className="mt-2 text-slate-500">{`{ "id": "msg_demo_01", "status": "queued" }`}</p>
                    </div>
                    <div className="border-t border-slate-800 bg-slate-900/80 p-6 font-mono text-xs lg:border-l lg:border-t-0">
                        <p>
                            <span className="text-slate-500">API key:</span> iqp_live_••••••••
                        </p>
                        <p className="mt-3">
                            <span className="text-slate-500">Webhook:</span> message.delivered
                        </p>
                        <p className="mt-3 text-slate-500">Logs: 3 events (example)</p>
                    </div>
                </div>
            </Reveal>
        </SectionShell>
    );
}
