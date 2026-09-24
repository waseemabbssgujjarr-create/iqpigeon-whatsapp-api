<?php

namespace App\Services;

use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\User;

class OnboardingChecklistService
{
    /**
     * @return array<int, array{key: string, label: string, title: string, description: string, action_label: string|null, complete: bool, href: string|null, disabled?: bool}>
     */
    public function stepsFor(User $user, ?Partner $partner): array
    {
        $verificationEnabled = (bool) config('auth.email_verification_enabled');

        $steps = [
            [
                'key' => 'account',
                'label' => 'Create account',
                'title' => 'Platform account',
                'description' => 'Your dashboard login for billing, API keys, connections, and webhooks.',
                'action_label' => null,
                'complete' => true,
                'href' => null,
            ],
            [
                'key' => 'verify_email',
                'label' => $verificationEnabled ? 'Verify email' : 'Verify email (disabled)',
                'title' => 'Verify email',
                'description' => 'Confirm your email address to unlock the full dashboard when verification is enabled.',
                'action_label' => $verificationEnabled ? 'Verify email' : null,
                'complete' => $verificationEnabled ? $user->hasVerifiedEmail() : true,
                'href' => $verificationEnabled ? route('verification.notice') : null,
                'disabled' => ! $verificationEnabled,
            ],
            [
                'key' => 'choose_plan',
                'label' => 'Choose plan',
                'title' => 'Choose a plan',
                'description' => 'Select the IQPigeon platform subscription that covers API access, dashboard, and webhooks.',
                'action_label' => 'View plans',
                'complete' => $partner !== null && $partner->subscriptions()->exists(),
                'href' => route('app.billing'),
            ],
            [
                'key' => 'payment',
                'label' => 'Complete payment',
                'title' => 'Activate subscription',
                'description' => 'Complete Stripe checkout. Your account activates after Stripe confirms payment via webhook.',
                'action_label' => 'Go to billing',
                'complete' => $partner !== null && in_array($partner->provisioning_status, [
                    ProvisioningStatus::PaymentConfirmed,
                    ProvisioningStatus::Provisioning,
                    ProvisioningStatus::Active,
                ], true),
                'href' => route('app.billing'),
            ],
            [
                'key' => 'api_key',
                'label' => 'Generate API key',
                'title' => 'Create an API key',
                'description' => 'Your CRM uses this Bearer token for server-to-server calls. It is not a Meta token.',
                'action_label' => 'Manage API keys',
                'complete' => $partner !== null && $partner->apiKeys()->whereNull('revoked_at')->exists(),
                'href' => route('app.api-keys'),
            ],
            [
                'key' => 'whatsapp',
                'label' => 'Connect WhatsApp',
                'title' => 'Connect WhatsApp',
                'description' => 'Connect the WhatsApp Business number you want to send through via Meta Embedded Signup.',
                'action_label' => 'Connect WhatsApp',
                'complete' => $partner !== null && $partner->whatsappConnections()->where('connection_status', \App\Enums\ConnectionStatus::Active)->exists(),
                'href' => route('app.connections'),
            ],
            [
                'key' => 'webhook',
                'label' => 'Configure webhook',
                'title' => 'Configure CRM webhook',
                'description' => 'Tell IQPigeon where to deliver inbound messages and delivery events for your CRM.',
                'action_label' => 'Configure webhook',
                'complete' => $partner !== null && $partner->webhookEndpoints()->where('is_active', true)->exists(),
                'href' => route('app.webhooks'),
            ],
            [
                'key' => 'first_request',
                'label' => 'Send first API request',
                'title' => 'Send first API request',
                'description' => 'Use your API key and connection ID to call POST /api/v1/messages from your backend.',
                'action_label' => 'Open API docs',
                'complete' => $partner !== null && $partner->usageRecords()->exists(),
                'href' => route('docs'),
            ],
        ];

        return $steps;
    }

    /**
     * @return array{headline: string, remaining: int, next_step: array<string, mixed>|null}
     */
    public function summary(User $user, ?Partner $partner): array
    {
        $steps = $this->stepsFor($user, $partner);
        $pending = array_values(array_filter($steps, fn ($s) => ! $s['complete'] && empty($s['disabled'])));

        if ($pending === []) {
            return [
                'headline' => 'You are ready to send API requests.',
                'remaining' => 0,
                'next_step' => null,
            ];
        }

        $next = $pending[0];

        return [
            'headline' => $next['title'],
            'remaining' => count($pending),
            'next_step' => $next,
        ];
    }

    public function isComplete(User $user, ?Partner $partner): bool
    {
        foreach ($this->stepsFor($user, $partner) as $step) {
            if (! $step['complete']) {
                return false;
            }
        }

        return true;
    }
}
