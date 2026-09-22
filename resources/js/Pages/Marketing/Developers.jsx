import { Link } from '@inertiajs/react';
import MarketingPageShell from '../../marketing/components/MarketingPageShell';

export default function Developers() {
    return (
        <MarketingPageShell
            title="Developers"
            lead="Integrate once against IQPigeon — your customers bring Meta."
        >
            <p>
                Base URL: <code className="rounded bg-slate-800 px-2 py-1 font-mono text-sm">https://whatsappapi.iqpigeon.com/api/v1</code>
            </p>
            <ul className="mt-6 list-disc space-y-2 pl-5">
                <li>Bearer API keys with granular scopes</li>
                <li>Required Idempotency-Key on mutating POSTs</li>
                <li>Plan-based rate limits with Retry-After</li>
                <li>Encrypted Meta tokens — never exposed via API</li>
            </ul>
            <p className="mt-8">
                <Link href="/docs" className="iqp-btn-primary inline-block rounded-lg px-5 py-2.5 text-sm font-semibold text-white no-underline">
                    Open documentation
                </Link>
            </p>
        </MarketingPageShell>
    );
}
