<?php

namespace App\Http\Controllers\App;

use App\Enums\ConnectionStatus;
use App\Http\Controllers\Controller;
use App\Models\WhatsappConnection;
use App\Services\ConnectionOnboardingService;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        $partner = PartnerResolver::fromUser($request->user());

        $connections = $partner
            ? $partner->whatsappConnections()->orderByDesc('id')->get()->map(function ($c) {
                $pendingSession = $c->embeddedSignupSessions()
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->latest('id')
                    ->first();

                $status = $c->connection_status->value;
                $setupHint = match ($status) {
                    'pending' => $pendingSession
                        ? 'Meta signup started — finish connecting your number or remove this draft.'
                        : 'Setup window expired. Remove this draft and connect again.',
                    'error' => 'Connection failed during Meta signup. Try again or remove this entry.',
                    'disconnected', 'revoked' => 'This number is no longer connected.',
                    default => null,
                };

                return [
                    'uuid' => $c->uuid,
                    'connection_status' => $status,
                    'display_phone_number' => $c->display_phone_number,
                    'waba_id' => $c->waba_id,
                    'phone_number_id' => $c->phone_number_id,
                    'connected_at' => $c->connected_at?->toIso8601String(),
                    'disconnected_at' => $c->disconnected_at?->toIso8601String(),
                    'updated_at' => $c->updated_at?->toIso8601String(),
                    'created_at' => $c->created_at?->toIso8601String(),
                    'can_resume_setup' => $status === 'pending' && $pendingSession !== null,
                    'setup_hint' => $setupHint,
                ];
            })
            : collect();

        return Inertia::render('App/Connections', [
            'connections' => $connections,
            'canConnect' => $partner !== null,
        ]);
    }

    public function start(Request $request, ConnectionOnboardingService $onboarding): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        try {
            $result = $onboarding->startOnboarding($partner);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['connect' => $exception->getMessage()]);
        }

        return redirect()->away($result['onboarding_url']);
    }

    public function continueSetup(
        Request $request,
        string $uuid,
        ConnectionOnboardingService $onboarding,
    ): RedirectResponse {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        try {
            $result = $onboarding->resumeOnboarding($partner, $connection);
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            return back()->withErrors(['connect' => $exception->getMessage()]);
        }

        return redirect()->away($result['onboarding_url']);
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
