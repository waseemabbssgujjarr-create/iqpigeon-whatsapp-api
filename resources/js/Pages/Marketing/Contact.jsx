import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Contact() {
    return (
        <MarketingPageShell title="Contact" lead="Questions about integrating IQPigeon WhatsApp API into your CRM platform?">
            <p>Email: support@iqpigeon.com (placeholder — configure production contact in your deployment).</p>
            <p className="mt-4">For billing and API access, create an account and complete Stripe checkout in the dashboard.</p>
        </MarketingPageShell>
    );
}
