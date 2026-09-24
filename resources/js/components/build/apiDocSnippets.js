/** Examples aligned with ApiResponse + WhatsappConnectionResource + PartnerWebhookDeliveryExecutor */

export const standardErrorShape = `{
  "success": false,
  "error": {
    "code": "connection_not_found",
    "message": "Connection not found."
  },
  "request_id": "req_..."
}`;

export const postConnectionsRequest = (apiBaseUrl, returnUrl) => `curl -X POST "${apiBaseUrl}/connections" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: connect-customer_123-$(date +%s)" \\
  -H "Accept: application/json" \\
  -d '{
  "external_ref": "customer_123",
  "return_url": "${returnUrl}"
}'`;

export const postConnectionsResponse = `{
  "success": true,
  "request_id": "req_...",
  "data": {
    "connection": {
      "id": "f8480962-5010-4c4d-aa9d-dcefb5f0a1b2",
      "external_ref": "customer_123",
      "status": "pending",
      "display_phone_number": null,
      "phone_number_id": null,
      "waba_id": null,
      "connected_at": null,
      "onboarding_url": "https://whatsappapi.iqpigeon.com/oauth/meta/start?token=...",
      "onboarding_expires_at": "2026-09-26T12:00:00+00:00"
    },
    "onboarding_url": "https://whatsappapi.iqpigeon.com/oauth/meta/start?token=...",
    "expires_at": "2026-09-26T12:00:00+00:00"
  }
}`;

export const getConnectionRequest = (apiBaseUrl, connectionId) => `curl -s "${apiBaseUrl}/connections/${connectionId}" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Accept: application/json"`;

export const getConnectionResponseActive = `{
  "success": true,
  "request_id": "req_...",
  "data": {
    "connection": {
      "id": "f8480962-5010-4c4d-aa9d-dcefb5f0a1b2",
      "external_ref": "customer_123",
      "status": "active",
      "display_phone_number": "+15551234567",
      "phone_number_id": "123456789",
      "waba_id": "987654321",
      "connected_at": "2026-09-24T10:00:00+00:00"
    }
  }
}`;

export const onboardingReturnVerifyPhp = `$query = [
    'connection_id' => $_GET['connection_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'external_ref' => $_GET['external_ref'] ?? '',
    'timestamp' => $_GET['timestamp'] ?? '',
];
$query = array_filter($query, fn ($v) => $v !== '');
ksort($query);
$provided = $_GET['signature'] ?? '';
$expected = hash_hmac('sha256', http_build_query($query), $integrationSigningSecret);
// Optional: only if you configured integration_signing_secret in IQPigeon Settings.

// REQUIRED — authoritative check (server-to-server):
$connectionId = $query['connection_id'];
$response = $iqpigeon->get("/connections/{$connectionId}");
// Mark connected in CRM only when data.connection.status === 'active'.`;

export const webhookBodyShape = `{
  "id": "evt_uuid",
  "type": "messages",
  "data": {
    "messaging_product": "whatsapp",
    "metadata": {
      "display_phone_number": "...",
      "phone_number_id": "..."
    },
    "messages": [ ... ],
    "statuses": [ ... ]
  }
}`;

export const webhookSignaturePhp = `$timestamp = $_SERVER['HTTP_X_IQP_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_IQP_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');
$expected = hash_hmac('sha256', $timestamp . '.' . $body, $webhookEndpointSecret);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}`;

export const postMessageResponse = `{
  "success": true,
  "request_id": "req_...",
  "data": {
    "message": {
      "id": "msg_uuid",
      "connection_id": "connection_uuid",
      "direction": "outbound",
      "status": "queued",
      "to": "+15551234567",
      "type": "text",
      "body": "Hello",
      "created_at": "2026-09-24T10:00:00+00:00"
    }
  }
}`;

export const API_ERROR_CATALOG = [
    { code: 'unauthenticated', http: 401, meaning: 'Missing/invalid Bearer API key.', action: 'Fix Authorization header; rotate key if compromised.' },
    { code: 'insufficient_scope', http: 403, meaning: 'API key lacks required scope.', action: 'Create a key with the scope shown in the error message.' },
    { code: 'partner_not_active', http: 403, meaning: 'Subscription not active for API access.', action: 'Complete billing / wait for Stripe webhook activation.' },
    { code: 'rate_limit_exceeded', http: 429, meaning: 'Too many requests this minute.', action: 'Backoff using Retry-After header.' },
    { code: 'idempotency_key_required', http: 400, meaning: 'Idempotency-Key header missing.', action: 'Send a unique Idempotency-Key on POST /connections and POST /messages.' },
    { code: 'idempotency_key_mismatch', http: 409, meaning: 'Same key reused with different body.', action: 'Use a new key or replay the original request unchanged.' },
    { code: 'return_url_not_allowed', http: 422, meaning: 'return_url not on partner allowlist.', action: 'Add URL in Settings → CRM integration.' },
    { code: 'connection_limit', http: 422, meaning: 'Cannot add more connections.', action: 'Upgrade plan or remove unused connections.' },
    { code: 'connection_not_found', http: 404, meaning: 'UUID not found for this partner.', action: 'Verify connection_id and partner API key.' },
    { code: 'message_not_found', http: 404, meaning: 'Message UUID not found.', action: 'Check message id from send response.' },
    { code: 'webhook_not_found', http: 404, meaning: 'Webhook endpoint id not found.', action: 'Use dashboard or GET /webhooks.' },
    { code: 'partner_missing', http: 500, meaning: 'Internal partner context error.', action: 'Retry; contact support with request_id.' },
    { code: 'templates_unavailable', http: 502, meaning: 'Meta templates fetch failed.', action: 'Retry later; check connection and Meta status.' },
];
