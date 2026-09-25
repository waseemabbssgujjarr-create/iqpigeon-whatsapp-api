# WhatsApp Business App coexistence onboarding

IQPigeon supports two Meta Embedded Signup paths:

| Path | Dashboard action | Meta configuration |
|------|------------------|--------------------|
| **Standard Cloud API** | “Set up new Cloud API number” | `META_CONFIG_ID` (redirect OAuth) |
| **Coexistence (Business app)** | “Connect existing Business app number” | Same v4 `META_ES_CONFIG_ID` (JS SDK; optional `META_ES_CONFIG_ID_COEXISTENCE` override) |

## Meta “Ineligible” in the phone picker

When Meta marks a number **Ineligible** in Embedded Signup (for example “The Sicilian Restaurant”), that label is rendered **inside Meta’s UI**. IQPigeon does not maintain a blocklist of BM phone numbers.

Common Meta-side reasons:

- Number is not eligible for **WhatsApp Business Platform coexistence** in that region or account state
- Display name / business verification not approved
- Number already linked to another WABA or Cloud API setup Meta considers incompatible
- Consumer WhatsApp vs Business app mismatch
- Policy or quality restrictions on the WABA

**Action:** Use **Connect existing Business app number** (coexistence config) for eligible lines, or **standard Cloud API** for a new/API number. If Meta keeps showing Ineligible, resolve in Meta Business Manager or pick “Enter a new phone number”.

## Embedded Signup v4 (Meta-confirmed)

For **Embedded Signup v4**, Meta requires:

- **`version: "v4"`** in `extras`
- **Do not** pass `featureType` or `sessionInfoVersion` (legacy v2/v3 coexistence keys; they break v4 UI)
- Coexistence is **automatic** on v4 — one `config_id` can serve API-only and Business App onboarding
- Use **`features: ["api_access_only"]`** only when you want to narrow to Cloud API / new-number setup

| Product | Coexistence / both paths | API-only (new number) |
|---------|--------------------------|------------------------|
| IQPigeon CRM (`FB.login` + onboard URL) | `{"version":"v4"}` | same (user picks in Meta UI) |
| WhatsApp API SaaS — Business app button | `EMBEDDED_SIGNUP_V4_EXTRAS` in JS SDK | n/a |
| WhatsApp API SaaS — standard redirect OAuth | n/a | `{"version":"v4","features":["api_access_only"]}` |

Meta deprecates Embedded Signup v2/v3 in **2026** — keep v4 extras only.

## Webhook fields (coexistence)

Subscribe in Meta App → WhatsApp → Configuration: `messages`, `history`, `smb_app_state_sync`, and `smb_message_echoes`.

## After signup

Connections are **not** marked active until:

1. Token exchange and hydration (WABA + `phone_number_id`)
2. `WhatsappConnectionValidator` passes (scopes, app ID, phone metadata)
3. Cloud API registration: **PIN** (standard) or **coexistence auto-register** when verified snapshot + `onboarding_source=coexistence`

## Diagnostics

```bash
php artisan whatsapp:inspect-meta {connection-uuid}
php artisan whatsapp:test-connection {connection-uuid}
php artisan message:inspect {message-uuid}
```

Live send (explicit):

```bash
php artisan whatsapp:test-send {uuid} +923001234567 hello_world --language=en_US --confirm
```
