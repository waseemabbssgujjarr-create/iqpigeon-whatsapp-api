# Hostinger hPanel VPS — clone, pull, deploy

Target: **https://whatsappapi.iqpigeon.com** (Laravel app root separate from `public/`).

Use **SSH** from hPanel → **Advanced → SSH Access** (enable and note host, port, username).

Replace placeholders:

| Placeholder | Example |
|-------------|---------|
| `SSH_USER` | `u123456789` |
| `DOMAIN` | `whatsappapi.iqpigeon.com` |
| `APP_DIR` | `/home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api` |

Repo: `https://github.com/waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git`

---

## One-time setup (first deploy)

SSH in:

```bash
ssh SSH_USER@YOUR_VPS_IP -p 22
```

### 1. PHP 8.3+, Composer, Node 20+

On Ubuntu VPS (Hostinger KVM), as root or with sudo:

```bash
sudo apt update
sudo apt install -y git curl unzip
# PHP: use Hostinger hPanel PHP selector for the domain, or:
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
# Node 20 (example via NodeSource — or install nvm as SSH_USER)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

MySQL: create DB + user in **hPanel → Databases**. Redis: install on VPS or use Hostinger add-on if available; set `QUEUE_CONNECTION=redis` in `.env`.

### 2. Clone app (outside `public_html`)

```bash
mkdir -p ~/domains/DOMAIN
cd ~/domains/DOMAIN

git clone https://github.com/waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git iqpigeon-whatsapp-api
cd iqpigeon-whatsapp-api
git checkout master
```

### 3. Environment

```bash
cp .env.example .env
nano .env
```

Minimum production values:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://whatsappapi.iqpigeon.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

SESSION_SECURE_COOKIE=true
```

Then:

```bash
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ApiScopeSeeder --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Point the domain to Laravel `public/`

In **hPanel → Websites → DOMAIN → Document root**, set to:

```text
/home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api/public
```

(Not the project root — only `public/`.)

Enable SSL (Let’s Encrypt) in hPanel for `DOMAIN`.

### 5. Queue worker (required for webhooks & retries)

**Option A — Supervisor (recommended on VPS):**

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/iqp-api-queue.conf
```

```ini
[program:iqp-api-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api/artisan queue:work redis --sleep=3 --tries=5 --max-time=3600
directory=/home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api
autostart=true
autorestart=true
user=SSH_USER
numprocs=1
redirect_stderr=true
stdout_logfile=/home/SSH_USER/logs/iqp-api-queue.log
```

```bash
mkdir -p ~/logs
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start iqp-api-queue:*
```

**Option B — hPanel Cron (fallback, runs worker every minute — less ideal):**

```cron
* * * * * cd /home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api && php artisan queue:work redis --stop-when-empty --max-time=55 >> /home/SSH_USER/logs/queue-cron.log 2>&1
```

### 6. Scheduler cron (hPanel → Cron Jobs)

```cron
* * * * * cd /home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## Every release: git pull on VPS

### Quick copy-paste (manual)

```bash
export APP_DIR=/home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api
cd "$APP_DIR"

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

# If using Supervisor:
sudo supervisorctl restart iqp-api-queue:*
```

### Script (same repo)

After pull once, on the server:

```bash
export APP_DIR=/home/SSH_USER/domains/DOMAIN/iqpigeon-whatsapp-api
bash "$APP_DIR/scripts/deploy-hpanel.sh"
```

Make executable once:

```bash
chmod +x "$APP_DIR/scripts/deploy-hpanel.sh"
```

---

## Private repo: HTTPS token or deploy key

If the GitHub repo is **private**, on the VPS either:

**Deploy key (recommended):**

```bash
ssh-keygen -t ed25519 -C "hpanel-vps-whatsapp-api" -f ~/.ssh/id_ed25519_github_whatsapp -N ""
cat ~/.ssh/id_ed25519_github_whatsapp.pub
```

Add the public key in GitHub → repo → **Settings → Deploy keys** (read-only). Then:

```bash
nano ~/.ssh/config
```

```
Host github.com-whatsapp-api
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_ed25519_github_whatsapp
```

```bash
cd ~/domains/DOMAIN/iqpigeon-whatsapp-api
git remote set-url origin git@github.com-whatsapp-api:waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git
git pull
```

**Or** HTTPS with a fine-grained PAT (store in credential helper; do not commit).

---

## Rollback

```bash
cd "$APP_DIR"
git log --oneline -5
git checkout <commit-sha>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
sudo supervisorctl restart iqp-api-queue:*
```

---

## Verify

```bash
curl -sS -o /dev/null -w "%{http_code}\n" https://whatsappapi.iqpigeon.com/up
```

Expect `200`.

See also [DEPLOYMENT.md](./DEPLOYMENT.md) and [PRODUCTION-PREFLIGHT.md](./PRODUCTION-PREFLIGHT.md).
