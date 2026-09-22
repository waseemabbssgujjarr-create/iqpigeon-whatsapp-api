<?php

namespace App\Jobs;

use App\Models\Partner;
use App\Services\PartnerProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProvisionPartnerJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $partnerId,
    ) {}

    public function handle(PartnerProvisioningService $provisioning): void
    {
        $partner = Partner::query()->find($this->partnerId);

        if ($partner === null) {
            return;
        }

        if ($partner->provisioning_status === \App\Enums\ProvisioningStatus::Active) {
            return;
        }

        $result = $provisioning->provision($partner);

        if ($result['api_key_secret'] !== null) {
            \Illuminate\Support\Facades\Cache::put(
                'partner_api_secret_once_'.$partner->id,
                $result['api_key_secret'],
                now()->addMinutes(30),
            );
        }
    }
}
