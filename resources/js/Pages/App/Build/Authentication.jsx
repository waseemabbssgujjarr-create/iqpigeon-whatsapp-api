import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import { standardErrorShape } from '../../../components/build/apiDocSnippets';
import { GuideIntro, GuideMeta, RequestIdNote, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function Authentication({ apiBaseUrl }) {
    const meResponse = `{
  "success": true,
  "request_id": "req_...",
  "data": {
    "partner": {
      "id": "partner-uuid",
      "name": "Your company",
      "slug": "your-company",
      "status": "active",
      "provisioning_status": "active",
      "activated_at": "2026-09-24T10:00:00+00:00"
    }
  }
}`;

    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Authentication">
                <GuideIntro
                    what="Server-to-server authentication with an API key."
                    when="Every call from your CRM backend to /api/v1/*."
                />

                <GuideMeta method="GET" endpoint="/api/v1/me" scope="(any valid key)" />

                <DocBlock
                    title="Request"
                    code={`curl -s "${apiBaseUrl}/me" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Accept: application/json"`}
                />

                <DocBlock title="Response (200)" code={meResponse} />

                <DocBlock
                    title="Error (401)"
                    code={`{
  "success": false,
  "error": {
    "code": "unauthenticated",
    "message": "Invalid or expired API key."
  },
  "request_id": "req_..."
}`}
                />

                <DocBlock title="Create a key">
                    <Link href="/app/api-keys" className="text-violet-400 hover:underline">
                        Dashboard → API keys
                    </Link>
                    <p className="mt-2">Shown once at creation. Requires active subscription.</p>
                </DocBlock>

                <SecurityNote>Never expose your API key in browser JavaScript, mobile apps, or public repos.</SecurityNote>
                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
