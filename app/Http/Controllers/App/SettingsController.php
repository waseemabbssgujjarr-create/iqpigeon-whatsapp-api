<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        return Inertia::render('App/Settings', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'partner' => $partner ? [
                'name' => $partner->name,
                'slug' => $partner->slug,
                'allowed_return_urls' => data_get($partner->metadata, 'allowed_return_urls', []),
                'has_integration_signing_secret' => is_string(data_get($partner->metadata, 'integration_signing_secret'))
                    && strlen((string) data_get($partner->metadata, 'integration_signing_secret')) >= 16,
            ] : null,
            'flashIntegrationSigningSecret' => $request->session()->pull('integration_signing_secret'),
        ]);
    }

    public function updateIntegration(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);
        abort_if($partner === null || $partner->owner_user_id !== $user->id, 403);

        $validated = $request->validate([
            'allowed_return_urls' => ['nullable', 'array', 'max:20'],
            'allowed_return_urls.*' => ['string', 'url', 'max:2048'],
        ]);

        $urls = array_values(array_unique(array_filter($validated['allowed_return_urls'] ?? [])));
        $metadata = is_array($partner->metadata) ? $partner->metadata : [];
        $metadata['allowed_return_urls'] = $urls;

        $partner->forceFill(['metadata' => $metadata])->save();

        return back()->with('status', 'CRM integration settings saved.');
    }

    public function generateIntegrationSigningSecret(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);
        abort_if($partner === null || $partner->owner_user_id !== $user->id, 403);

        $secret = Str::random(32);
        $metadata = is_array($partner->metadata) ? $partner->metadata : [];
        $metadata['integration_signing_secret'] = $secret;
        $partner->forceFill(['metadata' => $metadata])->save();

        return back()
            ->with('status', 'Integration signing secret generated — copy it now.')
            ->with('integration_signing_secret', $secret);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill(['name' => $validated['name']])->save();

        if ($partner !== null && $user->id === $partner->owner_user_id && ! empty($validated['company'])) {
            $partner->forceFill(['name' => $validated['company']])->save();
        }

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('status', 'password-updated');
    }
}
