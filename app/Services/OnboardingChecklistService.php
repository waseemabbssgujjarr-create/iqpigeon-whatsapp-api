<?php

namespace App\Services;

use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Models\User;

class OnboardingChecklistService
{
    /**
     * @return array<int, array{key: string, label: string, complete: bool, href: string|null}>
     */
    public function stepsFor(User $user, ?Partner $partner): array
    {
        $verificationEnabled = (bool) config('auth.email_verification_enabled');

        $steps = [
            [
                'key' => 'account',
                'label' => 'Create account',
                'complete' => true,
                'href' => null,
            ],
            [
                'key' => 'verify_email',
                'label' => $verificationEnabled ? 'Verify email' : 'Verify email (disabled)',
                'complete' => $verificationEnabled ? $user->hasVerifiedEmail() : true,
                'href' => $verificationEnabled ? route('verification.notice') : null,
                'disabled' => ! $verificationEnabled,
            ],
            [
                'key' => 'choose_plan',
                'label' => 'Choose plan',
                'complete' => $partner !== null && $partner->subscriptions()->exists(),
                'href' => route('app.billing'),
            ],
            [
                'key' => 'payment',
                'label' => 'Complete payment',
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
                'complete' => $partner !== null && $partner->apiKeys()->whereNull('revoked_at')->exists(),
                'href' => route('app.api-keys'),
            ],
            [
                'key' => 'whatsapp',
                'label' => 'Connect WhatsApp',
                'complete' => $partner !== null && $partner->whatsappConnections()->where('connection_status', \App\Enums\ConnectionStatus::Active)->exists(),
                'href' => route('app.connections'),
            ],
            [
                'key' => 'webhook',
                'label' => 'Configure webhook',
                'complete' => $partner !== null && $partner->webhookEndpoints()->where('is_active', true)->exists(),
                'href' => route('app.webhooks'),
            ],
            [
                'key' => 'first_request',
                'label' => 'Send first API request',
                'complete' => $partner !== null && $partner->usageRecords()->exists(),
                'href' => route('developers'),
            ],
        ];

        return $steps;
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
