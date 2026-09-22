import { Link } from '@inertiajs/react';
import HeroComposition from '../components/HeroComposition';
import SectionShell from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function HeroSection() {
    return (
        <SectionShell tone="glow" className="!pt-28 lg:!pt-32">
            <div className="grid items-center gap-14 lg:grid-cols-[1.05fr_1fr] lg:gap-10 xl:gap-16">
                <div>
                    <Reveal>
                        <p className="font-mono text-xs uppercase tracking-[0.2em] text-violet-400">WhatsApp infrastructure</p>
                    </Reveal>
                    <Reveal delay={60}>
                        <h1 className="iqp-headline-xl mt-4 font-semibold text-white">
                            The WhatsApp infrastructure behind modern CRM platforms.
                        </h1>
                    </Reveal>
                    <Reveal delay={120}>
                        <p className="mt-6 max-w-xl text-lg leading-relaxed text-slate-400">
                            Connect businesses to WhatsApp, send messages through one API, receive events through webhooks, and let every
                            connected business maintain its own Meta relationship.
                        </p>
                    </Reveal>
                    <Reveal delay={180}>
                        <div className="mt-10 flex flex-wrap gap-4">
                            <Link href="/signup" className="iqp-btn-primary inline-flex items-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold text-white">
                                Start building <span aria-hidden="true">→</span>
                            </Link>
                            <Link href="/docs" className="iqp-btn-ghost inline-flex items-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold text-slate-200">
                                Explore the API
                            </Link>
                        </div>
                    </Reveal>
                    <Reveal delay={220}>
                        <div className="mt-10 flex flex-wrap gap-4 font-mono text-[10px] uppercase tracking-wider text-slate-500">
                            <span className="rounded-full border border-slate-700/60 px-3 py-1">REST API</span>
                            <span className="rounded-full border border-slate-700/60 px-3 py-1">Webhooks</span>
                            <span className="rounded-full border border-slate-700/60 px-3 py-1">Embedded Signup</span>
                        </div>
                    </Reveal>
                </div>
                <Reveal delay={100}>
                    <HeroComposition />
                </Reveal>
            </div>
        </SectionShell>
    );
}
