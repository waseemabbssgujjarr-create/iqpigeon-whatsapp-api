import { Link } from '@inertiajs/react';
import MarketingPhoto from '../components/MarketingPhoto';
import SectionShell from '../components/SectionShell';
import Reveal from '../components/Reveal';
import { marketingPhotos } from '../constants/marketingPhotos';

export default function HeroSection() {
    return (
        <SectionShell tone="clear" className="!pt-28 lg:!pt-36">
            <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div className="max-w-xl">
                    <Reveal>
                        <p className="text-sm font-medium uppercase tracking-[0.18em] text-violet-300/90">WhatsApp infrastructure</p>
                    </Reveal>
                    <Reveal delay={60}>
                        <h1 className="iqp-headline-xl mt-5 font-semibold text-white">
                            WhatsApp infrastructure for CRM platforms.
                        </h1>
                    </Reveal>
                    <Reveal delay={120}>
                        <p className="iqp-lead mt-6">
                            Connect businesses to WhatsApp, deliver messages through one API, and stream signed events back to your product —
                            without owning your customers&apos; Meta billing relationship.
                        </p>
                    </Reveal>
                    <Reveal delay={180}>
                        <div className="mt-10 flex flex-wrap gap-4">
                            <Link href="/signup" className="iqp-btn-primary inline-flex items-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold text-white">
                                Start building
                            </Link>
                            <Link href="/docs" className="iqp-btn-ghost inline-flex items-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold text-slate-100">
                                API documentation
                            </Link>
                        </div>
                    </Reveal>
                </div>
                <Reveal delay={100} className="flex justify-center lg:justify-end">
                    <MarketingPhoto
                        {...marketingPhotos.hero}
                        priority
                        className="w-full max-w-[22rem] sm:max-w-[24rem]"
                    />
                </Reveal>
            </div>
        </SectionShell>
    );
}
