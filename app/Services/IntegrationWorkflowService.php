<?php

namespace App\Services;

use App\Enums\ConnectionStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Models\ApiRequest;
use App\Models\Partner;
use App\Models\User;
use App\Models\WebhookDelivery;
class IntegrationWorkflowService
{
    /**
     * @return array{
     *     subscription_ready: bool,
     *     headline: string,
     *     integration_complete: bool,
     *     steps: array<int, array<string, mixed>>
     * }
     */
    public function forPartner(User $user, ?Partner $partner): array
    {
        $subscriptionReady = $partner !== null && in_array($partner->provisioning_status, [
            ProvisioningStatus::PaymentConfirmed,
            ProvisioningStatus::Provisioning,
            ProvisioningStatus::Active,
        ], true);

        $activeConnection = $partner
            ? $partner->whatsappConnections()->where('connection_status', ConnectionStatus::Active)->orderByDesc('id')->first()
            : null;

        $pendingConnections = $partner
            ? $partner->whatsappConnections()->where('connection_status', ConnectionStatus::Pending)->count()
            : 0;

        $hasApiKey = $partner !== null && $partner->apiKeys()->whereNull('revoked_at')->exists();

        $webhookEndpoint = $partner
            ? $partner->webhookEndpoints()->where('is_active', true)->orderByDesc('id')->first()
            : null;

        $webhookTested = false;
        $lastWebhookTest = null;

        if ($partner !== null) {
            $lastWebhookTest = WebhookDelivery::query()
                ->where('partner_id', $partner->id)
                ->where('event_type', 'webhook.test')
                ->orderByDesc('id')
                ->first(['status', 'response_status', 'delivered_at', 'created_at']);

            if ($lastWebhookTest !== null && $lastWebhookTest->status === WebhookDeliveryStatus::Delivered) {
                $webhookTested = true;
            }
        }

        $hasApiTraffic = false;
        if ($partner !== null) {
            $hasApiTraffic = $partner->messages()->exists()
                || $partner->usageRecords()->exists()
                || ApiRequest::query()
                    ->where('partner_id', $partner->id)
                    ->where('path', 'like', '%/messages%')
                    ->exists();
        }

        $whatsappComplete = $activeConnection !== null;
        $whatsappProgress = ! $whatsappComplete && ($pendingConnections > 0 || ($partner && $partner->whatsappConnections()->exists()));

        $step1Status = $whatsappComplete ? 'complete' : ($whatsappProgress ? 'in_progress' : 'not_started');
        $step2Status = ! $subscriptionReady ? 'not_started' : ($hasApiKey ? 'complete' : ($whatsappComplete ? 'in_progress' : 'not_started'));
        $step3Status = ! $subscriptionReady ? 'not_started' : ($webhookEndpoint !== null ? 'complete' : ($hasApiKey ? 'in_progress' : 'not_started'));
        $step4Status = ! $subscriptionReady ? 'not_started' : ($hasApiTraffic ? 'complete' : ($webhookEndpoint !== null && $hasApiKey && $whatsappComplete ? 'in_progress' : 'not_started'));

        $integrationComplete = $whatsappComplete && $hasApiKey && $webhookEndpoint !== null && $hasApiTraffic;

        $headline = $this->headline(
            $subscriptionReady,
            $step1Status,
            $step2Status,
            $step3Status,
            $step4Status,
            $webhookEndpoint !== null,
            $webhookTested,
            $integrationComplete,
        );

        return [
            'subscription_ready' => $subscriptionReady,
            'headline' => $headline,
            'integration_complete' => $integrationComplete,
            'steps' => [
                $this->step(
                    key: 'whatsapp',
                    number: 1,
                    title: 'Connect your WhatsApp number',
                    description: 'Connect a WhatsApp Business number through Meta.',
                    status: $step1Status,
                    complete: $whatsappComplete,
                    href: route('app.connections'),
                    action_label: $whatsappComplete ? 'Manage WhatsApp' : 'Connect WhatsApp',
                    detail: $activeConnection?->display_phone_number,
                ),
                $this->step(
                    key: 'api_key',
                    number: 2,
                    title: 'Create your API key',
                    description: 'Your CRM uses this key to securely call IQPigeon.',
                    status: $step2Status,
                    complete: $hasApiKey,
                    href: route('app.api-keys'),
                    action_label: $hasApiKey ? 'View API keys' : 'Create API key',
                ),
                $this->step(
                    key: 'webhook',
                    number: 3,
                    title: 'Connect your CRM',
                    description: 'Tell IQPigeon where incoming WhatsApp messages should be delivered.',
                    status: $step3Status,
                    complete: $webhookEndpoint !== null,
                    href: route('app.webhooks'),
                    action_label: $webhookEndpoint !== null ? 'Manage webhooks' : 'Add CRM webhook',
                    detail: $webhookEndpoint?->url,
                    meta: [
                        'webhook_tested' => $webhookTested,
                        'last_test_status' => $lastWebhookTest?->status?->value,
                        'last_test_http' => $lastWebhookTest?->response_status,
                    ],
                ),
                $this->step(
                    key: 'first_message',
                    number: 4,
                    title: 'Send your first message',
                    description: 'Everything is ready. Use the example below to send your first WhatsApp message.',
                    status: $step4Status,
                    complete: $hasApiTraffic,
                    href: null,
                    action_label: null,
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function step(
        string $key,
        int $number,
        string $title,
        string $description,
        string $status,
        bool $complete,
        ?string $href,
        ?string $action_label,
        ?string $detail = null,
        array $meta = [],
    ): array {
        return array_merge([
            'key' => $key,
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'complete' => $complete,
            'href' => $href,
            'action_label' => $action_label,
            'detail' => $detail,
        ], $meta === [] ? [] : ['meta' => $meta]);
    }

    private function headline(
        bool $subscriptionReady,
        string $step1,
        string $step2,
        string $step3,
        string $step4,
        bool $hasWebhook,
        bool $webhookTested,
        bool $integrationComplete,
    ): string {
        if (! $subscriptionReady) {
            return 'Activate your subscription to start integrating';
        }

        if ($integrationComplete) {
            return 'Your integration is ready';
        }

        if ($step1 !== 'complete') {
            return 'Connect your WhatsApp number';
        }

        if ($step2 !== 'complete') {
            return 'Create your API key';
        }

        if ($step3 !== 'complete') {
            return 'Add your CRM webhook';
        }

        if ($hasWebhook && ! $webhookTested) {
            return 'Test your webhook';
        }

        if ($step4 !== 'complete') {
            return 'Send your first message';
        }

        return 'Your integration is ready';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function primaryConnection(?Partner $partner): ?array
    {
        if ($partner === null) {
            return null;
        }

        $connection = $partner->whatsappConnections()
            ->where('connection_status', ConnectionStatus::Active)
            ->orderByDesc('id')
            ->first();

        if ($connection === null) {
            $connection = $partner->whatsappConnections()->orderByDesc('id')->first();
        }

        if ($connection === null) {
            return null;
        }

        return [
            'uuid' => $connection->uuid,
            'status' => $connection->connection_status->value,
            'display_phone' => $connection->display_phone_number,
            'waba_id' => $connection->waba_id,
            'updated_at' => $connection->updated_at?->toIso8601String(),
        ];
    }
}
