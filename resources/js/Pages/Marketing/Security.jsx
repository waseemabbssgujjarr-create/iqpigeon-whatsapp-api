import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Security() {
    return (
        <MarketingPageShell title="Security" lead="Technical controls implemented in the platform — not a certification statement.">
            <ul className="list-disc space-y-2 pl-5">
                <li>API keys stored hashed; full secret shown once at creation</li>
                <li>Dot-notation scope enforcement on every API route</li>
                <li>Idempotency keys with request hash mismatch protection</li>
                <li>Meta credentials encrypted at rest</li>
                <li>Partner webhook HMAC signatures and SSRF URL validation</li>
                <li>Audit logging for sensitive dashboard actions</li>
            </ul>
        </MarketingPageShell>
    );
}
