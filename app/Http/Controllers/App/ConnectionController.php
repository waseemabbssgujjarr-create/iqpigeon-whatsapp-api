<?php

namespace App\Http\Controllers\App;

use App\Enums\ConnectionStatus;
use App\Http\Controllers\Controller;
use App\Models\WhatsappConnection;
use App\Services\ConnectionOnboardingService;
use App\Models\EmbeddedSignupSession;
use App\Services\Meta\MetaEmbeddedSignupService;
use App\Services\Meta\WhatsappCloudApiRegistrationService;
use App\Services\Meta\WhatsappConnectionHydrator;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConnectionController extends Controller
{
    public function index(Request $request, WhatsappConnectionHydrator $hydrator, WhatsappCloudApiRegistrationService $registration): Response
    {
        $partner = PartnerResolver::fromUser($request->user());

        $connections = $partner
            ? $partner->whatsappConnections()->with('credentials')->orderByDesc('id')->get()->map(function ($c) use ($hydrator, $registration) {
                $hydrator->reconcileOperationalStatus($c);
                $c->refresh();

                $metadata = is_array($c->metadata) ? $c->metadata : [];
                if (($metadata['onboarding_source'] ?? '') === 'coexistence' && $c->connection_status === ConnectionStatus::Pending) {
                    $registration->applyOperationalStatusAfterHydration($c);
                    $c->refresh();
                }

                $pendingSession = $c->embeddedSignupSessions()
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->latest('id')
                    ->first();

                $status = $c->connection_status->value;
                $hasCredentials = $c->credentials !== null;
                $missingPhone = $c->phone_number_id === null || $c->phone_number_id === '';
                $needsRegistration = $registration->requiresCloudApiPinRegistration($c);
                $isCoexistence = ($metadata['onboarding_source'] ?? '') === 'coexistence';

                $setupHint = match ($status) {
                    'pending' => $needsRegistration
                        ? 'Enter your WhatsApp Business two-step verification PIN to register this number for Cloud API sending.'
                        : ($missingPhone && $hasCredentials
                            ? 'Meta authorized this connection but your phone number ID was not saved. Sync from Meta or remove and reconnect.'
                            : ($pendingSession
                                ? 'Meta signup started — finish connecting your number or remove this draft.'
                                : 'Setup window expired. Remove this draft and connect again.')),
                    'error' => 'Connection failed during Meta signup. Try again or remove this entry.',
                    'disconnected', 'revoked' => 'This number is no longer connected.',
                    default => null,
                };

                return [
                    'uuid' => $c->uuid,
                    'connection_status' => $c->connection_status->value,
                    'display_phone_number' => $c->display_phone_number,
                    'waba_id' => $c->waba_id,
                    'phone_number_id' => $c->phone_number_id,
                    'connected_at' => $c->connected_at?->toIso8601String(),
                    'disconnected_at' => $c->disconnected_at?->toIso8601String(),
                    'updated_at' => $c->updated_at?->toIso8601String(),
                    'created_at' => $c->created_at?->toIso8601String(),
                    'can_resume_setup' => $status === 'pending' && $pendingSession !== null,
                    'can_sync_from_meta' => $status === 'pending' && $hasCredentials && $missingPhone,
                    'can_register_cloud_api' => $status === 'pending' && $needsRegistration,
                    'meta_linked_awaiting_registration' => $status === 'pending' && $needsRegistration,
                    'onboarding_source' => (string) ($metadata['onboarding_source'] ?? ''),
                    'is_coexistence' => $isCoexistence,
                    'setup_hint' => $setupHint,
                ];
            })
            : collect();

        return Inertia::render('App/Connections', [
            'connections' => $connections,
            'canConnect' => $partner !== null,
        ]);
    }

    public function start(Request $request, ConnectionOnboardingService $onboarding): RedirectResponse|HttpResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        try {
            $result = $onboarding->startOnboarding($partner, onboardingSource: 'standard');
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => $exception->getMessage()]);
        }

        return $this->redirectToOnboarding($request, $result['onboarding_url']);
    }

    public function startCoexistence(Request $request, ConnectionOnboardingService $onboarding): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        try {
            $result = $onboarding->startOnboarding($partner, onboardingSource: 'coexistence');
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => $exception->getMessage()]);
        }

        return redirect()
            ->route('app.connections')
            ->with('coexistence_onboarding', [
                'connection_uuid' => $result['connection']->uuid,
                'session_token' => $result['session_token'],
            ]);
    }

    public function completeEmbeddedSignup(
        Request $request,
        string $uuid,
        MetaEmbeddedSignupService $embeddedSignup,
    ): RedirectResponse {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'session_token' => ['required', 'string', 'max:128'],
            'embedded_signup_event' => ['nullable', 'array'],
        ]);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $session = EmbeddedSignupSession::query()
            ->where('whatsapp_connection_id', $connection->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->where('state_token_hash', hash('sha256', $validated['session_token']))
            ->first();

        if ($session === null) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => 'Embedded Signup session expired. Start again.']);
        }

        try {
            $embeddedSignup->completeForConnection(
                $session,
                $validated['code'],
                $validated['embedded_signup_event'] ?? [],
            );
        } catch (\Throwable) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => 'Meta could not complete WhatsApp connection. Try again or use standard Cloud API setup.']);
        }

        $connection->refresh();

        if ($connection->connection_status === ConnectionStatus::Error) {
            $reason = is_array($connection->metadata)
                ? (string) ($connection->metadata['validation_error'] ?? 'Meta validation failed.')
                : 'Meta validation failed.';

            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => $reason]);
        }

        if ($connection->connection_status === ConnectionStatus::Active) {
            return redirect()
                ->route('app.connections')
                ->with('status', 'WhatsApp connected and validated.');
        }

        return redirect()
            ->route('app.connections')
            ->with('status', 'Meta authorized your number. Complete the remaining setup step shown below.');
    }

    public function continueSetup(
        Request $request,
        string $uuid,
        ConnectionOnboardingService $onboarding,
    ): RedirectResponse|HttpResponse {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        try {
            $result = $onboarding->resumeOnboarding($partner, $connection);
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => $exception->getMessage()]);
        }

        $metadata = is_array($connection->metadata) ? $connection->metadata : [];
        if (($metadata['onboarding_source'] ?? '') === 'coexistence') {
            return redirect()
                ->route('app.connections')
                ->with('coexistence_onboarding', [
                    'connection_uuid' => $connection->uuid,
                    'session_token' => $result['session_token'],
                ]);
        }

        return $this->redirectToOnboarding($request, $result['onboarding_url']);
    }

    private function redirectToOnboarding(Request $request, string $onboardingUrl): RedirectResponse|HttpResponse
    {
        if ($request->inertia()) {
            return Inertia::location($onboardingUrl);
        }

        return redirect()->away($onboardingUrl);
    }

    public function registerCloudApi(
        Request $request,
        string $uuid,
        WhatsappCloudApiRegistrationService $registration,
    ): RedirectResponse {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->with('credentials')
            ->firstOrFail();

        $result = $registration->registerWithPin($connection, $validated['pin']);

        if ($result['ok']) {
            $connection->refresh();
            $metadata = is_array($connection->metadata) ? $connection->metadata : [];
            $message = ($metadata['onboarding_source'] ?? '') === 'coexistence'
                ? 'WhatsApp Business App number is connected and ready for messaging.'
                : 'WhatsApp number registered for Cloud API sending.';

            return redirect()->route('app.connections')->with('status', $message);
        }

        return redirect()
            ->route('app.connections')
            ->withErrors(['connect' => $result['error'] ?? 'Registration failed.']);
    }

    public function rehydrate(Request $request, string $uuid, WhatsappConnectionHydrator $hydrator): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->with('credentials')
            ->firstOrFail();

        if ($connection->credentials === null) {
            return redirect()
                ->route('app.connections')
                ->withErrors(['connect' => 'No Meta credentials stored for this connection. Start setup again.']);
        }

        if ($hydrator->rehydrateFromStoredCredentials($connection)) {
            $hydrator->applyOperationalStatusAfterHydration($connection->fresh());

            return redirect()->route('app.connections')->with('status', 'WhatsApp number synced from Meta.');
        }

        $hydrator->reconcileOperationalStatus($connection->fresh());

        return redirect()
            ->route('app.connections')
            ->withErrors(['connect' => 'Could not sync phone number from Meta. Try reconnecting or contact support.']);
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($connection->connection_status === ConnectionStatus::Active) {
            $connection->forceFill([
                'connection_status' => ConnectionStatus::Disconnected,
                'disconnected_at' => now(),
            ])->save();
        } else {
            $connection->embeddedSignupSessions()
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            $connection->forceFill([
                'connection_status' => ConnectionStatus::Disconnected,
                'disconnected_at' => now(),
            ])->save();
        }

        return redirect()->route('app.connections')->with('status', 'Connection removed.');
    }
}
