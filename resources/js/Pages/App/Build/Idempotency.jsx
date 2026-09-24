import { DocBlock } from '../../../components/build/DocBlock';
import { standardErrorShape } from '../../../components/build/apiDocSnippets';
import { GuideIntro, GuideMeta, RequestIdNote, SecurityNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function Idempotency({ apiBaseUrl }) {
    const mismatchError = `{
  "success": false,
  "error": {
    "code": "idempotency_key_mismatch",
    "message": "Idempotency-Key was already used with a different request payload."
  },
  "request_id": "req_..."
}`;

    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Idempotency">
                <GuideIntro
                    what="Safe retries for mutating API calls. The same Idempotency-Key + same body replays the original response."
                    when="Every POST /connections and POST /messages from your CRM backend."
                />

                <GuideMeta method="POST" endpoint="/api/v1/connections, /api/v1/messages" scope="(see each endpoint)" />

                <DocBlock title="Required header">
                    <p>
                        <code className="text-slate-300">Idempotency-Key: &lt;stable-unique-string&gt;</code>
                    </p>
                    <p className="mt-2">Missing header → HTTP 400, code idempotency_key_required.</p>
                </DocBlock>

                <DocBlock
                    title="Example — start onboarding"
                    code={`POST ${apiBaseUrl}/connections
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
Idempotency-Key: connect-customer_123-attempt-1

{ "external_ref": "customer_123", "return_url": "https://crm.example/callback" }`}
                />

                <DocBlock
                    title="Example — send message"
                    code={`POST ${apiBaseUrl}/messages
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
Idempotency-Key: msg-customer_123-0001

{
  "connection_id": "YOUR_CONNECTION_UUID",
  "to": "+15551234567",
  "type": "text",
  "body": "Hello"
}`}
                />

                <DocBlock title="Retry behavior">
                    <p>Same key + identical body → original JSON response is returned (including status code).</p>
                    <p className="mt-2">Same key + different body → HTTP 409 idempotency_key_mismatch.</p>
                </DocBlock>

                <DocBlock title="Error — mismatch (409)" code={mismatchError} />
                <DocBlock
                    title="Error — missing key (400)"
                    code={`{
  "success": false,
  "error": {
    "code": "idempotency_key_required",
    "message": "Idempotency-Key header is required for this request."
  },
  "request_id": "req_..."
}`}
                />

                <SecurityNote>Generate Idempotency-Key on your server. Use one key per logical operation (one connect attempt, one message send).</SecurityNote>
                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
