import { Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const form = useForm({});

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h1 className="text-xl font-semibold text-[#0f172a]">Verify your email</h1>
                <p className="mt-2 text-sm text-slate-600">
                    Thanks for signing up. Click the link in your email, or resend the verification message.
                </p>
                {status === 'verification-link-sent' && (
                    <p className="mt-4 text-sm text-emerald-700">A new verification link has been sent.</p>
                )}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/email/verification-notification');
                    }}
                    className="mt-6"
                >
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-violet-600 py-2 text-sm font-medium text-white hover:bg-violet-700"
                    >
                        Resend verification email
                    </button>
                </form>
                <Link href="/logout" method="post" as="button" className="mt-4 block text-center text-sm text-slate-500">
                    Log out
                </Link>
            </div>
        </main>
    );
}
