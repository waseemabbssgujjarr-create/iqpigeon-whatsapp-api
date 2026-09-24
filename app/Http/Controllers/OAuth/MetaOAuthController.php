<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Services\ConnectionOnboardingService;
use App\Services\CrmReturnUrlService;
use App\Services\Meta\MetaEmbeddedSignupService;
use App\Support\OnboardingReturnSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaOAuthController extends Controller
{
    public function __construct(
        private readonly ConnectionOnboardingService $onboarding,
        private readonly MetaEmbeddedSignupService $embeddedSignup,
        private readonly CrmReturnUrlService $returnUrls,
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

        try {
            $session = $this->embeddedSignup->completeCallback($code, $state);
        } catch (\Throwable $exception) {
            Log::warning('meta.oauth.callback_failed', ['message' => $exception->getMessage()]);

            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => 'WhatsApp connection could not be completed. Try again.']);
        }

        $connection = $session->whatsappConnection;
        $partner = $session->partner;
        $returnUrl = data_get($session->metadata, 'return_url');

        if (is_string($returnUrl) && $returnUrl !== '' && $partner !== null && $this->returnUrls->isAllowed($partner, $returnUrl)) {
            return redirect()->away(
                OnboardingReturnSignature::appendToUrl($returnUrl, $partner, $connection, 'connected'),
            );
        }

        return redirect()
            ->route('app.connections')
            ->with('status', 'WhatsApp connection completed.');
    }
}
