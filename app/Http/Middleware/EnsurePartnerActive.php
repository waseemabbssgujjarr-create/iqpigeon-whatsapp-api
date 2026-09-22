<?php

namespace App\Http\Middleware;

use App\Enums\ProvisioningStatus;
use App\Models\Partner;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Partner|null $partner */
        $partner = $request->attributes->get('partner');

        if ($partner === null) {
            return ApiResponse::error('partner_missing', 'Partner context is missing.', 500)
                ->toResponse($request);
        }

        if ($partner->provisioning_status !== ProvisioningStatus::Active) {
            return ApiResponse::error(
                'partner_not_active',
                'Partner account is not active for API access.',
                403,
            )->toResponse($request);
        }

        return $next($request);
    }
}
