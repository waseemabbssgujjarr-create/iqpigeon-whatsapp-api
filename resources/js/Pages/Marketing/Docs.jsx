import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Docs() {
    return (
        <MarketingPageShell title="Documentation" lead="Quick reference for the IQPigeon WhatsApp API platform.">
            <p>
                Base URL: <code className="rounded bg-slate-800 px-2 py-1 font-mono text-sm">https://whatsappapi.iqpigeon.com/api/v1</code>
            </p>

            <h2>Billing separation</h2>
            <p>
                Your <strong>IQPigeon platform subscription</strong> (Stripe) pays for API access, dashboard, and infrastructure.{' '}
                <strong>Meta/WhatsApp messaging charges</strong> are billed by Meta to each connected business. IQPigeon does not pay or
                mark up Meta messaging costs.
            </p>

            <h2>Authentication</h2>
            <pre className="overflow-x-auto rounded-xl bg-slate-900 p-4 font-mono text-sm">{`Authorization: Bearer iqp_live_YOUR_API_KEY`}</pre>
            <p>Keys are shown once at creation. Store them securely. Revoked or expired keys return 401.</p>

            <h2>Scopes (dot notation only)</h2>
            <ul>
                <li><code>messages.read</code> — list and read messages</li>
                <li><code>messages.send</code> — queue outbound messages</li>
                <li><code>connections.read</code> — list connections</li>
                <li><code>connections.write</code> — start onboarding sessions</li>
                <li><code>templates.read</code> / <code>templates.write</code></li>
                <li><code>media.read</code> / <code>media.write</code></li>
                <li><code>webhooks.read</code> / <code>webhooks.write</code></li>
                <li><code>usage.read</code></li>
            </ul>

            <h2>Connections &amp; Embedded Signup</h2>
            <p>
                <code>POST /connections</code> starts an onboarding session. Complete Meta Embedded Signup in the dashboard or OAuth flow.
                Tokens are stored encrypted and never returned by the API.
            </p>

            <h2>Sending messages</h2>
            <pre className="overflow-x-auto rounded-xl bg-slate-900 p-4 font-mono text-sm">{`POST /messages
Idempotency-Key: unique-key-123
{
  "connection_id": "uuid",
  "to": "+15551234567",
  "type": "text",
  "body": "Hello"
}`}</pre>

            <h2>Partner webhooks</h2>
            <p>
                Register endpoints with <code>POST /webhooks</code>. Deliveries are signed, SSRF-checked, and retried on transient failures
                with bounded backoff. See dashboard delivery history with <code>webhooks.read</code>.
            </p>

            <h2>Idempotency</h2>
            <p>
                Required on mutating POSTs. Same key + same body replays the original response. Same key + different body returns{' '}
                <code>409 idempotency_key_mismatch</code>.
            </p>
        </MarketingPageShell>
    );
}
