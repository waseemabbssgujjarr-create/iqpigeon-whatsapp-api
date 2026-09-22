import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

const layers = [
    {
        title: 'Your CRM',
        body: 'Triggers sends, stores conversation context, and reacts to webhook payloads.',
    },
    {
        title: 'IQPigeon',
        body: 'Authentication, validation, queues, usage metering, and partner webhook delivery.',
    },
    {
        title: 'Meta / WhatsApp',
        body: 'Official Business Platform — each connected business maintains its own Meta relationship.',
    },
];

export default function ArchitectureSection() {
    return (
        <SectionShell tone="clear" bridge id="architecture">
            <SectionHeader
                title="One infrastructure layer between your CRM and WhatsApp."
                description="A clear separation of responsibilities — your product owns UX; we operate the messaging rail."
                align="center"
            />
            <div className="mt-14 grid gap-6 md:grid-cols-3">
                {layers.map((layer, i) => (
                    <Reveal key={layer.title} delay={i * 50}>
                        <article className="iqp-card h-full rounded-2xl p-8">
                            <p className="text-sm font-medium text-violet-300">{String(i + 1).padStart(2, '0')}</p>
                            <h3 className="mt-3 text-xl font-semibold text-white">{layer.title}</h3>
                            <p className="iqp-body mt-3">{layer.body}</p>
                        </article>
                    </Reveal>
                ))}
            </div>
        </SectionShell>
    );
}
