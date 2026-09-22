# IQPigeon WhatsApp API Platform — Architecture

**Repository:** `iqpigeon-whatsapp-api` (separate GitHub repo from main IQPigeon)  
**Product URL:** https://whatsappapi.iqpigeon.com (staging: https://staging-whatsappapi.iqpigeon.com)  
**Stack:** Laravel **13**, PHP **8.3+**, Inertia, React, TypeScript, Vite, Tailwind, MySQL 8.x, Redis  

**Positioning:** WhatsApp API infrastructure for CRM companies. IQPigeon API subscription revenue is separate from Meta WhatsApp messaging charges.

**Status:** Phase 0 signed off — see [PHASE0-SIGNOFF.md](./PHASE0-SIGNOFF.md).

---

## Locked decisions (Phase 0)

| Item | Decision |
|------|----------|
| Repo | Separate `iqpigeon-whatsapp-api`; independent release/deploy |
| Meta | **Dedicated** Meta app “IQPigeon WhatsApp API” |
| First API key | Auto-create on successful provisioning; show **once**; store hash only |
| Main IQPigeon | **Zero** runtime dependency in MVP (no internal bridge) |
| Meta billing (v1) | Customer-direct by architecture; **no** IQPigeon credit-line attach; **no** `billing_mode` DB column |
| `POST /api/v1/connections` | Creates **Embedded Signup onboarding session** only — not CRM-supplied WABA/token |

---

## A. Relationship to main IQPigeon repo

Main IQPigeon is a PHP monolith with tenant Embedded Signup (`client_whatsapp_accounts`, `bots`) and **no** Partner API runtime. This product **reimplements** Meta/send patterns inside Laravel; it does **not** share database, sessions, or users with main IQPigeon.

Reference-only assets: brand colors from main CSS (purple accent, navy foundation).

---

## B. Proposed architecture

### B.1 Logical layers

```
┌─────────────────────────────────────────────────────────────────┐
│ Public web (Inertia/React)                                       │
│  Marketing │ Auth │ Dashboard │ Docs                             │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│ Application (Laravel 13)                                         │
│  Http/Web          Http/Api/V1        Http/Admin                 │
│  BillingController Provisioning       PartnerAdmin               │
│  StripeWebhook     MetaWebhook        Audit                      │
└─────────┬──────────────────┬──────────────────┬─────────────────┘
          │                  │                  │
┌─────────▼────────┐ ┌───────▼────────┐ ┌───────▼────────┐
│ Domain services  │ │ Infrastructure │ │ Jobs / Queue   │
│ PlanEntitlement  │ │ StripeClient   │ │ DeliverWebhook │
│ MessageService   │ │ MetaGraph      │ │ ProvisionPartner│
│ ConnectionService│ │ CredentialVault│ │ ProcessMetaEvent│
│ WebhookService   │ │ RateLimiter    │ │ RetryFailed... │
│ UsageService     │ │ IdempotencyStore│                 │
└─────────┬────────┘ └───────┬────────┘ └────────────────┘
          │                  │
┌─────────▼──────────────────▼────────────────────────────────────┐
│ MySQL — partners, connections, keys, usage (whatsapp_api_*)      │
│ Redis — cache, rate limits, queue                                 │
└─────────────────────────────────────────────────────────────────┘

External:
  Stripe │ Meta (Dedicated App: ES + Graph + webhook) │ CRM (API + webhooks)

MVP: no dependency on main IQPigeon availability.
Post-MVP: optional `/internal/v1` bridge only when a feature requires it.
```

### B.2 Authentication boundaries

| Actor | Mechanism |
|-------|-----------|
| Dashboard user | Laravel session + email verification; cookies scoped to API product domain |
| Partner API | `Authorization: Bearer iqp_live_…` → hash verify + scopes |
| Stripe | Webhook signature + idempotent `stripe_events` |
| Meta | `X-Hub-Signature-256` on `/webhooks/meta` |
| CRM webhooks | HMAC `timestamp.body` with partner webhook secret |

