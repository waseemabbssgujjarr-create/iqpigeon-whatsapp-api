import { Link, useForm } from '@inertiajs/react';
import AuthField from '../../marketing/auth/AuthField';
import AuthLayout from '../../marketing/auth/AuthLayout';

export default function ForgotPassword({ status }) {
    const form = useForm({ email: '' });

    return (
        <AuthLayout
            title="Reset your password"
            subtitle="Enter the email for your account and we will send a secure reset link."
            footer={
                <Link href="/login" className="font-medium text-violet-300 hover:text-violet-200">
                    Back to login
                </Link>
            }
        >
            {status && (
                <p className="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                    {status}
                </p>
            )}
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/forgot-password');
                }}
                className="space-y-5"
            >
                <AuthField label="Email" id="email" error={form.errors.email}>
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
                <button
                    type="submit"
                    disabled={form.processing}
                    className="iqp-btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {form.processing ? 'Sending…' : 'Email reset link'}
                </button>
            </form>
        </AuthLayout>
    );
}
