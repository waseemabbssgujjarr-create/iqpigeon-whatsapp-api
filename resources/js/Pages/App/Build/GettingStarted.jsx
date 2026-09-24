import { Link } from '@inertiajs/react';
import { DocBlock } from '../../../components/build/DocBlock';
import { GuideIntro } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function GettingStarted({ platformUrl }) {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Getting started">
                <GuideIntro
                    what="Connect WhatsApp to your CRM and start sending messages through IQPigeon."
                    when="You are integrating a CRM or SaaS product with the WhatsApp API platform."
                />

                <DocBlock title="One-minute mental model">
                    <ul className="list-disc space-y-2 pl-5">
                        <li>Your customer stays in your CRM.</li>
                        <li>Your CRM connects to IQPigeon with an API key.</li>
                        <li>IQPigeon handles Meta / WhatsApp (tokens stay on IQPigeon).</li>
                        <li>Your CRM sends messages through POST /api/v1/messages.</li>
                        <li>Your CRM receives events on your webhook URL.</li>
                    </ul>
                </DocBlock>

                <DocBlock title="Architecture">
                    <pre className="text-xs leading-relaxed text-slate-300">{`┌─────────────┐     API key      ┌──────────────┐     Meta API     ┌──────────┐
│  Your CRM   │ ───────────────► │   IQPigeon   │ ───────────────► │ WhatsApp │
│  (server)   │ ◄─────────────── │  (platform)  │ ◄─────────────── │  / Meta  │
└─────────────┘    webhooks      └──────────────┘                  └──────────┘
       │
       └── End user browser ──► Connect WhatsApp (hosted onboarding) ──► return to CRM`}</pre>
                </DocBlock>

                <DocBlock title="Setup checklist">
                    <ol className="list-decimal space-y-1 pl-5">
                        <li>Activate subscription</li>
                        <li>Connect WhatsApp (dashboard or CRM POST /connections)</li>
                        <li>Create API key</li>
                        <li>Add CRM webhook + test</li>
                        <li>Send first message</li>
                    </ol>
                    <Link href="/app" className="mt-4 inline-block text-violet-400 hover:underline">
                        Open Home workspace →
                    </Link>
                </DocBlock>

                <p className="text-xs text-slate-500">Platform: {platformUrl}</p>
            </BuildCrmLayout>
        </AppLayout>
    );
}
