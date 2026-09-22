# Production deployment (Ubuntu + Nginx)

Target: **https://whatsappapi.iqpigeon.com** — independent from main IQPigeon CRM.

## Stack

| Component | Version |
|-----------|---------|
| Ubuntu | 22.04 LTS or 24.04 LTS |
| PHP-FPM | 8.3+ |
| Nginx | stable |
| MySQL | 8.0+ |
| Redis | 7+ |
| Node | 20+ (build only) |
| Supervisor or systemd | queue workers |

## 1. Server bootstrap

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server supervisor \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 2. Application deploy

```bash
sudo mkdir -p /var/www/iqpigeon-whatsapp-api
sudo chown $USER:www-data /var/www/iqpigeon-whatsapp-api
git clone git@github.com:your-org/iqpigeon-whatsapp-api.git /var/www/iqpigeon-whatsapp-api
cd /var/www/iqpigeon-whatsapp-api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Configure `.env`:

- `APP_URL=https://whatsappapi.iqpigeon.com`
- `APP_DEBUG=false`
- `DB_*`, `REDIS_*`, `QUEUE_CONNECTION=redis`
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `META_APP_ID`, `META_APP_SECRET`, `META_CONFIG_ID`, `META_WEBHOOK_VERIFY_TOKEN`, `META_GRAPH_VERSION`
- Mail for verification / password reset
- Partner webhook delivery (optional overrides; defaults below):
  - `PARTNER_WEBHOOK_TIMEOUT=15`
  - `PARTNER_WEBHOOK_MAX_ATTEMPTS=5`
  - `PARTNER_WEBHOOK_BACKOFF_SECONDS=30,60,120,300`

```bash
npm ci && npm run build
php artisan migrate --force

# Production: seed catalog only (no demo user). Do NOT run full DatabaseSeeder in production.
php artisan db:seed --class=Database\\Seeders\\ApiScopeSeeder --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 3. Nginx + SSL

```nginx
server {
    listen 443 ssl http2;
    server_name whatsappapi.iqpigeon.com;
    root /var/www/iqpigeon-whatsapp-api/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/whatsappapi.iqpigeon.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/whatsappapi.iqpigeon.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Obtain certificates: `sudo certbot --nginx -d whatsappapi.iqpigeon.com`

## 4. Queue workers (Supervisor)

```ini
[program:iqp-api-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/iqpigeon-whatsapp-api/artisan queue:work redis --sleep=3 --tries=5 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/iqp-api-queue.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start iqp-api-queue:*
```

Alternative: systemd unit running the same `queue:work` command.

## 5. Scheduler

```bash
sudo crontab -u www-data -e
```

```
* * * * * cd /var/www/iqpigeon-whatsapp-api && php artisan schedule:run >> /dev/null 2>&1
```

## 6. External webhooks

| Provider | URL |
|----------|-----|
| Stripe | `POST https://whatsappapi.iqpigeon.com/webhooks/stripe` |
| Meta | `GET|POST https://whatsappapi.iqpigeon.com/webhooks/meta` |

Configure **only** `/webhooks/stripe` in the Stripe Dashboard (`STRIPE_WEBHOOK_SECRET`). Cashier’s default `/stripe/webhook` route is **disabled** in this app.

Stripe checkout success URLs are **not** authoritative — only verified Stripe webhooks activate subscriptions.

### Partner outbound webhooks (to your URL)

Events fan out via `DeliverPartnerWebhookJob`; each endpoint gets one `webhook_deliveries` row per `event_id`. Delivery runs in `DeliverPartnerWebhookAttemptJob` with **bounded exponential backoff** (configurable via `config/webhooks.php` / env).

| Setting | Default |
|---------|---------|
| HTTP timeout | 15s (`PARTNER_WEBHOOK_TIMEOUT`) |
| Max attempts | 5 (`PARTNER_WEBHOOK_MAX_ATTEMPTS`) |
| Backoff (seconds between queue retries) | 30, 60, 120, 300 (`PARTNER_WEBHOOK_BACKOFF_SECONDS`) |

**Retried:** connection errors, timeouts, HTTP 408, 429, and 5xx. **Not retried:** other 4xx (marked `failed` in delivery history). After max attempts on retryable errors, status is `dead`. Each attempt updates `attempt_count`, `response_status`, `error_message`, and `next_retry_at`. Replayed fan-out jobs skip endpoints already `delivered` for that event.

Requests use SSRF validation, HMAC (`X-IQPigeon-Signature`), and a timestamp header. Disabled endpoints are not enqueued.

Workers must run continuously (`queue:work redis`) so retries are processed.

## 6b. Trusted proxies & HTTPS

The app trusts `X-Forwarded-*` from the reverse proxy (`bootstrap/app.php`). Terminate TLS at Nginx and set `SESSION_SECURE_COOKIE=true` in production `.env`.

## 7. Rollback

```bash
cd /var/www/iqpigeon-whatsapp-api
git fetch && git checkout <previous-tag>
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
sudo supervisorctl restart iqp-api-queue:*
```

## 8. Health checks

- `GET https://whatsappapi.iqpigeon.com/up`
- Monitor queue depth, failed jobs, Stripe/Meta webhook 4xx/5xx rates

## Billing note

Stripe bills **IQPigeon platform subscription** only. Meta WhatsApp messaging charges remain between Meta and each connected business.
