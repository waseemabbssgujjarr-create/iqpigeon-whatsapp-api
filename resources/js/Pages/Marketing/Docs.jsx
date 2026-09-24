import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Docs() {
    return (
        <MarketingPageShell
            title="Documentation"
            lead="Developer guide for CRM platforms integrating IQPigeon WhatsApp infrastructure."
        >
            <h2>What this API is</h2>
            <p>
                IQPigeon is WhatsApp infrastructure for external CRMs and SaaS products — not a chat inbox or bot builder. Your product
                calls IQPigeon with an API key; each business connects its WhatsApp number as a <strong>connection</strong>.
            </p>

            <h2>Quick start</h2>
            <ol>
                <li>Create a platform account and activate your subscription.</li>
                <li>Connect WhatsApp (Embedded Signup) in the dashboard.</li>
                <li>Create an API key (shown once) and store it in your CRM backend.</li>
                <li>Register a CRM webhook URL for inbound messages and delivery events.</li>
                <li>Send <code>POST /api/v1/messages</code> with your connection ID.</li>
            </ol>

            <h2>API key vs WhatsApp connection</h2>
            <ul>
                <li>
                    <strong>API key</strong> — answers &quot;Who is calling?&quot; (<code>Authorization: Bearer …</code>). Dashboard session
                    login is separate from API keys.
                </li>
                <li>
                    <strong>Connection</strong> — answers &quot;Which WhatsApp number?&quot; (<code>connection_id</code> in message requests).
                </li>
            </ul>
            <p>Meta access tokens stay encrypted server-side. Customers never manage Meta credentials through the API.</p>

            <h2>Base URL</h2>
            <p>
                <code className="rounded bg-slate-800 px-2 py-1 font-mono text-sm">https://whatsappapi.iqpigeon.com/api/v1</code>
            </p>

            <h2>Authentication</h2>
            <pre className="overflow-x-auto rounded-xl bg-slate-900 p-4 font-mono text-sm">{`Authorization: Bearer iqp_live_YOUR_API_KEY`}</pre>
            <p>Keys are hashed at rest and shown once at creation. Never expose keys in browser JavaScript or Git.</p>

            <h2>Send flow (outbound)</h2>
            <p>CRM → POST /messages → API key auth → partner + subscription checks → scopes → rate limit → connection authorization → idempotency → Meta → WhatsApp recipient.</p>

            <h2>Sending messages</h2>
            <pre className="overflow-x-auto rounded-xl bg-slate-900 p-4 font-mono text-sm">{`POST /api/v1/messages
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
Idempotency-Key: unique-key-123

{
  "connection_id": "uuid-of-connection",
  "to": "+15551234567",
  "type": "text",
  "body": "Hello"
}`}</pre>
            <p>
                Required fields match server validation: <code>connection_id</code> (uuid), <code>to</code>, <code>type</code>{' '}
                (text|template|image|document|audio|video), plus <code>body</code> for text or <code>template</code> for templates.
            </p>

            <h2>Inbound webhooks (CRM)</h2>
            <p>End customer WhatsApp → Meta → IQPigeon <code>/webhooks/meta</code> → verify signature → queue → normalize → deliver to your CRM webhook URL.</p>
            <p>Configure endpoints in the dashboard or via API with <code>webhooks.write</code>. Verify signatures on your CRM side.</p>

            <h2>Idempotency</h2>
            <p>
                Mutating POSTs require <code>Idempotency-Key</code>. Retries with the same key and body replay the original response; mismatched
                body returns <code>409 idempotency_key_mismatch</code>.
            </p>

            <h2>Errors</h2>
            <pre className="overflow-x-auto rounded-xl bg-slate-900 p-4 font-mono text-sm">{`{
  "success": false,
  "error": {
    "code": "connection_not_found",
    "message": "The requested WhatsApp connection was not found."
  },
  "request_id": "..."
}`}</pre>

            <h2>Scopes (dot notation)</h2>
            <ul>
                <li><code>messages.read</code> / <code>messages.send</code></li>
                <li><code>connections.read</code> / <code>connections.write</code></li>
                <li><code>webhooks.read</code> / <code>webhooks.write</code></li>
                <li><code>usage.read</code></li>
            </ul>

            <h2>Billing separation</h2>
            <p>
                Stripe pays for the IQPigeon platform. Meta bills each connected business for WhatsApp messaging separately.
            </p>
        </MarketingPageShell>
    );
}
