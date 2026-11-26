#!/usr/bin/env bash
#
# Deploy the latest code on the server. Run from anywhere:
#
#     ./deploy.sh
#
# What it does, in order:
#   1. git pull (fast-forward only, so local changes on the server stop the deploy instead of merging)
#   2. composer install without dev packages
#   3. npm ci + npm run build, if npm is available on the server
#      (otherwise upload public/build yourself after `npm run build` locally)
#   4. maintenance mode on, migrations, caches rebuilt (php artisan optimize), maintenance mode off
#
# Settings (optional, as environment variables):
#   BRANCH=main        branch to deploy
#   PHP=php            PHP binary, e.g. PHP=php8.5 if the host names it that way
#   COMPOSER=composer  Composer command, e.g. COMPOSER="php8.5 composer.phar"
#
# Example: PHP=php8.5 ./deploy.sh

set -euo pipefail

BRANCH="${BRANCH:-main}"
PHP="${PHP:-php}"

cd "$(dirname "$0")"

step() {
    printf '\n\033[1;33m==> %s\033[0m\n' "$1"
}

fail() {
    printf '\n\033[1;31m✗ %s\033[0m\n' "$1" >&2
    exit 1
}

# --- Checks -----------------------------------------------------------------

[ -f .env ] || fail ".env is missing. Copy .env.example to .env and fill it in first."
command -v "$PHP" > /dev/null || fail "PHP binary '$PHP' not found. Try PHP=php8.5 ./deploy.sh"

if [ -n "${COMPOSER:-}" ]; then
    : # use what was given
elif command -v composer > /dev/null; then
    COMPOSER="composer"
elif [ -f composer.phar ]; then
    COMPOSER="$PHP composer.phar"
else
    fail "Composer not found. Install it, put composer.phar here, or set COMPOSER=..."
fi

if grep -qE '^APP_DEBUG=true' .env; then
    printf '\033[1;31m! APP_DEBUG=true in .env. Set it to false on the server.\033[0m\n'
fi

if [ -f public/hot ]; then
    printf '\033[1;31m! public/hot exists, so pages load assets from a Vite dev server. Removing it.\033[0m\n'
    rm -f public/hot
fi

# --- Code -------------------------------------------------------------------

step "Pulling $BRANCH"
before="$(git rev-parse HEAD)"
git pull --ff-only origin "$BRANCH"
after="$(git rev-parse HEAD)"

if [ "$before" = "$after" ]; then
    echo "Already up to date ($(git log -1 --format='%h %s'))."
else
    git log --oneline "$before..$after"
fi

step "Installing PHP packages"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if command -v npm > /dev/null; then
    step "Building the frontend"
    npm ci --no-audit --no-fund
    npm run build
else
    printf '\nnpm not found: skipping the frontend build. If CSS or JS changed, run `npm run build` locally and upload public/build.\n'
fi

[ -f public/build/manifest.json ] || fail "public/build/manifest.json is missing, so the site has no CSS or JS. Build and upload public/build."

# --- App --------------------------------------------------------------------

step "Maintenance mode on"
"$PHP" artisan down --retry=15 || true

# Whatever happens below, don't leave the site in maintenance mode.
trap '"$PHP" artisan up > /dev/null 2>&1 || true' EXIT

step "Running migrations"
"$PHP" artisan migrate --force

step "Rebuilding caches"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

step "Maintenance mode off"
"$PHP" artisan up
trap - EXIT

printf '\n\033[1;32m✓ Deployed %s\033[0m\n' "$(git log -1 --format='%h %s')"
