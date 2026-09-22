# Phase 0 sign-off (locked)

**Date:** 2026-09-21  
**Product:** IQPigeon WhatsApp API (`whatsappapi.iqpigeon.com`)

| Decision | Choice |
|----------|--------|
| Repository | **Separate GitHub repo:** `iqpigeon-whatsapp-api` (not under main `iqpigeon/`) |
| Domain | `whatsappapi.iqpigeon.com` (staging: `staging-whatsappapi.iqpigeon.com`) |
| Framework | **Laravel 13**, PHP **8.3+**, Inertia, React, TypeScript, Vite, Tailwind |
| Data store | MySQL 8.x, Redis (queue + rate limits) |
| Meta app | **Dedicated** “IQPigeon WhatsApp API” app — no sharing with main IQPigeon app |
| Database | Separate MySQL; no main-IQPigeon DB access |
| Dashboard auth | Separate Laravel session (domain-scoped cookies) |
| CRM API auth | `iqp_live_*` keys; **hash only** in DB |
| First API key | **Auto-created** in `ProvisionPartnerJob`; shown **once** after payment |
| Stripe | Provision only from **verified webhooks** (idempotent `stripe_events`) |
| WhatsApp onboarding | **Embedded Signup**; customer-owned WABA; **never** attach IQPigeon credit line in v1 |
| Meta messaging billing | **Customer-direct** (architectural default; no `billing_mode` column in v1) |
| Internal bridge to main IQPigeon | **None in MVP** |
| CRM webhooks | Async queue, HMAC signatures, retries + DLQ |
| Usage metrics | IQPigeon infrastructure only — not Meta charges |

## Connection API rule (v1)

`POST /api/v1/connections` **does not** accept WABA IDs or Meta tokens from the CRM.

It creates an **onboarding session** (Embedded Signup). The end customer completes Meta authorization; activation happens in `/oauth/meta/callback`.

## Provisioning flow (includes first API key)

```
Stripe payment confirmed (webhook)
  → ProvisionPartnerJob
  → partner ACTIVE
  → webhook secret
  → first API key (iqp_live_*, plan default scopes, hash stored)
  → redirect / flash: show raw key ONCE
  → dashboard
```

## Principal model

```
IQPigeon     = infrastructure provider (API subscription)
CRM          = API customer
CRM businesses = WhatsApp customers (Meta relationship)
Meta         = WhatsApp service + messaging billing
```

Canonical architecture: [ARCHITECTURE.md](./ARCHITECTURE.md)
