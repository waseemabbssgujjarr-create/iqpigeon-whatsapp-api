import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import { webhookBodyShape, webhookSignaturePhp } from '../../../components/build/apiDocSnippets';
import { GuideIntro, RequestIdNote, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function ReceiveMessages() {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Receive messages">
                <GuideIntro
                    what="Inbound WhatsApp messages and status updates delivered to your HTTPS webhook."
                    when="Customers message your connected WhatsApp Business number."
                />

                <DocBlock title="Configure endpoint">
                    <Link href="/app/webhooks" className="text-violet-400 hover:underline">
                        Dashboard → Webhooks
                    </Link>
                    <p className="mt-2">Or POST /api/v1/webhooks with scope webhooks.write (requires Idempotency-Key).</p>
                </DocBlock>

                <DocBlock title="Delivery body" code={webhookBodyShape} />

                <DocBlock title="Verify signature (required on your server)" code={webhookSignaturePhp}>
                    <p>Headers: X-IQP-Signature, X-IQP-Timestamp, X-IQP-Event-Id, X-IQP-Request-Id</p>
                </DocBlock>

                <SecurityNote>
                    Webhook secret verifies inbound <em>events</em>. Integration signing secret verifies onboarding return URLs — different
                    purposes. See Settings.
                </SecurityNote>

                <Link href="/app/build/message-status" className="mt-4 inline-block text-sm text-violet-400 hover:underline">
                    Message status payloads →
                </Link>
                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
