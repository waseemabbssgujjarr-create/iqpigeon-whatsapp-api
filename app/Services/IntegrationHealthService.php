<?php

namespace App\Services;

use App\Enums\ConnectionStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Models\ApiRequest;
use App\Models\Partner;
use App\Models\WebhookDelivery;

class IntegrationHealthService
{
    /**
     * @return array{
     *     ready: bool,
     *     headline: string,
     *     checks: array<int, array{key: string, label: string, ok: bool, detail: string|null}>
     * }
     */
    public function forPartner(?Partner $partner): array
    {
        if ($partner === null) {
            return $this->emptyHealth('Create your account and activate a plan.');
        }

        $subscriptionReady = in_array($partner->provisioning_status, [
            ProvisioningStatus::PaymentConfirmed,
            ProvisioningStatus::Provisioning,
            ProvisioningStatus::Active,
        ], true);

        $hasApiKey = $partner->apiKeys()->whereNull('revoked_at')->exists();

        $activeConnection = $partner->whatsappConnections()
            ->where('connection_status', ConnectionStatus::Active)
            ->exists();

        $webhook = $partner->webhookEndpoints()->where('is_active', true)->first();

        $webhookTestDelivered = WebhookDelivery::query()
            ->where('partner_id', $partner->id)
            ->where('event_type', 'webhook.test')
            ->where('status', WebhookDeliveryStatus::Delivered)
            ->exists();

        $outbound = $partner->messages()->exists()
            || ApiRequest::query()
                ->where('partner_id', $partner->id)
                ->where('path', 'like', '%/messages%')
                ->where('status_code', '>=', 200)
                ->where('status_code', '<', 300)
                ->exists();

        $inbound = WebhookDelivery::query()
            ->where('partner_id', $partner->id)
            ->where('event_type', '!=', 'webhook.test')
            ->where('status', WebhookDeliveryStatus::Delivered)
            ->exists();

        $checks = [
            [
                'key' => 'whatsapp',
                'label' => 'WhatsApp connected',
                'ok' => $activeConnection,
                'detail' => $activeConnection ? null : 'Connect a WhatsApp Business number.',
            ],
            [
                'key' => 'api_auth',
                'label' => 'API authentication',
                'ok' => $hasApiKey && $subscriptionReady,
                'detail' => ! $subscriptionReady ? 'Activate subscription.' : (! $hasApiKey ? 'Create an API key.' : null),
            ],
            [
                'key' => 'webhook_config',
                'label' => 'CRM webhook',
                'ok' => $webhook !== null,
                'detail' => $webhook === null ? 'Add your CRM webhook URL.' : null,
            ],
            [
                'key' => 'webhook_test',
                'label' => 'Webhook test',
                'ok' => $webhookTestDelivered,
                'detail' => ! $webhookTestDelivered ? 'Run a webhook test from the Webhooks page.' : null,
            ],
            [
                'key' => 'outbound',
                'label' => 'Outbound ready',
                'ok' => $outbound,
                'detail' => $outbound ? null : 'Send a message via POST /api/v1/messages.',
            ],
            [
                'key' => 'inbound',
                'label' => 'Inbound ready',
                'ok' => $inbound,
                'detail' => $inbound ? null : 'Inbound deliveries appear after WhatsApp events reach your webhook.',
            ],
        ];

        $fullyReady = $activeConnection && $hasApiKey && $subscriptionReady && $webhook !== null
            && $webhookTestDelivered && $outbound && $inbound;

        return [
            'ready' => $fullyReady,
            'headline' => $fullyReady ? 'Your CRM is ready' : 'Complete setup to go live',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{ready: bool, headline: string, checks: array<int, array<string, mixed>>}
     */
    private function emptyHealth(string $headline): array
    {
        return [
            'ready' => false,
            'headline' => $headline,
            'checks' => [],
        ];
    }
}
