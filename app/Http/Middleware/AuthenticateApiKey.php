<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return ApiResponse::error('unauthenticated', 'Missing or invalid Authorization header.', 401)
                ->toResponse($request);
        }

        $secret = trim(substr($header, 7));

        if ($secret === '') {
            return ApiResponse::error('unauthenticated', 'Missing API key.', 401)
                ->toResponse($request);
        }

        $apiKey = $this->apiKeyService->findBySecret($secret);

        if ($apiKey === null || $apiKey->isExpired()) {
            return ApiResponse::error('unauthenticated', 'Invalid or expired API key.', 401)
                ->toResponse($request);
        }

        $apiKey->loadMissing('scopes', 'partner');
        $this->apiKeyService->touchLastUsed($apiKey);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('partner', $apiKey->partner);

        return $next($request);
    }
}
