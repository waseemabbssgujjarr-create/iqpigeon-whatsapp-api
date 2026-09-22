<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConnectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreConnectionRequest;
use App\Http\Resources\WhatsappConnectionResource;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Services\ConnectionOnboardingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionOnboardingService $onboarding,
    ) {}

    public function index(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $connections = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::ok([
            'connections' => WhatsappConnectionResource::collection($connections),
        ]);
    }

    public function store(StoreConnectionRequest $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        try {
            $result = $this->onboarding->startOnboarding(
                $partner,
                $request->validated('external_ref'),
                $request->validated('metadata') ?? [],
            );
        } catch (\RuntimeException $exception) {
            return ApiResponse::error('connection_limit', $exception->getMessage(), 422);
        }

        $resource = (new WhatsappConnectionResource($result['connection']))
            ->additional([
                'onboarding_url' => $result['onboarding_url'],
                'onboarding_expires_at' => $result['expires_at']->toIso8601String(),
            ]);

        return ApiResponse::ok([
            'connection' => $resource,
            'onboarding_url' => $result['onboarding_url'],
            'expires_at' => $result['expires_at']->toIso8601String(),
        ], 201);
    }

    public function show(Request $request, string $id): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $id)
            ->first();

        if ($connection === null) {
            return ApiResponse::error('connection_not_found', 'Connection not found.', 404);
        }

        $additional = [];

        if ($connection->connection_status === ConnectionStatus::Pending) {
            $session = $connection->embeddedSignupSessions()
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if ($session !== null) {
                $additional['onboarding_expires_at'] = $session->expires_at->toIso8601String();
            }
        }

        return ApiResponse::ok([
            'connection' => (new WhatsappConnectionResource($connection))->additional($additional),
        ]);
    }

    public function destroy(Request $request, string $id): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $connection = WhatsappConnection::query()
            ->where('partner_id', $partner->id)
            ->where('uuid', $id)
            ->first();

        if ($connection === null) {
            return ApiResponse::error('connection_not_found', 'Connection not found.', 404);
        }

        $connection->forceFill([
            'connection_status' => ConnectionStatus::Disconnected,
            'disconnected_at' => now(),
        ])->save();

        return ApiResponse::ok(['deleted' => true]);
    }
}
