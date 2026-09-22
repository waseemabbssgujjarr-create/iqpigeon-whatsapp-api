<?php

namespace App\Http\Middleware;

use App\Models\ApiRequest;
use App\Models\ApiKey;
use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    private float $startedAt = 0;

    public function handle(Request $request, Closure $next): Response
    {
        $this->startedAt = microtime(true);

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

        $durationMs = (int) round((microtime(true) - $this->startedAt) * 1000);

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
    }
}
