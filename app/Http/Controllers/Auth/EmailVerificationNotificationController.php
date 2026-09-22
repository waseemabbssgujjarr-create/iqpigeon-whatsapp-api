<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! config('auth.email_verification_enabled')) {
            return redirect()->intended(route('app.dashboard'));
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('app.dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