### B.3 Partner vs user

- `users` — login identity  
- `partners` — CRM company (billing + API subject); 1:1 with owner user at signup  
- `subscriptions` — Stripe-backed, links partner + plan  

### B.4 Provisioning state machine

```
registered → checkout_pending → payment_confirmed → provisioning → active
                                      │                    │
                                      └────────────────────┴→ provisioning_failed
```

**On successful provisioning (mandatory steps):**

1. Activate subscription + partner status  
2. Create encrypted **webhook secret** for CRM delivery signing  
3. **Create first API key** (`iqp_live_…`, plan default scopes, **hash only** in DB)  
4. Return raw key **once** (session flash / dedicated onboarding screen)  
5. Audit log + “API account ready” email  

Never provision from checkout success page alone.

### B.5 Meta & WhatsApp billing (v1)

- Embedded Signup on this product’s OAuth routes (dedicated Meta app).  
- Encrypted Meta tokens in `whatsapp_connection_credentials`; never exposed via API.  
- **Do not** call credit-line sharing / attach APIs in v1.  
- **Do not** store `billing_mode` — v1 assumes customer-direct Meta billing only; document in UI/docs.  
- Copy everywhere: IQPigeon subscription ≠ Meta messaging fees.

### B.6 Connection onboarding (security model)

```
CRM: POST /api/v1/connections  (scope: connections.manage)
  → create whatsapp_connection row (status: pending)
  → create embedded_signup_session (signed state, partner_id, connection_id)
  → response: { connection_id, onboarding_url, expires_at }

End customer opens onboarding_url (or CRM redirects)
  → GET /oauth/meta/start?session=...
  → Meta Embedded Signup UI (customer authenticates with Meta)
  → GET /oauth/meta/callback
  → exchange token, store credentials, subscribe WABA to app, status: active
  → queue connection.connected webhook to CRM
```

CRM **cannot** pass Meta access tokens or arbitrary WABA IDs on `POST /connections`.

### B.7 API request pipeline

```
Request → request_id → AuthenticateApiKey → Partner + subscription state
  → RateLimit(plan) → Scope → AuthorizeConnection (when applicable)
  → Idempotency (mutating) → Service → MetaGraph → persist → JSON
```

### B.8 Meta inbound pipeline

```
Meta POST → verify → 200 ACK → persist system_event → ProcessMetaWebhookJob
  → resolve by phone_number_id → normalize → messages/status tables
  → WebhookDeliveryJob → CRM endpoint (async, retries)
```

---

## C. Database model

**Database:** e.g. `whatsapp_api_production`

### Core tables

- `users`, `partners`, `partner_users` (later)  
- `plans`, `subscriptions`, `stripe_events`, `invoices` (cache)  
- `api_scopes`, `api_keys`, `api_key_scope`  
- `whatsapp_connections` — **no `billing_mode` column in v1**  
- `whatsapp_connection_credentials`  
- `embedded_signup_sessions` — ties API-initiated onboarding to OAuth  
- `messages`, `api_idempotency_keys`  
- `webhook_endpoints`, `webhook_deliveries`  
- `api_requests`, `usage_records`, `audit_logs`, `system_events`  

### `whatsapp_connections` (v1)

| Column | Notes |
|--------|--------|
| id, uuid, partner_id | |
| external_ref | CRM’s customer reference |
| waba_id, phone_number_id, display_phone_number | filled after ES |
| meta_business_id | optional |
| connection_status | pending, active, error, disconnected, revoked |
| metadata | json |
| connected_at, disconnected_at | |
| UNIQUE(partner_id, phone_number_id) | |

Meta messaging billing is **implicitly** customer-direct in v1; add columns only when a second model is implemented.

### `embedded_signup_sessions`

| partner_id, whatsapp_connection_id, token (hashed state), status, expires_at, completed_at |

### First API key (provisioning)

Store in `api_keys`: `label` = “Primary”, `environment` = live, `prefix`, `key_hash`, scopes from plan `feature_json.default_scopes` or seeded mapping.

