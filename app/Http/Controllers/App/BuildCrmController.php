<?php

namespace App\Http\Controllers\App;

use App\Enums\ConnectionStatus;
use App\Http\Controllers\Controller;
use App\Services\CrmReturnUrlService;
use App\Services\IntegrationHealthService;
use App\Support\PartnerResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildCrmController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function shared(Request $request): array
    {
        $partner = PartnerResolver::fromUser($request->user());

        $connection = $partner?->whatsappConnections()
            ->where('connection_status', ConnectionStatus::Active)
            ->orderByDesc('id')
            ->first();

        if ($connection === null) {
            $connection = $partner?->whatsappConnections()->orderByDesc('id')->first();
        }

        return [
            'apiBaseUrl' => url('/api/v1'),
            'platformUrl' => config('app.url'),
            'connectionUuid' => $connection?->uuid,
            'allowedReturnUrls' => $partner ? app(CrmReturnUrlService::class)->allowedOrigins($partner) : [],
            'integrationHealth' => app(IntegrationHealthService::class)->forPartner($partner),
        ];
    }

    public function gettingStarted(Request $request): Response
    {
        return Inertia::render('App/Build/GettingStarted', $this->shared($request));
    }

    public function authentication(Request $request): Response
    {
        return Inertia::render('App/Build/Authentication', $this->shared($request));
    }

    public function whatsAppConnection(Request $request): Response
    {
        return Inertia::render('App/Build/WhatsAppConnection', $this->shared($request));
    }

    public function sendMessages(Request $request): Response
    {
        return Inertia::render('App/Build/SendMessages', $this->shared($request));
    }

    public function receiveMessages(Request $request): Response
    {
        return Inertia::render('App/Build/ReceiveMessages', $this->shared($request));
    }

    public function webhooks(Request $request): Response
    {
        return Inertia::render('App/Build/WebhooksGuide', $this->shared($request));
    }

    public function messageStatus(Request $request): Response
    {
        return Inertia::render('App/Build/MessageStatus', $this->shared($request));
    }

    public function idempotency(Request $request): Response
    {
        return Inertia::render('App/Build/Idempotency', $this->shared($request));
    }

    public function errors(Request $request): Response
    {
        return Inertia::render('App/Build/Errors', $this->shared($request));
    }

    public function codeExamples(Request $request): Response
    {
        return Inertia::render('App/Build/CodeExamples', $this->shared($request));
    }

    public function apiReference(Request $request): Response
    {
        return Inertia::render('App/Build/ApiReference', $this->shared($request));
    }
}
