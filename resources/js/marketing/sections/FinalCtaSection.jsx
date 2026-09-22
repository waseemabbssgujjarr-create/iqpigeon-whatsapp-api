import { Link } from '@inertiajs/react';
import Reveal from '../components/Reveal';

export default function FinalCtaSection() {
    return (
        <section className="relative overflow-hidden py-28">
            <div className="pointer-events-none absolute inset-0 opacity-40">
                <svg className="h-full w-full" aria-hidden="true">
                    {[...Array(8)].map((_, i) => (
                        <line
                            key={i}
                            x1={`${10 + i * 12}%`}
                            y1="0%"
                            x2="50%"
                            y2="100%"
                            stroke="url(#final-grad)"
                            strokeWidth="1"
                            opacity="0.35"
                        />
                    ))}
                    <defs>
                        <linearGradient id="final-grad" x1="0" y1="0" x2="0" y2="1">
                            <stop stopColor="#8b5cf6" />
                            <stop stopColor="#050810" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div className="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <Reveal>
                    <h2 className="text-4xl font-semibold tracking-tight text-white sm:text-5xl">Build your CRM&apos;s WhatsApp layer.</h2>
                    <div className="mt-10 flex flex-wrap justify-center gap-4">
                        <Link href="/signup" className="iqp-btn-primary rounded-xl px-8 py-4 text-sm font-semibold text-white">
                            Start building
                        </Link>
                        <Link href="/docs" className="iqp-btn-ghost rounded-xl px-8 py-4 text-sm font-semibold text-slate-200">
                            Read docs
                        </Link>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
