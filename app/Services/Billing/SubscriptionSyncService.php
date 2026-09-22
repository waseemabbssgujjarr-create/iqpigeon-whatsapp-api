<?php

namespace App\Services\Billing;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\Subscription;
use App\Jobs\ProvisionPartnerJob;

class SubscriptionSyncService
{
    public function syncFromStripeSubscription(Partner $partner, object $stripeSubscription, ?int $planId = null): Subscription
    {
        $status = SubscriptionStatus::fromStripe((string) $stripeSubscription->status);

        if ($planId === null) {
            $planId = (int) data_get($stripeSubscription->metadata, 'plan_id');
        }

        if ($planId <= 0) {
            $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
            if (is_string($priceId) && $priceId !== '') {
                $configuredPrice = (string) config('services.stripe.price_id');
                if ($configuredPrice !== '' && hash_equals($configuredPrice, $priceId)) {
                    $planId = (int) (Plan::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? 0);
                }
                if ($planId <= 0) {
                    $planId = (int) (Plan::query()->where('stripe_price_id', $priceId)->value('id') ?? 0);
                }
            }
        }

        $subscription = Subscription::query()->updateOrCreate(
            ['stripe_id' => (string) $stripeSubscription->id],
            [
                'partner_id' => $partner->id,
                'plan_id' => $planId > 0 ? $planId : null,
                'type' => 'default',
                'stripe_status' => $status,
                'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? null,
                'quantity' => (int) ($stripeSubscription->items->data[0]->quantity ?? 1),
                'trial_ends_at' => isset($stripeSubscription->trial_end)
                    ? now()->createFromTimestamp((int) $stripeSubscription->trial_end)
                    : null,
                'ends_at' => isset($stripeSubscription->cancel_at)
                    ? now()->createFromTimestamp((int) $stripeSubscription->cancel_at)
                    : null,
            ],
        );

        $this->applyPartnerBillingState($partner, $status);

        return $subscription;
    }

    public function applyPartnerBillingState(Partner $partner, SubscriptionStatus $status): void
    {
        if ($status->isBillable()) {
            if ($partner->provisioning_status !== ProvisioningStatus::Active) {
                ProvisionPartnerJob::dispatch($partner->id);
            }

            if ($partner->status !== PartnerStatus::Active) {
                $partner->forceFill(['status' => PartnerStatus::Active])->save();
            }

            if ($partner->provisioning_status === ProvisioningStatus::Suspended
                || $partner->provisioning_status === ProvisioningStatus::Cancelled) {
                $partner->forceFill(['provisioning_status' => ProvisioningStatus::Active])->save();
            }

            return;
        }

        if (in_array($status, [SubscriptionStatus::PastDue, SubscriptionStatus::Unpaid], true)) {
            $partner->forceFill([
                'status' => PartnerStatus::Suspended,
                'provisioning_status' => ProvisioningStatus::Suspended,
            ])->save();

            return;
        }

        if (in_array($status, [SubscriptionStatus::Canceled, SubscriptionStatus::IncompleteExpired], true)) {
            $partner->forceFill([
                'status' => PartnerStatus::Suspended,
                'provisioning_status' => ProvisioningStatus::Cancelled,
            ])->save();
        }
    }
}
