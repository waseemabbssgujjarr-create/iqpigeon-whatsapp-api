<?php

namespace App\Services;

use App\Enums\PartnerStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use Illuminate\Support\Str;

class PartnerProvisioningService
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
        private readonly PlanEntitlementService $entitlements,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @return array{partner: Partner, api_key_secret: string|null}
     */
    public function provision(Partner $partner): array
    {
        $partner->forceFill([
            'provisioning_status' => ProvisioningStatus::Provisioning,
        ])->save();

        if ($partner->webhook_secret === null) {
            $partner->forceFill([
                'webhook_secret' => Str::random(64),
            ])->save();
        }

        $partner->forceFill([
            'status' => PartnerStatus::Active,
            'provisioning_status' => ProvisioningStatus::Active,
            'activated_at' => now(),
        ])->save();

        $apiKeySecret = null;

        if ($partner->apiKeys()->whereNull('revoked_at')->doesntExist()) {
            $scopes = $this->entitlements->defaultScopesForPartner($partner);
            $generated = $this->apiKeyService->generate($partner, 'Primary', $scopes);
            $apiKeySecret = $generated['secret'];
        }

        $this->auditLog->log('partner.provisioned', $partner, subject: $partner);

        return [
            'partner' => $partner->refresh(),
            'api_key_secret' => $apiKeySecret,
        ];
    }
}
