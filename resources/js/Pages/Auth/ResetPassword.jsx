import { useForm } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const form = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
            <div className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
                <h1 className="text-xl font-semibold text-[#0f172a]">Reset password</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/reset-password');
                    }}
                    className="mt-6 space-y-4"
                >
                    <input type="hidden" value={form.data.token} readOnly />
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
                        <label className="block text-sm text-slate-600">New password</label>
                        <input
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm text-slate-600">Confirm password</label>
                        <input
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            required
                        />
                        {(form.errors.email || form.errors.password) && (
                            <p className="text-sm text-red-600">{form.errors.email || form.errors.password}</p>
                        )}
                    </div>
                    <button type="submit" className="w-full rounded-lg bg-violet-600 py-2 text-sm font-medium text-white">
                        Reset password
                    </button>
                </form>
            </div>
        </main>
    );
}
