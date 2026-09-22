<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiScopes
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        if ($apiKey === null) {
            return ApiResponse::error('unauthenticated', 'API key context is missing.', 401)
                ->toResponse($request);
        }

        foreach ($requiredScopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return ApiResponse::error(
                    'insufficient_scope',
                    "Missing required scope [{$scope}].",
                    403,
                )->toResponse($request);
            }
        }

        return $next($request);
    }
}
