import { useForm } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const form = useForm({ email: '' });

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h1 className="text-xl font-semibold text-[#0f172a]">Forgot password</h1>
                {status && <p className="mt-2 text-sm text-emerald-700">{status}</p>}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/forgot-password');
                    }}
                    className="mt-6 space-y-4"
                >
                    <div>
                        <label className="block text-sm text-slate-600">Email</label>
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
                        {form.errors.email && <p className="mt-1 text-sm text-red-600">{form.errors.email}</p>}
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="w-full rounded-lg bg-violet-600 py-2 text-sm font-medium text-white"
                    >
                        Email reset link
                    </button>
                </form>
                <Link href="/login" className="mt-4 block text-center text-sm text-violet-700">
                    Back to login
                </Link>
            </div>
        </main>
    );
}
