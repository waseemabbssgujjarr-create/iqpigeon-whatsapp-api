import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthField from '../../marketing/auth/AuthField';
import AuthLayout from '../../marketing/auth/AuthLayout';
import PasswordField from '../../marketing/auth/PasswordField';

export default function Register() {
    const form = useForm({
        name: '',
        company: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [acceptedTerms, setAcceptedTerms] = useState(false);
    const [termsError, setTermsError] = useState('');

    const submit = (e) => {
        e.preventDefault();
        if (!acceptedTerms) {
            setTermsError('Please acknowledge the Terms and Privacy Policy.');
            return;
        }
        setTermsError('');
        form.post('/signup');
    };

    return (
        <AuthLayout
            title="Create your platform account"
            subtitle="Register your CRM organization to access the API, dashboard, webhooks, and Embedded Signup."
            footer={
                <>
                    Already have an account?{' '}
                    <Link href="/login" className="font-medium text-violet-300 hover:text-violet-200">
                        Log in
                    </Link>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-5" noValidate>
                <AuthField label="Your name" id="name" error={form.errors.name}>
                    <input
                        id="name"
                        type="text"
                        autoComplete="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        className="iqp-auth-input w-full rounded-xl border border-slate-600/50 bg-slate-950/60 px-4 py-2.5 text-sm text-white"
                        required
                    />
                </AuthField>
                <AuthField label="Company / partner name" id="company" error={form.errors.company}>
                    <input
                        id="company"
                        type="text"
                        autoComplete="organization"
                        value={form.data.company}
                        onChange={(e) => form.setData('company', e.target.value)}
                        className="iqp-auth-input w-full rounded-xl border border-slate-600/50 bg-slate-950/60 px-4 py-2.5 text-sm text-white"
                        required
                    />
                </AuthField>
                <AuthField label="Work email" id="email" error={form.errors.email}>
                    <input
                        id="email"
                        type="email"
                        autoComplete="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        className="iqp-auth-input w-full rounded-xl border border-slate-600/50 bg-slate-950/60 px-4 py-2.5 text-sm text-white"
                        required
                    />
                </AuthField>
                <PasswordField
                    label="Password"
                    id="password"
                    autoComplete="new-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />
                <PasswordField
                    label="Confirm password"
                    id="password_confirmation"
                    autoComplete="new-password"
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    error={form.errors.password_confirmation}
                />
                <label className="flex items-start gap-3 text-sm text-slate-400">
                    <input
                        type="checkbox"
                        checked={acceptedTerms}
                        onChange={(e) => {
                            setAcceptedTerms(e.target.checked);
                            if (e.target.checked) {
                                setTermsError('');
                            }
                        }}
                        className="mt-1 rounded border-slate-600 bg-slate-900 text-violet-600 focus:ring-violet-500"
                    />
                    <span>
                        I agree to the{' '}
                        <Link href="/terms" className="text-violet-300 hover:underline">
                            Terms
                        </Link>{' '}
                        and{' '}
                        <Link href="/privacy" className="text-violet-300 hover:underline">
                            Privacy Policy
                        </Link>
                        .
                    </span>
                </label>
                {termsError && (
                    <p className="text-sm text-red-400" role="alert">
                        {termsError}
                    </p>
                )}
                <button
                    type="submit"
                    disabled={form.processing}
                    className="iqp-btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {form.processing ? 'Creating account…' : 'Create account'}
                </button>
            </form>
        </AuthLayout>
    );
}
