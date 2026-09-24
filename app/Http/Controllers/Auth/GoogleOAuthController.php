<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured yet.',
            ]);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(SocialAuthService $socialAuth): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured yet.',
            ]);
        }

        try {
            $oauthUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            Log::info('auth.google.invalid_state');

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in expired. Please try again.',
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::warning('auth.google.callback_failed', ['exception' => $e::class]);

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again or use email.',
            ]);
        }

        try {
            $user = $socialAuth->findOrCreateUser('google', $oauthUser);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, remember: true);

        Log::info('auth.google.success', ['user_id' => $user->id]);

        return redirect()->intended(route('app.dashboard'));
    }

    private function isConfigured(): bool
    {
        return (string) config('services.google.client_id') !== ''
            && (string) config('services.google.client_secret') !== '';
    }
}
