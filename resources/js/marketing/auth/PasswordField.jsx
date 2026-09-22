import { useState } from 'react';
import AuthField from './AuthField';

export default function PasswordField({ label, id, value, onChange, error, autoComplete }) {
    const [visible, setVisible] = useState(false);

    return (
        <AuthField label={label} id={id} error={error}>
            <div className="relative">
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    autoComplete={autoComplete}
                    onChange={onChange}
                    className="iqp-auth-input w-full rounded-xl border border-slate-600/50 bg-slate-950/60 py-2.5 pl-4 pr-12 text-sm text-white placeholder:text-slate-600"
                    required
                />
                <button
                    type="button"
                    className="absolute inset-y-0 right-0 px-3 text-xs font-medium text-slate-400 hover:text-violet-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-cyan-400"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                >
                    {visible ? 'Hide' : 'Show'}
                </button>
            </div>
        </AuthField>
    );
}
