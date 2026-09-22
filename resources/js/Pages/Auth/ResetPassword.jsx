import { useForm } from '@inertiajs/react';
import AuthField from '../../marketing/auth/AuthField';
import AuthLayout from '../../marketing/auth/AuthLayout';
import PasswordField from '../../marketing/auth/PasswordField';

export default function ResetPassword({ token, email }) {
    const form = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    return (
        <AuthLayout title="Choose a new password" subtitle="Use a strong password you do not reuse on other services.">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/reset-password');
                }}
                className="space-y-5"
            >
                <input type="hidden" name="token" value={form.data.token} readOnly />
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
                <PasswordField
                    label="New password"
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
                <button
                    type="submit"
                    disabled={form.processing}
                    className="iqp-btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {form.processing ? 'Updating…' : 'Reset password'}
                </button>
            </form>
        </AuthLayout>
    );
}
