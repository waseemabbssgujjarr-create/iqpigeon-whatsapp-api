<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            ] : null,
        ]);
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
