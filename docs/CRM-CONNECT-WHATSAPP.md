# CRM Connect WhatsApp (hosted onboarding)

## Promise

Your CRM customer connects WhatsApp **without Meta access tokens in your app**. IQPigeon stores Meta credentials server-side.

## Flow

```
CRM SERVER   POST /api/v1/connections  (+ Idempotency-Key, scope connections.write)
     ↓
CRM BROWSER  redirect → data.onboarding_url
     ↓
Meta Embedded Signup (IQPigeon /oauth/meta/start → /oauth/meta/callback)
     ↓
CRM BROWSER  redirect → allowlisted return_url (optional query + optional signature)
     ↓
CRM SERVER   GET /api/v1/connections/{connection_id}  (authoritative)
     ↓
Only if status=active → mark WhatsApp connected
```

**Do not trust the browser callback alone.**

## Allowlist return URLs

Settings → CRM integration (`partner.metadata.allowed_return_urls`) or env `IQPIGEON_CRM_RETURN_URL_ALLOWLIST`.

## Integration signing secret vs webhook secret

| Secret | Purpose |
|--------|---------|
| **Integration signing secret** (Settings) | Optional HMAC on onboarding **return URL** query params |
| **Webhook endpoint secret** | HMAC on **inbound event** POST bodies (`timestamp.body`) |

Onboarding return signature (when configured):

```
HMAC-SHA256(integration_signing_secret, http_build_query(sorted_query_params_without_signature))
```

Webhook signature:

```
HMAC-SHA256(webhook_endpoint_secret, timestamp + "." + raw_body)
```

If no integration signing secret is configured, the return URL may omit `signature` — you **must** still call `GET /connections/{id}`.

## API: start onboarding

```http
POST /api/v1/connections
Authorization: Bearer iqp_live_…
Content-Type: application/json
Idempotency-Key: connect-customer_123-attempt-1

{
  "external_ref": "your_crm_customer_id",
  "return_url": "https://your-crm.com/integrations/iqpigeon/callback"
}
```

Response `data.connection.id`, `data.onboarding_url`, `data.expires_at`.

## Verify (required)

```http
GET /api/v1/connections/{connection_id}
Authorization: Bearer …
```

Mark connected only when `data.connection.status` is `active`.

## OAuth failure

If Meta onboarding fails, the user may land on the IQPigeon dashboard instead of your CRM. Poll `GET /connections/{id}`; do not assume success from the browser.

## Dashboard flow

**Connect WhatsApp** in the dashboard uses the same connection model without `return_url`.
