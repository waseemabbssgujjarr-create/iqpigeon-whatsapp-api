import { DocBlock } from '../../../components/build/DocBlock';
import { webhookBodyShape, webhookSignaturePhp } from '../../../components/build/apiDocSnippets';
import { GuideIntro, RequestIdNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function MessageStatus() {
    const statusExample = `{
  "id": "evt_...",
  "type": "messages",
  "data": {
    "messaging_product": "whatsapp",
    "metadata": {
      "display_phone_number": "15551234567",
      "phone_number_id": "123456789012345"
    },
    "statuses": [
      {
        "id": "wamid.XXX",
        "status": "delivered",
        "timestamp": "1529389944",
        "recipient_id": "16315551234"
      }
    ]
  }
}`;

    const inboundExample = `{
  "id": "evt_...",
  "type": "messages",
  "data": {
    "messaging_product": "whatsapp",
    "metadata": { "phone_number_id": "..." },
    "messages": [
      {
        "from": "16315551234",
        "id": "wamid.XXX",
        "timestamp": "1529389944",
        "type": "text",
        "text": { "body": "Hello" }
      }
    ]
  }
}`;

    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Message status">
                <GuideIntro
                    what="Delivery and read updates for outbound messages, plus inbound customer replies."
                    when="Processing webhooks at your CRM URL (not Meta directly)."
                />

                <DocBlock title="How events reach your CRM">
                    <p>WhatsApp → Meta → IQPigeon → POST to your webhook URL.</p>
                    <p className="mt-2">
                        HTTP body shape from IQPigeon (<code className="text-slate-300">PartnerWebhookDeliveryExecutor</code>):
                    </p>
                </DocBlock>

                <DocBlock title="Webhook JSON envelope" code={webhookBodyShape}>
                    <p>
                        <code className="text-slate-300">type</code> is the Meta change field (commonly <code className="text-slate-300">messages</code>).
                        <code className="text-slate-300">data</code> is the Meta change value object.
                    </p>
                </DocBlock>

                <DocBlock title="Status updates (sent / delivered / read)" code={statusExample}>
                    <p>
                        Status values come from Meta inside <code className="text-slate-300">data.statuses[].status</code> (e.g. sent, delivered, read).
                        Map them to your CRM pipeline stages.
                    </p>
                </DocBlock>

                <DocBlock title="Inbound message example" code={inboundExample} />

                <DocBlock title="Verify webhook (server)" code={webhookSignaturePhp}>
                    <p>Use the webhook endpoint secret from IQPigeon (shown once when you create the endpoint).</p>
                </DocBlock>

                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
