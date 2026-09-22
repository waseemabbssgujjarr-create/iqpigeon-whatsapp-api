<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
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
            ? $partner->whatsappConnections()->orderByDesc('id')->get()->map(fn ($c) => [
                'uuid' => $c->uuid,
                'connection_status' => $c->connection_status->value,
                'display_phone_number' => $c->display_phone_number,
                'waba_id' => $c->waba_id,
                'phone_number_id' => $c->phone_number_id,
                'connected_at' => $c->connected_at?->toIso8601String(),
                'disconnected_at' => $c->disconnected_at?->toIso8601String(),
            ])
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
}
