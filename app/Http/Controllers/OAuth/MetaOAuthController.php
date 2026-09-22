<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Services\ConnectionOnboardingService;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MetaOAuthController extends Controller
{
    public function __construct(
        private readonly ConnectionOnboardingService $onboarding,
        private readonly MetaEmbeddedSignupService $embeddedSignup,
    ) {}

    public function start(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            abort(400, 'Missing onboarding token.');
        }

        $session = $this->onboarding->findSessionByToken($token);

        if ($session === null) {
            abort(404, 'Onboarding session not found or expired.');
        }

        return redirect()->away($this->embeddedSignup->authorizationUrl($session, $token));
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($code === '' || $state === '') {
            abort(400, 'Missing OAuth parameters.');
        }

        $this->embeddedSignup->completeCallback($code, $state);

        return redirect()->route('app.connections')->with('status', 'WhatsApp connection completed.');
    }
}
