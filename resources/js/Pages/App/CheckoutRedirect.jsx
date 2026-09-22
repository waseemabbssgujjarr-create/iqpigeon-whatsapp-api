import { useEffect } from 'react';
import AppLayout from '../../Layouts/AppLayout';

/**
 * Full-page handoff to Stripe-hosted Checkout (session URL from server).
 * Ensures a real browser navigation even if external-location headers are dropped by proxies.
 */
export default function CheckoutRedirect({ redirectUrl }) {
    useEffect(() => {
        if (redirectUrl && typeof window !== 'undefined') {
            window.location.replace(redirectUrl);
        }
    }, [redirectUrl]);

    return (
        <AppLayout title="Checkout">
            <div className="mx-auto max-w-md rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p className="text-sm font-medium text-slate-800">Redirecting to secure checkout…</p>
                <p className="mt-2 text-xs text-slate-500">If you are not redirected, use the button below.</p>
                {redirectUrl && (
                    <a
                        href={redirectUrl}
                        className="mt-6 inline-block rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-700"
                    >
                        Continue to Stripe
                    </a>
                )}
            </div>
        </AppLayout>
    );
}
