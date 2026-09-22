<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWebhookRequest;
use App\Http\Requests\Api\V1\UpdateWebhookRequest;
use App\Http\Resources\WebhookEndpointResource;
use App\Models\Partner;
use App\Models\WebhookEndpoint;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index(Request $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $endpoints = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::ok([
            'webhooks' => WebhookEndpointResource::collection($endpoints),
        ]);
    }

    public function store(StoreWebhookRequest $request): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $secret = $request->validated('secret') ?? Str::random(32);

        $endpoint = WebhookEndpoint::query()->create([
            'partner_id' => $partner->id,
            'url' => $request->validated('url'),
            'events' => $request->validated('events'),
            'secret' => $secret,
            'is_active' => true,
        ]);

        return ApiResponse::ok([
            'webhook' => new WebhookEndpointResource($endpoint),
            'secret' => $secret,
        ], 201);
    }

    public function update(UpdateWebhookRequest $request, int $id): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $endpoint = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('id', $id)
            ->first();

        if ($endpoint === null) {
            return ApiResponse::error('webhook_not_found', 'Webhook endpoint not found.', 404);
        }

        $endpoint->fill($request->validated());
        $endpoint->save();

        return ApiResponse::ok([
            'webhook' => new WebhookEndpointResource($endpoint->refresh()),
        ]);
    }

    public function destroy(Request $request, int $id): ApiResponse
    {
        /** @var Partner $partner */
        $partner = $request->attributes->get('partner');

        $endpoint = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('id', $id)
            ->first();

        if ($endpoint === null) {
            return ApiResponse::error('webhook_not_found', 'Webhook endpoint not found.', 404);
        }

        $endpoint->delete();

        return ApiResponse::ok(['deleted' => true]);
    }
}
