import { Link, useForm, usePage } from '@inertiajs/react';
import AuthField from '../../marketing/auth/AuthField';
import AuthLayout from '../../marketing/auth/AuthLayout';
import PasswordField from '../../marketing/auth/PasswordField';

export default function Login({ status }) {
    const form = useForm({ email: '', password: '', remember: false });
    const { flash } = usePage().props;
    const banner = status || flash?.status;

    return (
        <AuthLayout
            title="Welcome back"
            subtitle="Sign in to manage API keys, connections, billing, and webhooks."
            footer={
                <>
                    New to IQPigeon?{' '}
                    <Link href="/signup" className="font-medium text-violet-300 hover:text-violet-200">
                        Create account
                    </Link>
                </>
            }
        >
            {banner && (
                <p className="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                    {banner}
                </p>
            )}
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/login');
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
                <PasswordField
                    label="Password"
                    id="password"
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />
                <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <label className="flex items-center gap-2 text-slate-400">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(e) => form.setData('remember', e.target.checked)}
                            className="rounded border-slate-600 bg-slate-900 text-violet-600 focus:ring-violet-500"
                        />
                        Remember me
                    </label>
                    <Link href="/forgot-password" className="font-medium text-violet-300 hover:text-violet-200">
                        Forgot password?
                    </Link>
                </div>
                <button
                    type="submit"
                    disabled={form.processing}
                    className="iqp-btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {form.processing ? 'Signing in…' : 'Sign in'}
                </button>
            </form>
        </AuthLayout>
    );
}
