<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $verificationEnabled = (bool) config('auth.email_verification_enabled');

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (! $verificationEnabled) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $slugBase = Str::slug($validated['company']);
        $slug = $slugBase;
        $suffix = 1;

        while (Partner::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'name' => $validated['company'],
            'slug' => $slug,
            'status' => PartnerStatus::Pending,
            'provisioning_status' => ProvisioningStatus::Registered,
        ]);

        $user->forceFill(['partner_id' => $partner->id])->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('app.dashboard'));
    }
}
