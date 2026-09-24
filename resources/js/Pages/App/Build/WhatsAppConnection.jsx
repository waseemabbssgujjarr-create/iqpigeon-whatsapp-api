import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import {
    getConnectionRequest,
    getConnectionResponseActive,
    onboardingReturnVerifyPhp,
    postConnectionsRequest,
    postConnectionsResponse,
} from '../../../components/build/apiDocSnippets';
import { GuideIntro, GuideMeta, RequestIdNote, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function WhatsAppConnection({ apiBaseUrl, platformUrl, allowedReturnUrls }) {
    const returnUrl = allowedReturnUrls?.length ? allowedReturnUrls[0] : 'https://crm.example.com/whatsapp/callback';

    const flowSteps = [
        'CRM SERVER → POST /api/v1/connections',
        'Receive data.onboarding_url',
        'CRM BROWSER opens onboarding_url',
        'Meta Embedded Signup (via IQPigeon /oauth/meta/start)',
        'IQPigeon /oauth/meta/callback stores Meta credentials server-side',
        'Browser redirect → allowlisted CRM return_url',
        'CRM SERVER → GET /api/v1/connections/{connection_id}',
        'Only when status=active → mark WhatsApp connected in CRM',
    ];

    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Connect WhatsApp (CRM)">
                <GuideIntro
                    what="Hosted WhatsApp onboarding for your CRM customers. Meta tokens never enter your CRM."
                    when="End users click Connect WhatsApp inside your product."
                />

                <SecurityNote>
                    Do not trust the browser callback alone. Always verify the connection server-to-server with GET
                    /api/v1/connections/&#123;connection_id&#125; before marking WhatsApp connected.
                </SecurityNote>

                <DocBlock title="End-to-end flow">
                    <ol className="list-decimal space-y-1 pl-5">
                        {flowSteps.map((step) => (
                            <li key={step}>{step}</li>
                        ))}
                    </ol>
                </DocBlock>

                <DocBlock title="CRM UI pattern">
                    <p>Not connected → [ Connect WhatsApp ]</p>
                    <p>Connected → ✓ + phone · Manage · Send test message</p>
                </DocBlock>

                <GuideMeta method="POST" endpoint="/api/v1/connections" scope="connections.write" />

                <DocBlock title="Start onboarding (CRM server)" code={postConnectionsRequest(apiBaseUrl, returnUrl)}>
                    <p>Headers: Authorization, Content-Type, Idempotency-Key (required), Accept.</p>
                    <p>
                        Allowlist <code className="text-slate-300">{returnUrl}</code> in{' '}
                        <Link href="/app/settings" className="text-violet-400 hover:underline">
                            Settings → CRM integration
                        </Link>
                        .
                    </p>
                </DocBlock>

                <DocBlock title="Response (201)" code={postConnectionsResponse}>
                    <p>
                        Redirect the user&apos;s browser to <code className="text-slate-300">data.onboarding_url</code>. Store{' '}
                        <code className="text-slate-300">data.connection.id</code> (UUID) keyed by <code className="text-slate-300">external_ref</code>.
                    </p>
                    <RequestIdNote />
                </DocBlock>

                <DocBlock title="Verify connection (CRM server — required)" code={getConnectionRequest(apiBaseUrl, 'CONNECTION_UUID')}>
                    <p>Required scope: connections.read</p>
                </DocBlock>

                <DocBlock title="When status is active" code={getConnectionResponseActive}>
                    <p>Only now should your CRM show WhatsApp as connected for that customer.</p>
                </DocBlock>

                <DocBlock title="Onboarding return URL (browser)">
                    <p>Query parameters may include: connection_id, status, external_ref, timestamp, and optionally signature.</p>
                    <p className="mt-2 font-medium text-slate-300">A. Onboarding return signature (optional)</p>
                    <p>
                        If you configured an <strong>integration signing secret</strong> in Settings, IQPigeon signs sorted query params with{' '}
                        <code className="text-slate-300">HMAC-SHA256(http_build_query(params), integration_signing_secret)</code>. This is{' '}
                        <em>not</em> the same as webhook signing.
                    </p>
                    <p className="mt-2 font-medium text-slate-300">B. Webhook signature (inbound events)</p>
                    <p>
                        Webhooks use <code className="text-slate-300">HMAC-SHA256(timestamp + &quot;.&quot; + raw_body, webhook_endpoint_secret)</code>{' '}
                        with headers X-IQP-Signature, X-IQP-Timestamp, X-IQP-Event-Id, X-IQP-Request-Id.
                    </p>
                    <p className="mt-2">
                        If no integration signing secret exists, the return URL may omit signature — you must still call GET /connections/&#123;id&#125;.
                    </p>
                </DocBlock>

                <DocBlock title="Optional: verify return URL signature (server)" code={onboardingReturnVerifyPhp} />

                <DocBlock title="If Meta onboarding fails or user lands on IQPigeon dashboard">
                    <p>
                        OAuth errors redirect to the IQPigeon dashboard, not your CRM. Your CRM must not assume success from the browser alone.
                    </p>
                    <p className="mt-2">
                        Poll <code className="text-slate-300">GET /connections/&#123;id&#125;</code> until status is active, error, or timeout. If still pending
                        after session expiry, start a new onboarding or remove the draft connection.
                    </p>
                </DocBlock>

                <DocBlock
                    title="Browser redirect target"
                    code={`${platformUrl}/oauth/meta/start?token=…`}
                >
                    <p>Your CRM should never construct Meta OAuth URLs directly.</p>
                </DocBlock>

                <Link href="/app/connections" className="text-sm text-violet-400 hover:underline">
                    Connect from IQPigeon dashboard (no return_url) →
                </Link>
            </BuildCrmLayout>
        </AppLayout>
    );
}
