import { Link } from '@inertiajs/react';
import Reveal from '../components/Reveal';
import { usePrefersReducedMotion } from '../hooks/usePrefersReducedMotion';

function InfraVisual() {
    const reduced = usePrefersReducedMotion();

    return (
        <div className="iqp-glow-border relative aspect-[4/5] w-full max-w-lg overflow-hidden rounded-2xl iqp-glass-strong lg:aspect-square">
            <div className="iqp-dot-grid absolute inset-0 opacity-40" />
            <svg viewBox="0 0 400 420" className="iqp-hero-flow relative h-full w-full p-6" aria-hidden="true">
                <defs>
                    <linearGradient id="iqp-line" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#8b5cf6" />
                        <stop offset="100%" stopColor="#22d3ee" />
                    </linearGradient>
                </defs>
                {[
                    { y: 40, label: 'CRM PLATFORM', w: 160 },
                    { y: 120, label: 'IQPigeon API', w: 180 },
                    { y: 200, label: 'Auth · Queue · Route', w: 200 },
                    { y: 280, label: 'Meta / WhatsApp', w: 170 },
                    { y: 360, label: 'Partner webhooks', w: 160 },
                ].map((node, i) => (
                    <g key={node.label}>
                        <rect
                            x={200 - node.w / 2}
                            y={node.y}
                            width={node.w}
                            height={44}
                            rx={10}
                            fill="rgb(15 23 42 / 0.9)"
                            stroke="url(#iqp-line)"
                            strokeWidth="1"
                        />
                        <text x="200" y={node.y + 28} textAnchor="middle" fill="#e2e8f0" fontSize="11" fontFamily="ui-monospace, monospace">
                            {node.label}
                        </text>
                        {i < 4 && (
                            <line
                                x1="200"
                                y1={node.y + 44}
                                x2="200"
                                y2={node.y + 76}
                                stroke="url(#iqp-line)"
                                strokeWidth="2"
                                strokeDasharray={reduced ? '0' : '8 6'}
                                style={reduced ? {} : { animation: 'iqp-flow-dash 1.2s linear infinite alternate' }}
                            />
                        )}
                        <circle
                            cx="200"
                            cy={node.y + 22}
                            r="4"
                            fill="#22d3ee"
                            className={reduced ? '' : 'iqp-pulse-node'}
                            style={reduced ? {} : { animationDelay: `${i * 0.35}s` }}
                        />
                    </g>
                ))}
                {!reduced && (
                    <>
                        <circle r="3" fill="#8b5cf6">
                            <animateMotion dur="3s" repeatCount="indefinite" path="M200,84 L200,116" />
                        </circle>
                        <circle r="3" fill="#22d3ee">
                            <animateMotion dur="3.5s" repeatCount="indefinite" begin="0.5s" path="M200,324 L200,356" />
                        </circle>
                    </>
                )}
            </svg>
            <p className="absolute bottom-3 left-0 right-0 text-center font-mono text-[10px] uppercase tracking-widest text-slate-500">
                Live infrastructure flow (illustrative)
            </p>
        </div>
    );
}

export default function HeroSection() {
    return (
        <section className="relative overflow-hidden pt-28 pb-20 sm:pt-32 lg:pb-28">
            <div className="iqp-dot-grid pointer-events-none absolute inset-0 opacity-30" />
            <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <Reveal>
                        <p className="font-mono text-xs uppercase tracking-[0.2em] text-violet-400">WhatsApp infrastructure</p>
                    </Reveal>
                    <Reveal delay={80}>
                        <h1 className="mt-4 text-4xl font-semibold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">
                            The WhatsApp infrastructure behind modern CRM platforms.
                        </h1>
                    </Reveal>
                    <Reveal delay={160}>
                        <p className="mt-6 max-w-xl text-lg leading-relaxed text-slate-400">
                            Connect businesses to WhatsApp, send messages through one API, receive events through webhooks, and
                            let every connected business maintain its own Meta relationship.
                        </p>
                    </Reveal>
                    <Reveal delay={240}>
                        <div className="mt-10 flex flex-wrap gap-4">
                            <Link href="/signup" className="iqp-btn-primary inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold text-white">
                                Start building
                                <span aria-hidden="true">→</span>
                            </Link>
                            <Link href="/docs" className="iqp-btn-ghost inline-flex items-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold text-slate-200">
                                Explore the API
                            </Link>
                        </div>
                    </Reveal>
                </div>
                <Reveal delay={120} className="flex justify-center lg:justify-end">
                    <InfraVisual />
                </Reveal>
            </div>
        </section>
    );
}
