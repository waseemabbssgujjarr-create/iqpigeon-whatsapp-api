export function GuideIntro({ what, when }) {
    return (
        <div className="mb-6 space-y-2 text-sm text-slate-400">
            {what && (
                <p>
                    <span className="font-medium text-slate-300">What this does: </span>
                    {what}
                </p>
            )}
            {when && (
                <p>
                    <span className="font-medium text-slate-300">When to use it: </span>
                    {when}
                </p>
            )}
        </div>
    );
}

export function GuideMeta({ method, endpoint, scope }) {
    return (
        <dl className="mb-6 grid gap-2 rounded-lg border border-slate-700 bg-slate-950/50 p-4 text-sm">
            {method && endpoint && (
                <>
                    <dt className="text-slate-500">Endpoint</dt>
                    <dd className="font-mono text-violet-300">
                        {method} {endpoint}
                    </dd>
                </>
            )}
            {scope && (
                <>
                    <dt className="text-slate-500">Required scope</dt>
                    <dd className="font-mono text-slate-300">{scope}</dd>
                </>
            )}
        </dl>
    );
}

export function SecurityNote({ children }) {
    return <p className="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">{children}</p>;
}

export function RequestIdNote() {
    return (
        <p className="mt-4 text-xs text-slate-500">
            Every API response includes <code className="text-slate-400">request_id</code>. Include it when contacting support or correlating
            logs. It is not a secret.
        </p>
    );
}
