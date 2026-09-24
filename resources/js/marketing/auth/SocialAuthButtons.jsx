import { Link } from '@inertiajs/react';

export default function SocialAuthButtons({ socialAuth, mode = 'login' }) {
    const showGoogle = socialAuth?.google ?? false;
    const showFacebook = socialAuth?.facebook ?? false;

    if (!showGoogle && !showFacebook) {
        return null;
    }

    const registerHint = mode === 'register';

    return (
        <div className="space-y-3">
            {showGoogle && (
                <Link
                    href="/auth/google/redirect"
                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-600/50 bg-slate-950/40 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-900/60"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            fill="#4285F4"
                            d="M22 12c0-.68-.06-1.35-.17-2H12v3.77h5.92a5.02 5.02 0 0 1-2.18 3.3v2.73h3.53A10.5 10.5 0 0 0 22 12z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.53-2.73c-.98.66-2.23 1.05-3.75 1.05-2.88 0-5.32-1.95-6.19-4.57H1.1v2.82A11 11 0 0 0 12 23z"
                        />
                        <path fill="#FBBC05" d="M5.81 14.09A6.6 6.6 0 0 1 5.44 12c0-.73.13-1.43.37-2.09V7.09H1.1A11 11 0 0 0 1 12c0 1.78.43 3.45 1.1 4.91l4.71-2.82z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.1 7.09l4.71 2.82C7.68 7.33 9.62 5.38 12 5.38z" />
                    </svg>
                    Continue with Google
                </Link>
            )}
            {showFacebook && (
                <Link
                    href="/auth/facebook/redirect"
                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-600/50 bg-slate-950/40 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-900/60"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true">
                        <path d="M24 12.07C24 5.73 18.63.36 12.29.36 5.95.36.58 5.73.58 12.07c0 5.86 4.27 10.72 9.85 11.58v-8.2H7.9v-3.38h2.53V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.2 2.23.2v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 3.38h-2.34v8.2C19.73 22.79 24 17.93 24 12.07z" />
                    </svg>
                    Continue with Facebook
                </Link>
            )}
            {registerHint && (
                <p className="text-center text-xs text-slate-500">
                    Social sign-up creates your platform account and partner organization automatically.
                </p>
            )}
            <div className="relative py-1">
                <div className="absolute inset-0 flex items-center" aria-hidden="true">
                    <div className="w-full border-t border-slate-700/60" />
                </div>
                <p className="relative mx-auto w-fit bg-transparent px-2 text-xs uppercase tracking-wider text-slate-500">or email</p>
            </div>
        </div>
    );
}
