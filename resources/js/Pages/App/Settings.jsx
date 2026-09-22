import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Settings({ user, partner }) {
        const profile = useForm({ name: user?.name ?? '', company: partner?.name ?? '' });
    const password = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const { flash } = usePage().props;

    return (
        <AppLayout title="Settings">
            <div className="grid gap-8 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="font-semibold text-[#0f172a]">Profile</h2>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            profile.patch('/app/settings/profile');
                        }}
                        className="mt-4 space-y-3"
                    >
                        <input
                            value={profile.data.name}
                            onChange={(e) => profile.setData('name', e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                        <button type="submit" className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                            Save profile
                        </button>
                    </form>
                    <p className="mt-4 text-sm text-slate-600">
                        Email: {user?.email}{' '}
                        {user?.email_verified_at ? (
                            <span className="text-emerald-600">(verified)</span>
                        ) : (
                            <span className="text-amber-600">(not verified)</span>
                        )}
                    </p>
                    {partner && (
                        <p className="mt-2 text-sm text-slate-600">
                            Company: {partner.name} ({partner.slug})
                        </p>
                    )}
                </section>

                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="font-semibold text-[#0f172a]">Password</h2>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            password.put('/app/settings/password');
                        }}
                        className="mt-4 space-y-3"
                    >
                        <input
                            type="password"
                            placeholder="Current password"
                            value={password.data.current_password}
                            onChange={(e) => password.setData('current_password', e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                        <input
                            type="password"
                            placeholder="New password"
                            value={password.data.password}
                            onChange={(e) => password.setData('password', e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                        <input
                            type="password"
                            placeholder="Confirm new password"
                            value={password.data.password_confirmation}
                            onChange={(e) => password.setData('password_confirmation', e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        />
                        <button type="submit" className="rounded-lg bg-[#0f172a] px-4 py-2 text-sm text-white">
                            Update password
                        </button>
                    </form>
                    {flash?.status && <p className="mt-3 text-sm text-emerald-700">{flash.status}</p>}
                </section>
            </div>
        </AppLayout>
    );
}
