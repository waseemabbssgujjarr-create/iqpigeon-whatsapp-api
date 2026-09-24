import { useState } from 'react';
import { copyText } from '../ui/copyText';

export function DocBlock({ title, children, code, copyable = true }) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        if (!code) return;
        await copyText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <section className="mb-8 rounded-xl border border-slate-700 bg-slate-900/40 p-5">
            {title && <h2 className="text-lg font-semibold text-white">{title}</h2>}
            {children && <div className="mt-3 space-y-2 text-sm leading-relaxed text-slate-400">{children}</div>}
            {code && (
                <div className="mt-4">
                    {copyable && (
                        <button type="button" onClick={copy} className="mb-2 text-xs font-medium text-violet-400 hover:underline">
                            {copied ? 'Copied' : 'Copy'}
                        </button>
                    )}
                    <pre className="overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-200 ring-1 ring-slate-800">{code}</pre>
                </div>
            )}
        </section>
    );
}
