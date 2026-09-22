import { Link, useForm } from '@inertiajs/react';
import AuthLayout from '../../marketing/auth/AuthLayout';

export default function VerifyEmail({ status }) {
    const form = useForm({});

    return (
        <AuthLayout
            title="Verify your email"
            subtitle="We sent a confirmation link to your inbox. Verify your email to access the dashboard, billing, and API keys."
        >
            <div className="rounded-xl border border-slate-700/60 bg-slate-950/50 px-4 py-4">
                <p className="text-sm leading-relaxed text-slate-300">
                    Check your spam folder if you do not see the message within a few minutes. Links expire for security.
                </p>
            </div>
            {status === 'verification-link-sent' && (
                <p className="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                    A new verification link has been sent to your email address.
                </p>
            )}
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/email/verification-notification');
                }}
                className="mt-6 space-y-3"
            >
                <button
                    type="submit"
                    disabled={form.processing}
                    className="iqp-btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {form.processing ? 'Sending…' : 'Resend verification email'}
                </button>
            </form>
            <div className="mt-6 flex flex-col gap-3 text-center text-sm">
                <Link href="/" className="text-slate-400 hover:text-violet-300">
                    Back to homepage
                </Link>
                <Link href="/logout" method="post" as="button" className="text-slate-500 hover:text-slate-300">
                    Log out
                </Link>
            </div>
        </AuthLayout>
    );
}
