# Production preflight — GO-LIVE checklist

Last verified against codebase with:

```powershell
.\tools\php.ps1 artisan migrate:fresh --seed --force
.\tools\php.ps1 artisan test    # 84 passed
npm run build
```

Live Stripe/Meta behavior requires real credentials in production; not verified without them.

---

## READY IN CODE

| Area | Status |
|------|--------|
| Migrations | Reproducible from empty DB; FKs + indexes on partner-scoped tables |
| API auth | Bearer API keys, hashed storage, expiry/revoke |
| Scopes | Dot notation; enforced per route |
| Tenant isolation | Partner-scoped queries |
| Rate limits | Plan `rate_limit_per_minute` → 429 |
| Idempotency | Required on mutating POSTs; TTL 24h |
| Request IDs | `AssignRequestId` middleware |
| Stripe webhooks | `/webhooks/stripe`, signature verify, `stripe_events` idempotency, Checkout `STRIPE_PRICE_ID`, provisioning job |
| Checkout return | Does **not** activate account (tested) |
| Meta webhooks | Signature verify, persist, queue `ProcessMetaWebhookJob` |
| Meta tokens | Encrypted model casts; hidden from API resources |
| OAuth | State = onboarding token hash lookup |
| Partner webhooks | SSRF validator, HTTPS (HTTP only local/testing), HMAC + timestamp headers, bounded retries (408/429/5xx/connection/timeout), delivery history (`pending` → `delivering` → `delivered` / `failed` / `dead`) |
| Secrets in repo | None (placeholders + test fakes only) |
| CI | PHP 8.3, MySQL, Redis, migrate/seed, test, build |
| Cashier duplicate route | Disabled via `Cashier::ignoreRoutes()` in `AppServiceProvider::register()` (only `/webhooks/stripe` remains) |
| Trusted proxies | Enabled for Nginx/LB |

---

## REQUIRES PRODUCTION ENVIRONMENT

| Item | Action |
|------|--------|
| `.env` | Set production values (see below) |
| MySQL | Dedicated database user, least privilege |
| Redis | Running; `QUEUE_CONNECTION=redis` |
| Queue workers | Supervisor/systemd running `queue:work redis` |
| Scheduler | Cron `* * * * * php artisan schedule:run` (no app schedules registered yet — optional) |
| SSL | Cert for `whatsappapi.iqpigeon.com` |
| Stripe | Live keys, webhook endpoint, Price IDs on plans |
| Meta | App ID/secret, config ID, webhook verify token, Graph version |
| Mail | SMTP/API for verification + password reset |
| Seed policy | Run **ApiScopeSeeder + PlanSeeder only**; skip demo `test@example.com` user |
| Monitoring | Failed jobs table, queue depth, `/up` health |
| Partner webhook retries | Env: `PARTNER_WEBHOOK_TIMEOUT`, `PARTNER_WEBHOOK_MAX_ATTEMPTS`, `PARTNER_WEBHOOK_BACKOFF_SECONDS`; monitor `webhook_deliveries` and failed queue jobs |

---

## Exact production `.env` variables

```ini
APP_NAME="IQPigeon WhatsApp API"
APP_ENV=production
APP_KEY=base64:...          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://whatsappapi.iqpigeon.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iqp_whatsapp_api
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_SAME_SITE=lax

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@iqpigeon.com
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_ID=price_live_...
CASHIER_CURRENCY=usd

META_APP_ID=
META_APP_SECRET=
META_CONFIG_ID=
META_GRAPH_VERSION=v21.0
META_WEBHOOK_VERIFY_TOKEN=

API_IDEMPOTENCY_TTL_HOURS=24

PARTNER_WEBHOOK_TIMEOUT=15
PARTNER_WEBHOOK_MAX_ATTEMPTS=5
PARTNER_WEBHOOK_BACKOFF_SECONDS=30,60,120,300
```

---

## Nginx (minimum)

- `root` → `public/`
- `try_files` → `index.php`
- PHP 8.3-FPM socket
- TLS 1.2+
- `client_max_body_size` ≥ 20m (media uploads)

---

## PHP extensions

`mbstring`, `pdo_mysql`, `redis`, `intl`, `zip`, `bcmath`, `curl`, `openssl`, `fileinfo`

---

## Commands (deploy)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ApiScopeSeeder --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
php artisan queue:restart
```

**Worker:** `php artisan queue:work redis --sleep=3 --tries=5 --max-time=3600` (partner webhook attempt jobs use up to 5 tries with per-job backoff)

**Scheduler:** `* * * * * cd /var/www/iqpigeon-whatsapp-api && php artisan schedule:run >> /dev/null 2>&1`

---

## Webhook URLs (configure externally)

- Stripe: `https://whatsappapi.iqpigeon.com/webhooks/stripe`
- Meta: `https://whatsappapi.iqpigeon.com/webhooks/meta`
