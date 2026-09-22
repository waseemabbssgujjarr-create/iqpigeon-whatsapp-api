<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ApiRequest;
use App\Services\OnboardingChecklistService;
use App\Support\PartnerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OnboardingChecklistService $onboarding): Response
    {
        $user = $request->user();
        $partner = PartnerResolver::fromUser($user);

        $checklist = $onboarding->stepsFor($user, $partner);
        $showChecklist = ! $onboarding->isComplete($user, $partner);

        $apiKeySecret = null;

        if ($partner !== null) {
            $cacheKey = 'partner_api_secret_once_'.$partner->id;
            $apiKeySecret = Cache::pull($cacheKey);
        }

        $connection = $partner?->whatsappConnections()->latest('id')->first();
        $webhook = $partner?->webhookEndpoints()->where('is_active', true)->first();
        $subscription = $partner?->activeSubscription()->with('plan')->first();

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
            'showChecklist' => $showChecklist,
            'checklist' => $checklist,
            'flashApiKeySecret' => $apiKeySecret ?? $request->session()->pull('api_key_secret'),
            'account' => [
                'email_verified' => $user->hasVerifiedEmail(),
                'partner_status' => $partner?->status->value,
                'provisioning_status' => $partner?->provisioning_status->value,
            ],
            'plan' => $subscription?->plan?->only(['name', 'slug']),
            'subscription' => $subscription ? [
                'stripe_status' => $subscription->stripe_status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ] : null,
            'apiKeys' => [
                'active_count' => $partner?->apiKeys()->whereNull('revoked_at')->count() ?? 0,
            ],
            'connection' => $connection ? [
                'status' => $connection->connection_status->value,
                'display_phone' => $connection->display_phone_number,
                'waba_id' => $connection->waba_id,
            ] : null,
            'webhook' => $webhook ? [
                'url' => $webhook->url,
                'is_active' => $webhook->is_active,
            ] : null,
            'usageSummary' => $usageSummary,
            'recentActivity' => $recentActivity,
        ]);
    }
}
