import { Link } from '@inertiajs/react';
import FirstApiRequestGuide from '../../../components/FirstApiRequestGuide';
import { DocBlock } from '../../../components/build/DocBlock';
import { postMessageResponse, standardErrorShape } from '../../../components/build/apiDocSnippets';
import { GuideIntro, GuideMeta, RequestIdNote, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function SendMessages({ apiBaseUrl, connectionUuid }) {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Send messages">
                <GuideIntro
                    what="Send outbound WhatsApp messages from your CRM through IQPigeon."
                    when="Your server replies to customers or sends notifications."
                />

                <GuideMeta method="POST" endpoint="/api/v1/messages" scope="messages.send" />

                <FirstApiRequestGuide apiBaseUrl={apiBaseUrl} connectionId={connectionUuid} />

                <DocBlock title="Response (202 Accepted)" code={postMessageResponse}>
                    <p>Message fields match MessageResource (id, connection_id, status, to, type, body, …).</p>
                </DocBlock>

                <DocBlock title="Error — connection not found (404)" code={standardErrorShape} />

                <SecurityNote>
                    Requires Idempotency-Key header. Partner must be active (subscription). connection_id must belong to your partner.
                </SecurityNote>
                <RequestIdNote />

                <Link href="/app/build/idempotency" className="mt-4 inline-block text-sm text-violet-400 hover:underline">
                    Idempotency guide →
                </Link>
            </BuildCrmLayout>
        </AppLayout>
    );
}
