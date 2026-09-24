<?php

namespace App\Http\Controllers\App;

use App\Enums\ConnectionStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiRequest;
use App\Models\WebhookDelivery;
use App\Services\IntegrationHealthService;
use App\Services\IntegrationWorkflowService;
use App\Support\PartnerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        IntegrationWorkflowService $integration,
        IntegrationHealthService $health,
    ): Response {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        $workflow = $integration->forPartner($user, $partner);
        $integrationHealth = $health->forPartner($partner);
        $primaryConnection = $integration->primaryConnection($partner);

        $apiKeySecret = null;

        if ($partner !== null) {
            $cacheKey = 'partner_api_secret_once_'.$partner->id;
            $apiKeySecret = Cache::pull($cacheKey);
        }

        $activeConnection = $partner?->whatsappConnections()
            ->where('connection_status', ConnectionStatus::Active)
            ->orderByDesc('id')
            ->first();

        $connectionForExamples = $activeConnection ?? $partner?->whatsappConnections()->orderByDesc('id')->first();

        $webhook = $partner?->webhookEndpoints()->where('is_active', true)->orderByDesc('id')->first();
        $subscription = $partner?->activeSubscription()->with('plan')->first();

        $activeApiKey = $partner?->apiKeys()->whereNull('revoked_at')->orderByDesc('id')->first();

        $lastWebhookDelivery = $partner
            ? WebhookDelivery::query()
                ->where('partner_id', $partner->id)
                ->orderByDesc('id')
                ->first(['event_type', 'status', 'response_status', 'created_at'])
            : null;

        $lastApiRequest = $partner
            ? ApiRequest::query()
                ->where('partner_id', $partner->id)
                ->orderByDesc('id')
                ->first(['method', 'path', 'status_code', 'created_at'])
            : null;

        $usageSummary = null;

        if ($partner !== null) {
            $usageSummary = [
                'api_requests' => ApiRequest::query()->where('partner_id', $partner->id)->count(),
                'messages' => $partner->messages()->count(),
                'webhook_deliveries' => $partner->webhookEndpoints()->withCount('deliveries')->get()->sum('deliveries_count'),
            ];
        }

        $recentActivity = $partner
            ? ApiRequest::query()
                ->where('partner_id', $partner->id)
                ->orderByDesc('id')
                ->limit(8)
                ->get(['method', 'path', 'status_code', 'created_at'])
            : collect();

        return Inertia::render('App/Dashboard', [
            'workflow' => $workflow,
            'integrationHealth' => $integrationHealth,
            'apiBaseUrl' => url('/api/v1'),
            'connectionUuid' => $connectionForExamples?->uuid,
            'flashApiKeySecret' => $apiKeySecret ?? $request->session()->pull('api_key_secret'),
            'plan' => $subscription?->plan?->only(['name', 'slug']),
            'subscription' => $subscription ? [
                'stripe_status' => $subscription->stripe_status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ] : null,
            'whatsapp' => $primaryConnection,
            'crmIntegration' => [
                'api_key' => $activeApiKey ? [
                    'label' => $activeApiKey->label,
                    'prefix' => $activeApiKey->prefix,
                    'last_used_at' => $activeApiKey->last_used_at?->toIso8601String(),
                    'created_at' => $activeApiKey->created_at?->toIso8601String(),
                ] : null,
                'webhook' => $webhook ? [
                    'url' => $webhook->url,
                    'is_active' => $webhook->is_active,
                    'last_test' => $lastWebhookDelivery && $lastWebhookDelivery->event_type === 'webhook.test' ? [
                        'status' => $lastWebhookDelivery->status->value,
                        'http' => $lastWebhookDelivery->response_status,
                        'at' => $lastWebhookDelivery->created_at?->toIso8601String(),
                        'reachable' => $lastWebhookDelivery->status === WebhookDeliveryStatus::Delivered,
                    ] : null,
                ] : null,
                'last_api_request' => $lastApiRequest ? [
                    'method' => $lastApiRequest->method,
                    'path' => $lastApiRequest->path,
                    'status_code' => $lastApiRequest->status_code,
                    'at' => $lastApiRequest->created_at?->toIso8601String(),
                ] : null,
            ],
            'usageSummary' => $usageSummary,
            'recentActivity' => $recentActivity,
        ]);
    }
}
