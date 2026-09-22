import MarketingPhoto from '../components/MarketingPhoto';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { marketingPhotos } from '../constants/marketingPhotos';

export default function WhatsAppExperienceSection() {
    return (
        <SectionShell tone="clear" bridge>
            <div className="grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
                <SectionHeader
                    eyebrow="Customer experience"
                    title="CRM workflows. WhatsApp conversations."
                    description="Your users stay in the product they already trust. Their customers receive familiar WhatsApp messages — replies and status events flow back through signed webhooks."
                />
                <Reveal className="flex justify-center lg:justify-end">
                    <MarketingPhoto {...marketingPhotos.messaging} className="w-full max-w-[16rem] sm:max-w-[18rem]" />
                </Reveal>
            </div>
        </SectionShell>
    );
}
