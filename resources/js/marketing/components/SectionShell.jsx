import Reveal from './Reveal';

const tones = {
    ink: 'iqp-section--ink',
    navy: 'iqp-section--navy',
    lift: 'iqp-section--lift',
    glow: 'iqp-section--glow',
    grid: 'iqp-section--grid',
};

export default function SectionShell({ id, tone = 'ink', bridge = false, className = '', children }) {
    return (
        <>
            {bridge && <div className="iqp-section-bridge" aria-hidden="true" />}
            <section id={id} className={`iqp-section ${tones[tone] || tones.ink} ${className}`}>
                <div className="iqp-container">{children}</div>
            </section>
        </>
    );
}

export function SectionHeader({ eyebrow, title, description, align = 'left', size = 'lg' }) {
    const TitleTag = size === 'xl' ? 'h2' : 'h2';
    return (
        <Reveal className={align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl'}>
            {eyebrow && <p className="font-mono text-xs uppercase tracking-[0.2em] text-violet-400">{eyebrow}</p>}
            <TitleTag className={`mt-3 font-semibold text-white ${size === 'xl' ? 'iqp-headline-xl' : 'iqp-headline-lg'}`}>{title}</TitleTag>
            {description && <p className="mt-4 text-lg leading-relaxed text-slate-400">{description}</p>}
        </Reveal>
    );
}
