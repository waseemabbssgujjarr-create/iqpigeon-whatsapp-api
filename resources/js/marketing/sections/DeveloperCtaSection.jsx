import { Link } from '@inertiajs/react';
import SectionShell from '../components/SectionShell';
import Reveal from '../components/Reveal';

export default function DeveloperCtaSection() {
    return (
        <SectionShell tone="lift">
            <Reveal className="iqp-glow-border mx-auto max-w-4xl rounded-3xl iqp-glass-strong px-8 py-16 text-center lg:px-16">
                <h2 className="iqp-headline-lg font-semibold text-white">Build your first WhatsApp integration today.</h2>
                <div className="mx-auto mt-10 flex max-w-lg flex-wrap justify-center gap-4 font-mono text-sm text-slate-300">
                    <span className="rounded-lg border border-slate-700 px-4 py-2">1 API key</span>
                    <span className="rounded-lg border border-slate-700 px-4 py-2">1 endpoint</span>
                    <span className="rounded-lg border border-slate-700 px-4 py-2">1 webhook</span>
                </div>
                <div className="mt-10 flex flex-wrap justify-center gap-4">
                    <Link href="/docs" className="iqp-btn-ghost rounded-xl px-6 py-3 text-sm font-semibold text-white">
                        Read documentation
                    </Link>
                    <Link href="/signup" className="iqp-btn-primary rounded-xl px-6 py-3 text-sm font-semibold text-white">
                        Create account
                    </Link>
                </div>
            </Reveal>
        </SectionShell>
    );
}
