import MarketingLayout from '../MarketingLayout';
import Reveal from './Reveal';

export default function MarketingPageShell({ title, lead, children }) {
    return (
        <MarketingLayout>
            <div className="mx-auto max-w-4xl px-4 pb-24 pt-32 sm:px-6 lg:px-8">
                <Reveal>
                    <h1 className="text-4xl font-semibold tracking-tight text-white">{title}</h1>
                    {lead && <p className="mt-4 text-lg text-slate-400">{lead}</p>}
                </Reveal>
                <Reveal delay={80} className="prose prose-invert mt-10 max-w-none prose-headings:text-white prose-p:text-slate-400 prose-a:text-violet-300">
                    {children}
                </Reveal>
            </div>
        </MarketingLayout>
    );
}
