import MarketingLayout from '../../marketing/MarketingLayout';
import ArchitectureSection from '../../marketing/sections/ArchitectureSection';
import BillingSeparationSection from '../../marketing/sections/BillingSeparationSection';
import CapabilityRail from '../../marketing/sections/CapabilityRail';
import DeveloperCtaSection from '../../marketing/sections/DeveloperCtaSection';
import EmbeddedSignupSection from '../../marketing/sections/EmbeddedSignupSection';
import FaqSection from '../../marketing/sections/FaqSection';
import FinalCtaSection from '../../marketing/sections/FinalCtaSection';
import HeroSection from '../../marketing/sections/HeroSection';
import ObservabilitySection from '../../marketing/sections/ObservabilitySection';
import PlatformShowcaseSection from '../../marketing/sections/PlatformShowcaseSection';
import PricingSection from '../../marketing/sections/PricingSection';
import ReliabilitySection from '../../marketing/sections/ReliabilitySection';
import SecuritySection from '../../marketing/sections/SecuritySection';
import UseCasesSection from '../../marketing/sections/UseCasesSection';
import WhatsAppExperienceSection from '../../marketing/sections/WhatsAppExperienceSection';

export default function Home({ plans = [] }) {
    return (
        <MarketingLayout>
            <HeroSection />
            <CapabilityRail />
            <PlatformShowcaseSection />
            <ArchitectureSection />
            <WhatsAppExperienceSection />
            <ObservabilitySection />
            <ReliabilitySection />
            <EmbeddedSignupSection />
            <BillingSeparationSection />
            <SecuritySection />
            <UseCasesSection />
            <PricingSection plans={plans} />
            <DeveloperCtaSection />
            <FaqSection />
            <FinalCtaSection />
        </MarketingLayout>
    );
}
