export default function Docs() {
    return (
        <main className="min-h-screen bg-slate-50 px-4 py-12 text-slate-900">
            <article className="prose prose-slate mx-auto max-w-3xl">
                <h1>IQPigeon WhatsApp API</h1>
                <p>
                    Base URL: <code>https://whatsappapi.iqpigeon.com/api/v1</code>
                </p>

                <h2>Billing separation</h2>
                <p>
                    Your <strong>IQPigeon platform subscription</strong> (Stripe) pays for API access, dashboard, and
                    infrastructure. <strong>Meta/WhatsApp messaging charges</strong> are billed by Meta to each connected
                    business. IQPigeon does not pay or mark up Meta messaging costs.
                </p>

                <h2>Authentication</h2>
                <pre>{`Authorization: Bearer iqp_live_YOUR_API_KEY`}</pre>
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
                <p>Do not use colon syntax (e.g. <code>messages:send</code>).</p>

                <h2>Connections &amp; Embedded Signup</h2>
                <p>
                    <code>POST /connections</code> starts an onboarding session. Complete Meta Embedded Signup in the
                    dashboard or OAuth flow. Tokens are stored encrypted and never returned by the API.
                </p>

                <h2>Sending messages</h2>
                <pre>{`POST /messages
Idempotency-Key: unique-key-123
{
  "connection_id": "uuid",
  "to": "+15551234567",
  "type": "text",
  "body": "Hello"
}`}</pre>

                <h2>Partner webhooks</h2>
                <p>
                    Register endpoints with <code>POST /webhooks</code> (scope <code>webhooks.write</code>). Events are
                    delivered as JSON POSTs to your public HTTPS URL. Verify{' '}
                    <code>X-IQPigeon-Signature</code> with your endpoint secret; each request includes a timestamp header.
                    Private IPs, link-local, and other SSRF-blocked targets are rejected at registration and delivery.
                </p>
                <p>
                    Delivery is asynchronous with bounded retries: connection failures, timeouts, HTTP 408, 429, and 5xx
                    are retried up to 5 times (default) with exponential backoff (30s, 60s, 120s, 300s between attempts).
                    Other 4xx responses are not retried. View attempt history in the dashboard (
                    <code>webhooks.read</code>). Respond with <code>2xx</code> to acknowledge; idempotent handling of the
                    same <code>event_id</code> on your side is recommended.
                </p>

                <h2>Idempotency</h2>
                <p>
                    Required on mutating POSTs. Same key + same body replays the original response. Same key + different
                    body returns <code>409 idempotency_key_mismatch</code>.
                </p>

                <h2>Errors &amp; rate limits</h2>
                <p>
                    JSON: <code>{`{"success":false,"error":{"code":"…","message":"…"},"request_id":"…"}`}</code>.
                    Exceeding plan limits returns <code>429</code> with <code>Retry-After</code>.
                </p>

                <h2>Usage</h2>
                <p>
                    <code>GET /usage</code> with <code>usage.read</code> returns metered records. API traffic is also logged
                    per partner for dashboard analytics.
                </p>
            </article>
        </main>
    );
}
