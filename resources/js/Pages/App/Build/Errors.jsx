import { DocBlock } from '../../../components/build/DocBlock';
import { API_ERROR_CATALOG, standardErrorShape } from '../../../components/build/apiDocSnippets';
import { GuideIntro, RequestIdNote } from '../../../components/build/BuildGuideSections';
import BuildCrmLayout from '../../../Layouts/BuildCrmLayout';
import AppLayout from '../../../Layouts/AppLayout';

export default function Errors() {
    return (
        <AppLayout hideTitle>
            <BuildCrmLayout title="Errors">
                <GuideIntro
                    what="Structured JSON errors for every failed API call."
                    when="Handling CRM retries, user messaging, and support escalations."
                />

                <DocBlock title="Standard error shape" code={standardErrorShape} />

                <div className="overflow-x-auto rounded-xl border border-slate-700">
                    <table className="min-w-full text-sm">
                        <thead className="bg-slate-800 text-left text-slate-400">
                            <tr>
                                <th className="px-4 py-2">code</th>
                                <th className="px-4 py-2">HTTP</th>
                                <th className="px-4 py-2">Meaning</th>
                                <th className="px-4 py-2">CRM action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {API_ERROR_CATALOG.map((row) => (
                                <tr key={row.code} className="border-t border-slate-800 text-slate-300">
                                    <td className="px-4 py-2 font-mono text-xs text-violet-300">{row.code}</td>
                                    <td className="px-4 py-2">{row.http}</td>
                                    <td className="px-4 py-2">{row.meaning}</td>
                                    <td className="px-4 py-2 text-slate-400">{row.action}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <RequestIdNote />
            </BuildCrmLayout>
        </AppLayout>
    );
}
