<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverPartnerWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Rules\SafeWebhookUrl;
use App\Services\AuditLogService;
use App\Support\PartnerResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): Response
    {
        $partner = PartnerResolver::fromUser($request->user());

        $endpoints = $partner
            ? WebhookEndpoint::query()
                ->where('partner_id', $partner->id)
                ->withCount('deliveries')
                ->orderByDesc('id')
                ->get()
                ->map(fn (WebhookEndpoint $e) => [
                    'id' => $e->id,
                    'url' => $e->url,
                    'events' => $e->events,
                    'is_active' => $e->is_active,
                    'deliveries_count' => $e->deliveries_count,
                    'created_at' => $e->created_at?->toIso8601String(),
                ])
            : collect();

        $deliveries = $partner
            ? WebhookDelivery::query()
                ->where('partner_id', $partner->id)
                ->orderByDesc('id')
                ->limit(25)
                ->get(['id', 'uuid', 'event_type', 'status', 'attempt_count', 'response_status', 'created_at', 'webhook_endpoint_id'])
            : collect();

        return Inertia::render('App/Webhooks', [
            'endpoints' => $endpoints,
            'recentDeliveries' => $deliveries,
            'flashSecret' => $request->session()->pull('webhook_endpoint_secret'),
        ]);
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null || $partner->owner_user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048', new SafeWebhookUrl],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'max:128'],
        ]);

        $secret = Str::random(32);

        $endpoint = WebhookEndpoint::query()->create([
            'partner_id' => $partner->id,
            'url' => $validated['url'],
            'events' => $validated['events'] ?? ['*'],
            'secret' => $secret,
            'is_active' => true,
        ]);

        $audit->log('webhook_endpoint.created', $partner, $request->user(), $endpoint);

        return redirect()
            ->route('app.webhooks')
            ->with('webhook_endpoint_secret', $secret);
    }

    public function update(Request $request, int $id, AuditLogService $audit): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null, 403);

        $endpoint = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:2048', new SafeWebhookUrl],
            'events' => ['sometimes', 'nullable', 'array'],
            'events.*' => ['string', 'max:128'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $endpoint->fill($validated);
        $endpoint->save();

        $audit->log('webhook_endpoint.updated', $partner, $request->user(), $endpoint);

        return redirect()->route('app.webhooks');
    }

    public function destroy(Request $request, int $id, AuditLogService $audit): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null, 403);

        $endpoint = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('id', $id)
            ->firstOrFail();

        $audit->log('webhook_endpoint.deleted', $partner, $request->user(), $endpoint);
        $endpoint->delete();

        return redirect()->route('app.webhooks');
    }

    public function test(Request $request, int $id): RedirectResponse
    {
        $partner = PartnerResolver::fromUser($request->user());
        abort_if($partner === null, 403);

        $endpoint = WebhookEndpoint::query()
            ->where('partner_id', $partner->id)
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        DeliverPartnerWebhookJob::dispatch(
            $partner->id,
            'webhook.test',
            'test_'.Str::uuid(),
            ['endpoint_id' => $endpoint->id, 'message' => 'Test delivery from IQPigeon dashboard'],
        );

        return back()->with('status', 'Test webhook queued for delivery.');
    }
}
