import Reveal from './Reveal';

const tones = {
    ink: 'iqp-section--ink',
    navy: 'iqp-section--navy',
    lift: 'iqp-section--lift',
    glow: 'iqp-section--glow',
    grid: 'iqp-section--grid',
    clear: 'iqp-section--clear',
};

export default function SectionShell({ id, tone = 'clear', bridge = false, className = '', children }) {
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
            {eyebrow && <p className="text-sm font-medium uppercase tracking-[0.16em] text-violet-300/90">{eyebrow}</p>}
            <TitleTag className={`mt-4 font-semibold text-white ${size === 'xl' ? 'iqp-headline-xl' : 'iqp-headline-lg'}`}>{title}</TitleTag>
            {description && (
                <p className={`iqp-lead mt-5 max-w-2xl ${align === 'center' ? 'mx-auto' : ''}`}>{description}</p>
            )}
        </Reveal>
    );
}
