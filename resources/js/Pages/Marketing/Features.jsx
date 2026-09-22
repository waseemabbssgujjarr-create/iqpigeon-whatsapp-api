import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Features() {
    return (
        <MarketingPageShell
            title="Platform features"
            lead="Everything your CRM needs to operate WhatsApp at scale — without becoming a Meta billing intermediary."
        >
            <section id="webhooks">
                <h2>Connections &amp; Embedded Signup</h2>
                <p>Start onboarding sessions from the API or dashboard. Each business completes Meta Embedded Signup and keeps its own WABA.</p>
            </section>
            <section className="mt-10" id="usage">
                <h2>REST API</h2>
                <p>Send messages, manage templates and media, and read usage with dot-notation scopes and idempotent POSTs.</p>
            </section>
            <section className="mt-10">
                <h2>Partner webhooks</h2>
                <p>Signed HTTPS deliveries with SSRF protection, bounded retries, and delivery history in the dashboard.</p>
            </section>
        </MarketingPageShell>
    );
}
