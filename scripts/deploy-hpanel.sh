#!/usr/bin/env bash
#
# Pull latest code and refresh a production Laravel install on Hostinger VPS (SSH).
# Usage:
#   export APP_DIR=/home/USER/domains/whatsappapi.iqpigeon.com/iqpigeon-whatsapp-api
#   bash scripts/deploy-hpanel.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/home/USER/domains/whatsappapi.iqpigeon.com/iqpigeon-whatsapp-api}"
BRANCH="${BRANCH:-master}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER="${COMPOSER:-composer}"
NPM="${NPM:-npm}"

cd "$APP_DIR"

echo "==> Deploy: $APP_DIR (branch $BRANCH)"

if [[ ! -f artisan ]]; then
  echo "ERROR: artisan not found in APP_DIR. Set APP_DIR to the Laravel project root (not public/)." >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "ERROR: .env missing. Copy .env.example to .env and configure production values first." >&2
  exit 1
fi

echo "==> git fetch + pull"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> composer (production)"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

if [[ -f package-lock.json ]]; then
  echo "==> npm ci + build"
  $NPM ci
  $NPM run build
else
  echo "==> npm install + build"
  $NPM install
  $NPM run build
fi

echo "==> artisan migrate"
$PHP_BIN artisan migrate --force

echo "==> artisan caches"
$PHP_BIN artisan storage:link --force 2>/dev/null || true
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache 2>/dev/null || true

echo "==> queue restart signal"
$PHP_BIN artisan queue:restart

if command -v supervisorctl >/dev/null 2>&1; then
  echo "==> supervisor (if configured)"
  sudo supervisorctl restart iqp-api-queue:* 2>/dev/null || true
fi

echo "==> permissions (adjust USER if needed)"
# Uncomment and set your SSH user if deploy runs as that user:
# chown -R USER:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Done. Check: curl -sS -o /dev/null -w '%{http_code}\n' \"\$(grep ^APP_URL= .env | cut -d= -f2-)/up\""
