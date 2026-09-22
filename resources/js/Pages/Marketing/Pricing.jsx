import { Link } from '@inertiajs/react';
import MarketingLayout from '../../marketing/MarketingLayout';
import PricingSection from '../../marketing/sections/PricingSection';
import Reveal from '../../marketing/components/Reveal';

export default function Pricing({ plans = [] }) {
    return (
        <MarketingLayout>
            <div className="pt-24">
                <PricingSection plans={plans} />
                <Reveal className="mx-auto max-w-2xl px-4 pb-20 text-center text-sm text-slate-500">
                    After signup, complete checkout in the dashboard. Your account activates only after Stripe webhook verification — not
                    from the browser return URL.{' '}
                    <Link href="/signup" className="text-violet-400 hover:underline">
                        Create account
                    </Link>
                </Reveal>
            </div>
        </MarketingLayout>
    );
}
