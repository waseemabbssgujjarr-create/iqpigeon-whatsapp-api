import { router, useForm, usePage } from '@inertiajs/react';
import { copyText } from '../../components/ui/copyText';
import AppLayout from '../../Layouts/AppLayout';

export default function Settings({ user, partner, flashIntegrationSigningSecret }) {
    const profile = useForm({ name: user?.name ?? '', company: partner?.name ?? '' });
    const integration = useForm({
        allowed_return_urls: (partner?.allowed_return_urls ?? []).join('\n'),
    });
    const password = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const { flash } = usePage().props;

    return (
        <AppLayout title="Settings">
            <div className="grid gap-8 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-700 bg-slate-900/50 p-6 lg:col-span-2">
                    <h2 className="font-semibold text-white">CRM integration</h2>
                    <p className="mt-1 text-sm text-slate-400">
                        Allowlisted URLs for returning users after WhatsApp onboarding from your CRM (HTTPS in production).
                    </p>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            const urls = integration.data.allowed_return_urls
                                .split('\n')
                                .map((s) => s.trim())
                                .filter(Boolean);
                            integration.transform(() => ({ allowed_return_urls: urls })).patch('/app/settings/integration');
                        }}
                        className="mt-4 space-y-3"
                    >
                        <textarea
                            rows={4}
                            placeholder="https://your-crm.com/integrations/iqpigeon/callback"
                            value={integration.data.allowed_return_urls}
                            onChange={(e) => integration.setData('allowed_return_urls', e.target.value)}
                            className="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 font-mono text-sm text-white"
                        />
                        <button type="submit" className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                            Save CRM URLs
                        </button>
                    </form>

                    <div className="mt-8 border-t border-slate-700 pt-6">
                        <h3 className="font-medium text-white">Integration signing secret</h3>
                        <p className="mt-1 text-sm text-slate-400">
                            Verifies onboarding return URLs after Connect WhatsApp (CRM callback). Separate from webhook endpoint secrets.
                        </p>
                        <p className="mt-2 text-sm text-slate-300">
                            Status:{' '}
                            {partner?.has_integration_signing_secret ? (
                                <span className="text-emerald-400">Configured</span>
                            ) : (
                                <span className="text-amber-400">Not set — generate before relying on return URL signatures</span>
                            )}
                        </p>
                        {flashIntegrationSigningSecret && (
                            <div className="mt-4 rounded-lg border-2 border-violet-500/50 bg-violet-500/10 p-4">
                                <p className="text-sm font-semibold text-violet-100">Copy now — shown once</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    <code className="flex-1 break-all rounded bg-slate-950 px-3 py-2 text-sm text-white">
                                        {flashIntegrationSigningSecret}
                                    </code>
                                    <button
                                        type="button"
                                        onClick={() => copyText(flashIntegrationSigningSecret)}
                                        className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white"
                                    >
                                        Copy
                                    </button>
                                </div>
                                <p className="mt-2 text-xs text-violet-200/80">Store on your CRM server only. Never expose in browser code.</p>
                            </div>
                        )}
                        <button
                            type="button"
                            className="mt-4 rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800"
                            onClick={() => {
                                if (
                                    window.confirm(
                                        'Generate a new integration signing secret? The previous secret stops working for new callbacks.',
                                    )
                                ) {
                                    router.post('/app/settings/integration-signing-secret');
                                }
                            }}
                        >
                            {partner?.has_integration_signing_secret ? 'Rotate integration signing secret' : 'Generate integration signing secret'}
                        </button>
                    </div>
                </section>

                <section className="rounded-xl border border-slate-700 bg-slate-900/50 p-6">
                    <h2 className="font-semibold text-white">Profile</h2>
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
                            className="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                        <button type="submit" className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                            Save profile
                        </button>
                    </form>
                    <p className="mt-4 text-sm text-slate-400">
                        Email: {user?.email}{' '}
                        {user?.email_verified_at ? (
                            <span className="text-emerald-600">(verified)</span>
                        ) : (
                            <span className="text-amber-600">(not verified)</span>
                        )}
                    </p>
                    {partner && (
                        <p className="mt-2 text-sm text-slate-400">
                            Company: {partner.name} ({partner.slug})
                        </p>
                    )}
                </section>

                <section className="rounded-xl border border-slate-700 bg-slate-900/50 p-6">
                    <h2 className="font-semibold text-white">Password</h2>
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
                            className="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                        <input
                            type="password"
                            placeholder="New password"
                            value={password.data.password}
                            onChange={(e) => password.setData('password', e.target.value)}
                            className="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                        <input
                            type="password"
                            placeholder="Confirm new password"
                            value={password.data.password_confirmation}
                            onChange={(e) => password.setData('password_confirmation', e.target.value)}
                            className="w-full rounded-lg border border-slate-600 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                        <button type="submit" className="rounded-lg bg-violet-600 px-4 py-2 text-sm text-white">
                            Update password
                        </button>
                    </form>
                    {flash?.status && <p className="mt-3 text-sm text-emerald-400">{flash.status}</p>}
                </section>
            </div>
        </AppLayout>
    );
}
