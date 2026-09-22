<?php

namespace App\Services\Stripe;

use App\Enums\ProvisioningStatus;
use App\Jobs\ProvisionPartnerJob;
use App\Models\Partner;
use App\Models\StripeEvent;
use App\Services\Billing\SubscriptionSyncService;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookProcessor
{
    public function __construct(
        private readonly SubscriptionSyncService $subscriptionSync,
    ) {}

    public function verifyAndParse(string $payload, ?string $signatureHeader): \Stripe\Event
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new \RuntimeException('Stripe webhook secret is not configured.');
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            throw new SignatureVerificationException('Missing Stripe signature header.');
        }

        return Webhook::constructEvent($payload, $signatureHeader, $secret);
    }

    public function process(string $payload, ?string $signatureHeader): void
    {
        $event = $this->verifyAndParse($payload, $signatureHeader);

        $record = StripeEvent::query()->firstOrCreate(
            ['stripe_event_id' => $event->id],
            [
                'type' => $event->type,
                'payload' => $event->toArray(),
            ],
        );

        if ($record->processed_at !== null) {
            return;
        }

        try {
            $this->handleEvent($event);
        } catch (\Throwable $exception) {
            Log::error('stripe.webhook.process_failed', [
                'event_id' => $event->id,
                'type' => $event->type,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $record->forceFill(['processed_at' => now()])->save();
    }

    private function handleEvent(\Stripe\Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'invoice.paid' => $this->handleInvoicePaid($event),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            default => null,
        };
    }

    private function handleCheckoutCompleted(\Stripe\Event $event): void
    {
        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;
        $partner = $this->resolvePartnerFromCheckoutSession($session);

        if ($partner === null) {
            return;
        }

        $partner->forceFill([
            'provisioning_status' => ProvisioningStatus::PaymentConfirmed,
        ])->save();

        ProvisionPartnerJob::dispatch($partner->id);
    }

    private function handleSubscriptionUpdated(\Stripe\Event $event): void
    {
        /** @var \Stripe\Subscription $subscription */
        $subscription = $event->data->object;
        $partner = $this->resolvePartnerFromSubscription($subscription);

        if ($partner === null) {
            return;
        }

        $this->subscriptionSync->syncFromStripeSubscription(
            $partner,
            $subscription,
            (int) data_get($subscription->metadata, 'plan_id'),
        );
    }

    private function handleSubscriptionDeleted(\Stripe\Event $event): void
    {
        /** @var \Stripe\Subscription $subscription */
        $subscription = $event->data->object;
        $partner = $this->resolvePartnerFromSubscription($subscription);

        if ($partner === null) {
            return;
        }

        $this->subscriptionSync->syncFromStripeSubscription($partner, $subscription);
    }

    private function handleInvoicePaid(\Stripe\Event $event): void
    {
        /** @var \Stripe\Invoice $invoice */
        $invoice = $event->data->object;
        $partner = $this->resolvePartnerFromInvoice($invoice);

        if ($partner === null) {
            return;
        }

        $subscription = $invoice->subscription ?? null;

        if ($subscription !== null && is_object($subscription)) {
            $this->subscriptionSync->syncFromStripeSubscription($partner, $subscription);
        }
    }

    private function handleInvoicePaymentFailed(\Stripe\Event $event): void
    {
        /** @var \Stripe\Invoice $invoice */
        $invoice = $event->data->object;
        $customerId = (string) ($invoice->customer ?? '');

        if ($customerId === '') {
            return;
        }

        $partner = $this->resolvePartnerFromInvoice($invoice);

        if ($partner === null) {
            return;
        }

        $subscription = $invoice->subscription ?? null;

        if ($subscription !== null && is_object($subscription)) {
            $this->subscriptionSync->syncFromStripeSubscription($partner, $subscription);
        } else {
            $this->subscriptionSync->applyPartnerBillingState(
                $partner,
                \App\Enums\SubscriptionStatus::PastDue,
            );
        }
    }

    private function resolvePartnerFromCheckoutSession(object $session): ?Partner
    {
        $partnerId = data_get($session->metadata, 'partner_id');

        if ($partnerId === null) {
            return null;
        }

        $partner = Partner::query()->find((int) $partnerId);

        if ($partner === null) {
            return null;
        }

        $reference = (string) ($session->client_reference_id ?? '');

        if ($reference !== '' && $reference !== (string) $partner->uuid) {
            Log::warning('stripe.checkout.client_reference_mismatch', [
                'partner_id' => $partner->id,
            ]);

            return null;
        }

        return $partner;
    }

    private function resolvePartnerFromInvoice(object $invoice): ?Partner
    {
        $customerId = (string) ($invoice->customer ?? '');

        if ($customerId === '') {
            return null;
        }

        return Partner::query()->where('stripe_id', $customerId)->first();
    }

    private function resolvePartnerFromSubscription(object $subscription): ?Partner
    {
        $partnerId = data_get($subscription->metadata, 'partner_id');

        if ($partnerId !== null) {
            return Partner::query()->find((int) $partnerId);
        }

        $customerId = (string) ($subscription->customer ?? '');

        if ($customerId === '') {
            return null;
        }

        return Partner::query()->where('stripe_id', $customerId)->first();
    }
}
