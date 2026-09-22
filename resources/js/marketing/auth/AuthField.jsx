export default function AuthField({ label, id, error, children }) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-slate-300">
                {label}
            </label>
            <div className="mt-1.5">{children}</div>
            {error && (
                <p className="mt-1.5 text-sm text-red-400" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}
