import { Link } from '@inertiajs/react';
import SectionShell from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function FinalCtaSection() {
    return (
        <SectionShell tone="glow" className="!pb-28">
            <Reveal className="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/80 px-8 py-20 text-center lg:py-28">
                <svg className="pointer-events-none absolute inset-0 h-full w-full opacity-30" aria-hidden="true">
                    {[...Array(6)].map((_, i) => (
                        <line key={i} x1={`${15 + i * 14}%`} y1="0" x2="50%" y2="100%" stroke="#8b5cf6" strokeWidth="1" />
                    ))}
                </svg>
                <h2 className="iqp-headline-xl relative font-semibold text-white">Build your CRM&apos;s WhatsApp layer.</h2>
                <div className="relative mt-10 flex flex-wrap justify-center gap-4">
                    <Link href="/signup" className="iqp-btn-primary rounded-xl px-8 py-4 text-sm font-semibold text-white">
                        Start building
                    </Link>
                    <Link href="/docs" className="iqp-btn-ghost rounded-xl px-8 py-4 text-sm font-semibold text-slate-200">
                        Read docs
                    </Link>
                </div>
            </Reveal>
        </SectionShell>
    );
}
