<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class FacebookOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Facebook sign-in is not configured yet.',
            ]);
        }

        $driver = Socialite::driver('facebook')->scopes(['email', 'public_profile']);

        $configId = (string) config('services.facebook.config_id', '');
        if ($configId !== '') {
            $driver = $driver->with(['config_id' => $configId]);
        }

        return $driver->redirect();
    }

    public function callback(SocialAuthService $socialAuth): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Facebook sign-in is not configured yet.',
            ]);
        }

        try {
            $oauthUser = Socialite::driver('facebook')->user();
        } catch (InvalidStateException $e) {
            Log::info('auth.facebook.invalid_state');

            return redirect()->route('login')->withErrors([
                'email' => 'Facebook sign-in expired. Please try again.',
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::warning('auth.facebook.callback_failed', ['exception' => $e::class]);

            return redirect()->route('login')->withErrors([
                'email' => 'Facebook sign-in failed. Please try again or use email.',
            ]);
        }

        try {
            $user = $socialAuth->findOrCreateUser('facebook', $oauthUser);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, remember: true);

        Log::info('auth.facebook.success', ['user_id' => $user->id]);

        return redirect()->intended(route('app.dashboard'));
    }

    private function isConfigured(): bool
    {
        return (string) config('services.facebook.client_id') !== ''
            && (string) config('services.facebook.client_secret') !== '';
    }
}
