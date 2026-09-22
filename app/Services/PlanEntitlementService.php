<?php

namespace App\Services;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\Subscription;

class PlanEntitlementService
{
    public function resolvePlan(Partner $partner): ?Plan
    {
        $subscription = $this->resolveActiveSubscription($partner);

        return $subscription?->plan;
    }

    public function resolveActiveSubscription(Partner $partner): ?Subscription
    {
        return $partner->subscriptions()
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    public function partnerIsOperational(Partner $partner): bool
    {
        if ($partner->status !== PartnerStatus::Active) {
            return false;
        }

        if ($partner->provisioning_status !== ProvisioningStatus::Active) {
            return false;
        }

        $subscription = $this->resolveActiveSubscription($partner);

        return $subscription !== null && $subscription->isActive();
    }

    public function rateLimitPerMinute(Partner $partner): int
    {
        $plan = $this->resolvePlan($partner);

        return (int) ($plan?->feature('rate_limit_per_minute', 60) ?? 60);
    }

    public function maxConnections(Partner $partner): ?int
    {
        $plan = $this->resolvePlan($partner);
        $limit = $plan?->feature('max_connections');

        return $limit === null ? null : (int) $limit;
    }

    public function canAddConnection(Partner $partner): bool
    {
        if (! $this->partnerIsOperational($partner)) {
            return false;
        }

        $max = $this->maxConnections($partner);

        if ($max === null) {
            return true;
        }

        $current = $partner->whatsappConnections()
            ->whereNotIn('connection_status', ['revoked', 'disconnected'])
            ->count();

        return $current < $max;
    }

    /**
     * @return list<string>
     */
    public function defaultScopesForPartner(Partner $partner): array
    {
        $plan = $this->resolvePlan($partner);

        if ($plan === null) {
            return [];
        }

        return $plan->defaultScopes();
    }

    public function planIncludesScope(Partner $partner, string $scopeName): bool
    {
        return in_array($scopeName, $this->defaultScopesForPartner($partner), true);
    }

    public function includedMessagesMonthly(Partner $partner): ?int
    {
        $plan = $this->resolvePlan($partner);
        $value = $plan?->feature('included_messages_monthly');

        return $value === null ? null : (int) $value;
    }
}
