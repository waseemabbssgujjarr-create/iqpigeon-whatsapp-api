# IQPigeon WhatsApp API

Separate SaaS product for CRM companies — WhatsApp Cloud API infrastructure with Embedded Signup, Partner REST API, webhooks, and Stripe platform billing.

**Not** part of the main IQPigeon CRM repository.

## Requirements

- PHP **8.3+** (project-local toolchain under `.tools/` on Windows)
- Composer 2.x
- Node.js 20+ (22 recommended for CI)
- MySQL 8 or SQLite (local)
- Redis (production / optional local via Docker)

## Quick start (Windows)

```powershell
git clone <repo-url> iqpigeon-whatsapp-api
cd iqpigeon-whatsapp-api
.\scripts\setup.ps1
.\scripts\dev.ps1
```

Visit http://127.0.0.1:8000

## Toolchain (without changing system PHP)

```powershell
.\tools\php.ps1 -v          # PHP 8.3.33 from .tools/php83
.\tools\composer.ps1 install
.\scripts\test.ps1
npm run build
```

## Docker (MySQL + Redis)

```powershell
docker compose up -d
# Set DB_CONNECTION=mysql, DB_PORT=3307, REDIS_PORT=6380 in .env
```

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Phase 0 sign-off](docs/PHASE0-SIGNOFF.md)
- [Deployment](docs/DEPLOYMENT.md)

## API

- Base URL: `/api/v1`
- Auth: `Authorization: Bearer iqp_live_...`
- Scopes: dot notation (`messages.send`, `connections.write`, …)

## Billing

Stripe charges the **CRM company** for the IQPigeon API subscription. **Meta WhatsApp messaging fees are separate** and billed by Meta to each connected business.

## External credentials

Configure in `.env` (never commit):

- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `META_APP_ID`, `META_APP_SECRET`, `META_CONFIG_ID`, `META_WEBHOOK_VERIFY_TOKEN`

Tests use fakes/mocks where possible.
