<?php

namespace App\Http\Middleware;

use App\Models\ApiRequest;
use App\Models\ApiKey;
use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public const STARTED_AT_ATTRIBUTE = 'api_log_started_at';

    /** Maximum persisted duration (24 hours in ms). Fits unsignedInteger and avoids garbage values. */
    private const MAX_DURATION_MS = 86_400_000;

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::STARTED_AT_ATTRIBUTE, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $requestId = (string) $request->attributes->get('request_id', '');

        if ($requestId === '') {
            return;
        }

        /** @var Partner|null $partner */
        $partner = $request->attributes->get('partner');
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE);

        if (! is_float($startedAt) && ! is_int($startedAt)) {
            return;
        }

        $durationMs = (int) round((microtime(true) - (float) $startedAt) * 1000);
        $durationMs = max(0, min($durationMs, self::MAX_DURATION_MS));

        try {
            ApiRequest::query()->updateOrCreate(
                ['request_id' => $requestId],
                [
                    'partner_id' => $partner?->id,
                    'api_key_id' => $apiKey?->id,
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 512),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('api.request_log_failed', [
                'request_id' => $requestId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
