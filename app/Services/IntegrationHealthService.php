<?php

namespace App\Services;

use App\Enums\ConnectionStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Models\Partner;
use App\Models\WebhookDelivery;
use App\Services\Meta\WhatsappCloudApiRegistrationService;

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

        $registration = app(WhatsappCloudApiRegistrationService::class);

        $connections = $partner->whatsappConnections()->get();

        $activeConnection = $connections->contains(
            fn ($c) => $c->connection_status === ConnectionStatus::Active
        );

        $sendReady = $connections->contains(
            fn ($c) => $registration->isRegisteredForSending($c)
        );

        $webhook = $partner->webhookEndpoints()->where('is_active', true)->first();

        $webhookTestDelivered = WebhookDelivery::query()
            ->where('partner_id', $partner->id)
            ->where('event_type', 'webhook.test')
            ->where('status', WebhookDeliveryStatus::Delivered)
            ->exists();

        $outbound = $sendReady;

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
                'key' => 'whatsapp_send',
                'label' => 'WhatsApp send ready',
                'ok' => $sendReady,
                'detail' => $sendReady ? null : 'Complete Cloud API phone registration (6-digit PIN) on the Connections page.',
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
                'detail' => $outbound ? null : 'Register your WhatsApp number for Cloud API sending.',
            ],
            [
                'key' => 'inbound',
                'label' => 'Inbound ready',
                'ok' => $inbound,
                'detail' => $inbound ? null : 'Inbound deliveries appear after WhatsApp events reach your webhook.',
            ],
        ];

        $fullyReady = $sendReady && $hasApiKey && $subscriptionReady && $webhook !== null
            && $webhookTestDelivered && $inbound;

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
