#!/usr/bin/env bash
#
# First-time clone + install (run as root on srv1939416). Edit .env before migrate.
#
#   bash bootstrap-vps-root.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/iqpigeon-whatsapp-api}"
REPO="${REPO:-https://github.com/waseemabbssgujjarr-create/iqpigeon-whatsapp-api.git}"
BRANCH="${BRANCH:-master}"
WEB_USER="${WEB_USER:-www-data}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root (root@srv1939416)." >&2
  exit 1
fi

if [[ -d "$APP_DIR/.git" ]]; then
  echo "Already cloned at $APP_DIR — use scripts/deploy-vps-root.sh for updates." >&2
  exit 1
fi

mkdir -p "$(dirname "$APP_DIR")"
git clone "$REPO" "$APP_DIR"
cd "$APP_DIR"
git checkout "$BRANCH"

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo ""
  echo ">>> Edit .env now (DB, Redis, Stripe, Meta, APP_URL), then run:"
  echo "    cd $APP_DIR"
  echo "    php artisan key:generate"
  echo "    composer install --no-dev --optimize-autoloader"
  echo "    npm ci && npm run build"
  echo "    php artisan migrate --force"
  echo "    php artisan db:seed --class=Database\\\\Seeders\\\\ApiScopeSeeder --force"
  echo "    php artisan db:seed --class=Database\\\\Seeders\\\\PlanSeeder --force"
  echo "    bash scripts/deploy-vps-root.sh"
  echo ""
  exit 0
fi

bash "$APP_DIR/scripts/deploy-vps-root.sh"
