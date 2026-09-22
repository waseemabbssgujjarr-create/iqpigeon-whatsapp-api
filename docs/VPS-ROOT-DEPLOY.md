# Root VPS deploy — `srv1939416` (IQPigeon WhatsApp API only)

**Separate product** from main IQPigeon CRM. SSH as **root** on Hostinger VPS `srv1939416`.

| Item | Value |
|------|--------|
| App path | `/var/www/iqpigeon-whatsapp-api` |
| Web root | `/var/www/iqpigeon-whatsapp-api/public` |
| Domain | `whatsappapi.iqpigeon.com` |
| Git | `https://github.com/waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git` |
| Branch | `master` |

In **hPanel → Websites → whatsappapi.iqpigeon.com → Document root**, set exactly:

```text
/var/www/iqpigeon-whatsapp-api/public
```

---

## A. First-time install (run once as root)

Paste block by block on `root@srv1939416:~#`.

### A1. Packages

```bash
apt update && apt upgrade -y
apt install -y git curl unzip nginx redis-server supervisor \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

Create MySQL database/user in **hPanel → Databases** (or `mysql` CLI). Redis: `systemctl enable --now redis-server`.

### A2. Clone project

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git iqpigeon-whatsapp-api
cd /var/www/iqpigeon-whatsapp-api
git checkout master
```

If the repo is **private**, use a [deploy key](#private-github-repo) before `git clone`.

### A3. `.env` and install

```bash
cd /var/www/iqpigeon-whatsapp-api
cp .env.example .env
nano .env
```

Set at minimum:

```ini
APP_NAME="IQPigeon WhatsApp API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://whatsappapi.iqpigeon.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=YOUR_DB_NAME
DB_USERNAME=YOUR_DB_USER
DB_PASSWORD=YOUR_DB_PASSWORD

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_SECURE_COOKIE=true

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_ID=

META_APP_ID=
META_APP_SECRET=
META_CONFIG_ID=
META_WEBHOOK_VERIFY_TOKEN=
META_GRAPH_VERSION=v21.0

MAIL_MAILER=smtp
# ... mail settings ...

PARTNER_WEBHOOK_TIMEOUT=15
PARTNER_WEBHOOK_MAX_ATTEMPTS=5
PARTNER_WEBHOOK_BACKOFF_SECONDS=30,60,120,300
```

```bash
php artisan key:generate
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ApiScopeSeeder --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

### A4. Queue worker (Supervisor)

```bash
cat > /etc/supervisor/conf.d/iqp-api-queue.conf <<'EOF'
[program:iqp-api-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/iqpigeon-whatsapp-api/artisan queue:work redis --sleep=3 --tries=5 --max-time=3600
directory=/var/www/iqpigeon-whatsapp-api
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/iqp-api-queue.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl start iqp-api-queue:*
```

### A5. Scheduler (root crontab or hPanel cron)

```bash
(crontab -l 2>/dev/null; echo '* * * * * cd /var/www/iqpigeon-whatsapp-api && /usr/bin/php artisan schedule:run >> /dev/null 2>&1') | crontab -
```

Enable SSL in hPanel for `whatsappapi.iqpigeon.com`.

### A6. Smoke test

```bash
curl -sS -o /dev/null -w "%{http_code}\n" https://whatsappapi.iqpigeon.com/up
```

Expect `200`.

---

## B. Every update: git pull (as root)

### B1. One command script

```bash
bash /var/www/iqpigeon-whatsapp-api/scripts/deploy-vps-root.sh
```

(After the repo contains that script; or use B2 below.)

### B2. Copy-paste pull deploy

```bash
cd /var/www/iqpigeon-whatsapp-api

git fetch origin master
git checkout master
git pull --ff-only origin master

composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

supervisorctl restart iqp-api-queue:*

chown -R www-data:www-data storage bootstrap/cache
```

---

## Private GitHub repo

```bash
ssh-keygen -t ed25519 -C "srv1939416-whatsapp-api" -f /root/.ssh/id_ed25519_iqp_whatsapp_api -N ""
cat /root/.ssh/id_ed25519_iqp_whatsapp_api.pub
```

Add the printed key in GitHub → **iqpigeon-whatsapp-api** → Settings → Deploy keys (read-only).

```bash
cat >> /root/.ssh/config <<'EOF'

Host github-iqp-whatsapp-api
  HostName github.com
  User git
  IdentityFile /root/.ssh/id_ed25519_iqp_whatsapp_api
EOF
chmod 600 /root/.ssh/config

cd /var/www/iqpigeon-whatsapp-api
git remote set-url origin git@github-iqp-whatsapp-api:waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git
git pull origin master
```

---

## Rollback

```bash
cd /var/www/iqpigeon-whatsapp-api
git log --oneline -5
git checkout COMMIT_SHA_HERE
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
supervisorctl restart iqp-api-queue:*
```

---

## Not on this server

Main IQPigeon CRM lives in a **different** repo/path. Only `/var/www/iqpigeon-whatsapp-api` is this WhatsApp API product.