Optional: `provisioning_runs` log table for idempotent provision steps.

---

## D. Route structure

### Public web

`/`, `/pricing`, `/features`, `/developers`, `/docs`, `/contact`, `/login`, `/signup`, password reset, email verify.

### Dashboard (`/app/...`)

Dashboard, onboarding checklist, api-keys, connections, webhooks, usage, billing, settings.

### Webhooks

- `POST /webhooks/stripe`  
- `GET|POST /webhooks/meta`  

### OAuth (Embedded Signup)

- `GET /oauth/meta/start`  
- `GET /oauth/meta/callback`  

### API v1

| Method | Path | Notes |
|--------|------|--------|
| POST | `/messages` | messages.send; Idempotency-Key |
| GET | `/messages/{id}` | messages.read |
| GET | `/connections` | connections.read |
| POST | `/connections` | **Onboarding session only** (connections.manage) |
| GET | `/connections/{id}` | includes status + onboarding_url if pending |
| DELETE | `/connections/{id}` | connections.manage |
| GET | `/templates` | templates.read |
| POST | `/media` | messages.send |
| GET/POST/DELETE | `/webhooks` | webhooks.* |
| GET | `/usage` | usage.read |

### Admin (`/admin`)

Monitoring/support only — normal provisioning never requires admin.

### Internal bridge

**Not in MVP.** Add `/internal/v1` later with service token + allowlist if needed.

---

## E. External integrations

| System | Role |
|--------|------|
| **Stripe** | Checkout, subscriptions, portal, webhooks → provision |
| **Meta (dedicated app)** | Embedded Signup, Graph API, platform webhook |
| **Redis** | Queue, rate limits |
| **Email** | Welcome, verify, payment, API ready, failures |

### Service map

`PlanEntitlementService`, `StripeSubscriptionService`, `PartnerProvisioningService`, `ApiKeyService`, `ConnectionService`, `EmbeddedSignupService`, `CredentialVault`, `MetaGraphClient`, `MessageService`, `TemplateService`, `MediaService`, `MetaWebhookProcessor`, `WebhookDeliveryService`, `UsageRecorder`, `IdempotencyService`, `RateLimitService`.

---

## F. Implementation phases

### Phase 1 — Foundation

Laravel 13 + Inertia/React/TS + Tailwind theme; migrations `users`, `partners`, `plans`; auth; public marketing; plans from DB.

### Phase 2 — Stripe & provisioning

Webhooks; `ProvisionPartnerJob`; **auto first API key + show once**; billing UI; emails.

### Phase 3 — API foundation

Keys, scopes, rate limit, idempotency, errors, tests.

### Phase 4 — Connections & Meta ES

Credentials vault; API onboarding session + OAuth; Meta webhook ingest.

### Phase 5 — Messaging API

Text, templates, media.

### Phase 6 — CRM webhooks

Queue, signatures, retries, DLQ.

### Phase 7 — Dashboard & docs

Usage, checklist, public API docs.

### Phase 8 — Hardening

Security, observability, deployment runbook, full test suite.

---

## Appendices

### JSON errors

```json
{
  "success": false,
  "error": { "code": "connection_not_found", "message": "..." },
  "request_id": "req_..."
}
```

### CRM webhook signature

`HMAC-SHA256(secret, timestamp + "." + raw_body)`  
Headers: `X-IQP-Signature`, `X-IQP-Timestamp`, `X-IQP-Request-Id`, `X-IQP-Event-Id`

### Environment (minimal)

```
APP_URL=https://whatsappapi.iqpigeon.com
DB_DATABASE=whatsapp_api_production
STRIPE_* 
META_APP_ID, META_APP_SECRET, META_ES_CONFIG_ID, META_WEBHOOK_VERIFY_TOKEN
QUEUE_CONNECTION=redis
```

---

*Document version: 1.1 — Phase 0 sign-off locked. Laravel 13 / separate repo / dedicated Meta app / no MVP bridge / no billing_mode / API connections = onboarding sessions only.*
