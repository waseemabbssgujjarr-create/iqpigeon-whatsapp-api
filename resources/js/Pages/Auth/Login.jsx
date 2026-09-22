import { Link, useForm, usePage } from '@inertiajs/react';

export default function Login({ status }) {
    const form = useForm({ email: '', password: '', remember: false });
    const { flash } = usePage().props;

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h1 className="text-xl font-semibold text-[#0f172a]">Sign in</h1>
                {(status || flash?.status) && (
                    <p className="mt-2 text-sm text-emerald-700">{status || flash?.status}</p>
                )}
                {form.errors.email && <p className="mt-2 text-sm text-red-600">{form.errors.email}</p>}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/login');
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
                    </div>
                    <div>
                        <label className="block text-sm text-slate-600">Password</label>
                        <input
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(e) => form.setData('remember', e.target.checked)}
                        />
                        Remember me
                    </label>
                    <button type="submit" className="w-full rounded-lg bg-violet-600 py-2 text-sm font-medium text-white">
                        Sign in
                    </button>
                </form>
                <div className="mt-4 flex justify-between text-sm">
                    <Link href="/forgot-password" className="text-violet-700">
                        Forgot password?
                    </Link>
                    <Link href="/signup" className="text-violet-700">
                        Create account
                    </Link>
                </div>
            </div>
        </main>
    );
}
