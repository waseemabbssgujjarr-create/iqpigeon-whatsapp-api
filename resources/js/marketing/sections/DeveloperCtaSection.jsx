import { Link } from '@inertiajs/react';
import Reveal from '../components/Reveal';

export default function DeveloperCtaSection() {
    return (
        <section className="py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Reveal className="iqp-glow-border rounded-3xl iqp-glass-strong px-8 py-16 text-center sm:px-16">
                    <h2 className="text-3xl font-semibold text-white sm:text-4xl">Build your first WhatsApp integration today.</h2>
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
            </div>
        </section>
    );
}
