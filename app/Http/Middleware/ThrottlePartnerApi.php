<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Services\PlanEntitlementService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePartnerApi
{
    public function __construct(
        private readonly PlanEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Partner|null $partner */
        $partner = $request->attributes->get('partner');

        if ($partner === null) {
            return $next($request);
        }

        $limit = max(1, $this->entitlements->rateLimitPerMinute($partner));
        $key = 'partner-api:'.$partner->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return ApiResponse::error(
                'rate_limit_exceeded',
                'Too many API requests. Retry later.',
                429,
                ['retry_after' => $retryAfter],
            )->toResponse($request)->withHeaders([
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
