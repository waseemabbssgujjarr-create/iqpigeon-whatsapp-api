#!/usr/bin/env bash
#
# Git pull + production refresh for root VPS (e.g. root@srv1939416).
# IQPigeon WhatsApp API — /var/www/iqpigeon-whatsapp-api
#
#   bash /var/www/iqpigeon-whatsapp-api/scripts/deploy-vps-root.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/iqpigeon-whatsapp-api}"
BRANCH="${BRANCH:-master}"
PHP_BIN="${PHP_BIN:-php}"
WEB_USER="${WEB_USER:-www-data}"

cd "$APP_DIR"

if [[ ! -f artisan ]]; then
  echo "ERROR: Run from Laravel root. Expected: $APP_DIR" >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "ERROR: Missing .env — complete first-time setup in docs/VPS-ROOT-DEPLOY.md" >&2
  exit 1
fi

echo "==> [$HOSTNAME] deploy $APP_DIR ($BRANCH)"

git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

composer install --no-dev --optimize-autoloader --no-interaction

if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

$PHP_BIN artisan migrate --force
$PHP_BIN artisan storage:link --force 2>/dev/null || true
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache 2>/dev/null || true
$PHP_BIN artisan queue:restart

if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl restart iqp-api-queue:* || true
fi

chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

APP_URL="$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2- | tr -d '"'"'\"'"'"')"
if [[ -n "$APP_URL" ]]; then
  echo "==> health: ${APP_URL}/up"
  curl -sS -o /dev/null -w "HTTP %{http_code}\n" "${APP_URL}/up" || true
fi

echo "==> Done."
