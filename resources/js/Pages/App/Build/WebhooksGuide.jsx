import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import { webhookSignaturePhp } from '../../../components/build/apiDocSnippets';
import { GuideIntro, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function WebhooksGuide() {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Webhooks">
                <GuideIntro
                    what="Tell IQPigeon where to POST WhatsApp events."
                    when="Setting up inbound message handling in your CRM."
                />

                <Link href="/app/webhooks" className="mb-6 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">
                    Configure webhook in dashboard
                </Link>

                <DocBlock title="Dashboard flow">
                    <p>Webhook URL → Events (incoming + status) → Save &amp; test webhook</p>
                    <p className="mt-2">✓ Reachable when test delivery HTTP status is successful (webhook.test event).</p>
                </DocBlock>

                <DocBlock title="B. Webhook signature (events)" code={webhookSignaturePhp}>
                    <p>HMAC-SHA256( webhook_endpoint_secret, timestamp + &quot;.&quot; + raw_body )</p>
                </DocBlock>

                <DocBlock title="A. Onboarding return signature (Connect WhatsApp)">
                    <p>
                        Separate from webhooks. Configure <strong>integration signing secret</strong> in Settings. Algorithm: sorted query
                        string HMAC. See{' '}
                        <Link href="/app/build/whatsapp-connection" className="text-violet-400 hover:underline">
                            Connect WhatsApp
                        </Link>
                        .
                    </p>
                </DocBlock>

                <SecurityNote>
                    Validate timestamp to limit replay. Dedupe with X-IQP-Event-Id. Retries on 408, 429, 5xx with backoff.
                </SecurityNote>
            </BuildCrmLayout>
        </AppLayout>
    );
}
