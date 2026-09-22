<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Exceptions\PartnerWebhookRetryableException;
use App\Models\Partner;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\WebhookUrlValidator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PartnerWebhookDeliveryExecutor
{
    public function __construct(
        private readonly WebhookSignatureService $signer,
        private readonly WebhookUrlValidator $urlValidator,
        private readonly PartnerWebhookRetryPolicy $retryPolicy,
    ) {}

    /**
     * Perform one delivery attempt. Throws PartnerWebhookRetryableException when the queue should retry.
     */
    public function attempt(WebhookDelivery $delivery, int $queueAttemptNumber): void
    {
        $delivery->refresh();

        if ($delivery->status === WebhookDeliveryStatus::Delivered) {
            return;
        }

        $endpoint = WebhookEndpoint::query()->find($delivery->webhook_endpoint_id);

        if ($endpoint === null || ! $endpoint->is_active) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Failed,
                'error_message' => 'Webhook endpoint disabled or removed.',
            ])->save();

            return;
        }

        if ($endpoint->partner_id !== $delivery->partner_id) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Failed,
                'error_message' => 'Endpoint partner mismatch.',
            ])->save();

            return;
        }

        if (! $this->urlValidator->isAllowed($endpoint->url)) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Failed,
                'error_message' => 'Webhook URL blocked by SSRF policy.',
            ])->save();

            return;
        }

        $partner = Partner::query()->find($delivery->partner_id);

        if ($partner === null) {
            return;
        }

        $body = json_encode([
            'id' => $delivery->event_id,
            'type' => $delivery->event_type,
            'data' => $delivery->payload['data'] ?? $delivery->payload,
        ], JSON_THROW_ON_ERROR);

        $secret = (string) ($endpoint->secret ?? $partner->webhook_secret ?? '');

        if ($secret === '') {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Failed,
                'error_message' => 'Missing webhook signing secret.',
            ])->save();

            return;
        }

        $requestId = 'evt_'.Str::uuid()->toString();
        $headers = $this->signer->headers($secret, $body, $requestId, $delivery->event_id);

        $delivery->forceFill([
            'status' => WebhookDeliveryStatus::Delivering,
            'attempt_count' => $delivery->attempt_count + 1,
            'next_retry_at' => null,
        ])->save();

        try {
            $response = Http::timeout($this->retryPolicy->timeoutSeconds())
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($endpoint->url);
        } catch (ConnectionException $exception) {
            $this->failOrRetry($delivery, $queueAttemptNumber, null, $exception->getMessage(), $exception);

            return;
        }

        if ($response->successful()) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Delivered,
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 4000),
                'delivered_at' => now(),
                'error_message' => null,
            ])->save();

            return;
        }

        $this->failOrRetry(
            $delivery,
            $queueAttemptNumber,
            $response->status(),
            'Non-success HTTP response',
            null,
            $response,
        );
    }

    private function failOrRetry(
        WebhookDelivery $delivery,
        int $queueAttemptNumber,
        ?int $statusCode,
        string $message,
        ?\Throwable $throwable = null,
        ?Response $response = null,
    ): void {
        $retryable = $throwable !== null
            ? $this->retryPolicy->shouldRetryThrowable($throwable)
            : $this->retryPolicy->shouldRetryHttpStatus($statusCode);

        $maxAttempts = $this->retryPolicy->maxAttempts();
        $exhausted = $queueAttemptNumber >= $maxAttempts;

        if ($retryable && ! $exhausted) {
            $delay = $this->retryPolicy->backoffDelayForAttempt($queueAttemptNumber);

            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Pending,
                'response_status' => $statusCode,
                'response_body' => $response !== null ? substr($response->body(), 0, 4000) : null,
                'error_message' => $message,
                'next_retry_at' => now()->addSeconds($delay),
            ])->save();

            throw new PartnerWebhookRetryableException($message);
        }

        $delivery->forceFill([
            'status' => $exhausted && $retryable ? WebhookDeliveryStatus::Dead : WebhookDeliveryStatus::Failed,
            'response_status' => $statusCode,
            'response_body' => $response !== null ? substr($response->body(), 0, 4000) : null,
            'error_message' => $exhausted && $retryable
                ? 'Maximum delivery attempts exceeded.'
                : $message,
            'next_retry_at' => null,
        ])->save();
    }
}
