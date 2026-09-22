<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Services\IdempotencyService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleIdempotency
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === '') {
            return ApiResponse::error(
                'idempotency_key_required',
                'Idempotency-Key header is required for this request.',
                400,
            )->toResponse($request);
        }

        /** @var Partner|null $partner */
        $partner = $request->attributes->get('partner');

        if ($partner === null) {
            return ApiResponse::error('partner_missing', 'Partner context is missing.', 500)
                ->toResponse($request);
        }

        $requestHash = $this->idempotencyService->hashRequest($request);

        $existing = $this->idempotencyService->find($partner, $idempotencyKey);

        if ($existing !== null) {
            if (! hash_equals($existing->request_hash, $requestHash)) {
                return ApiResponse::error(
                    'idempotency_key_mismatch',
                    'Idempotency-Key was already used with a different request payload.',
                    409,
                )->toResponse($request);
            }

            if ($existing->response_status !== null) {
                return response()->json($existing->response_body, (int) $existing->response_status);
            }
        } else {
            $this->idempotencyService->begin($partner, $idempotencyKey, $request, $requestHash);
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $this->idempotencyService->complete(
                $partner,
                $idempotencyKey,
                $response->getStatusCode(),
                $this->decodeResponseBody($response),
            );
        }

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeResponseBody(Response $response): ?array
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : ['raw' => $content];
    }
}
