import { useState } from 'react';
import SectionShell, { SectionHeader } from '../components/SectionShell';
import Reveal from '../components/Reveal';

const cases = {
    'Sales CRM': ['Lead created', 'WhatsApp message', 'Reply', 'CRM event'],
    'Support CRM': ['Ticket update', 'Agent message', 'Customer reply', 'Webhook'],
    'Healthcare CRM': ['Appointment', 'Reminder sent', 'Confirmed', 'Audit log'],
    'Real Estate': ['Listing alert', 'Tour confirm', 'Reply', 'Pipeline update'],
    Education: ['Class notice', 'Delivered', 'Parent reply', 'Attendance sync'],
    'Automation Platform': ['Workflow trigger', 'API send', 'Delivery', 'Next step'],
};

export default function UseCasesSection() {
    const [active, setActive] = useState('Sales CRM');

    return (
        <SectionShell tone="navy" bridge>
            <SectionHeader title="Built for CRM builders" align="center" />
            <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {Object.entries(cases).map(([name, flow]) => (
                    <button
                        key={name}
                        type="button"
                        onClick={() => setActive(name)}
                        onFocus={() => setActive(name)}
                        className={`iqp-glass rounded-2xl p-6 text-left transition ${
                            active === name ? 'ring-2 ring-violet-500/50' : 'hover:border-slate-600'
                        }`}
                    >
                        <h3 className="font-semibold text-white">{name}</h3>
                        <ol className="mt-4 space-y-2 font-mono text-[10px] text-slate-500">
                            {flow.map((step, i) => (
                                <li key={step} className={active === name ? 'text-violet-300/90' : ''}>
                                    {i + 1}. {step}
                                </li>
                            ))}
                        </ol>
                    </button>
                ))}
            </div>
            <Reveal className="mt-8 text-center text-sm text-slate-500">Selected: {active} workflow (illustrative)</Reveal>
        </SectionShell>
    );
}
