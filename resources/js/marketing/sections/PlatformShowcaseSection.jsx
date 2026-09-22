import MarketingPhoto from '../components/MarketingPhoto';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { marketingPhotos } from '../constants/marketingPhotos';

const points = [
    {
        title: 'Embedded Signup',
        body: 'Onboard each business into WhatsApp with Meta’s flow, scoped to your platform.',
    },
    {
        title: 'Unified API',
        body: 'Send templates and session messages with API keys, idempotency, and plan-aware limits.',
    },
    {
        title: 'Partner webhooks',
        body: 'Receive delivery and inbound events with signing, retries, and observability in your dashboard.',
    },
];

export default function PlatformShowcaseSection() {
    return (
        <SectionShell tone="clear" bridge id="platform">
            <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">
                <Reveal className="order-2 lg:order-1">
                    <MarketingPhoto {...marketingPhotos.platform} aspect="landscape" className="mx-auto w-full max-w-md lg:max-w-lg" />
                </Reveal>
                <div className="order-1 lg:order-2">
                    <SectionHeader
                        eyebrow="Platform"
                        title="Built for teams shipping customer messaging inside a CRM."
                        description="IQPigeon sits between your product and Meta — so you focus on workflows, not WhatsApp plumbing."
                    />
                    <ul className="mt-10 space-y-8">
                        {points.map((item, i) => (
                            <Reveal key={item.title} delay={60 + i * 40}>
                                <li>
                                    <h3 className="text-lg font-semibold text-white">{item.title}</h3>
                                    <p className="iqp-body mt-2 max-w-md">{item.body}</p>
                                </li>
                            </Reveal>
                        ))}
                    </ul>
                </div>
            </div>
        </SectionShell>
    );
}
