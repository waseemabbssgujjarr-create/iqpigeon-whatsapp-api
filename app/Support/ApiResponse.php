<?php

namespace App\Support;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse implements Responsable
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?array $error = null,
        public int $status = 200,
        public array $meta = [],
    ) {}

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function ok(mixed $data = null, int $status = 200, array $meta = []): self
    {
        return new self(true, $data, null, $status, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function error(string $code, string $message, int $status = 400, array $meta = []): self
    {
        return new self(
            false,
            null,
            ['code' => $code, 'message' => $message],
            $status,
            $meta,
        );
    }

    public function toResponse($request): JsonResponse
    {
        /** @var Request $request */
        $requestId = (string) $request->attributes->get('request_id', '');

        $payload = [
            'success' => $this->success,
            'request_id' => $requestId !== '' ? $requestId : null,
        ];

        if ($this->success) {
            $payload['data'] = $this->normalizeForJson($this->data);
        } else {
            $payload['error'] = $this->error;
        }

        if ($this->meta !== []) {
            $payload['meta'] = $this->meta;
        }

        return response()->json($payload, $this->status);
    }

    private function normalizeForJson(mixed $value): mixed
    {
        if ($value instanceof JsonResource) {
            return $value->resolve(request());
        }

        if ($value instanceof ResourceCollection) {
            return $value->resolve(request());
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeForJson($item);
            }

            return $normalized;
        }

        return $value;
    }
}
