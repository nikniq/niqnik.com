#!/usr/bin/env bash
#
# Production deploy for nikniq.com. Run ON the server, from the app root:
#
#   ./deploy.sh                 # pull + composer (if lockfile moved) + migrate + caches
#   ./deploy.sh GamesSeeder     # ...and re-run one specific seeder class
#   ./deploy.sh --seed          # ...and re-run EVERY seeder (confirms first)
#
# One command for the whole sequence so pull and migrate cannot drift apart.
# Its local counterpart is cx.sh, which drives this script over SSH.
#
# Default seeding is a single class, not a blanket db:seed: products and
# games are edited by admins in the DB, and DatabaseSeeder creates the demo
# user with User::factory()->create(), which fails on a second run because
# the email already exists. --seed runs it anyway after you confirm.

set -euo pipefail
cd "$(dirname "$0")"

echo "→ git pull"
LOCK_BEFORE=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)
git pull --ff-only
LOCK_AFTER=$(git rev-parse HEAD:composer.lock 2>/dev/null || echo none)

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -d vendor ]; then
    if command -v composer >/dev/null 2>&1; then
        echo "→ composer install (lockfile changed or vendor missing)"
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        echo "⚠ composer.lock changed but composer is not on PATH."
        echo "  Install dependencies manually before trusting this deploy."
    fi
fi

echo "→ migrate"
php artisan migrate --force

if [ "${1:-}" = "--seed" ]; then
    echo "⚠ This re-runs EVERY seeder. DatabaseSeeder will fail if the demo user already exists."
    read -r -p "Type 'yes' to continue: " confirm
    if [ "$confirm" = "yes" ]; then
        echo "→ db:seed (all)"
        php artisan db:seed --force
    else
        echo "→ skipped seeding"
    fi
elif [ "${1:-}" != "" ]; then
    echo "→ db:seed --class=$1"
    php artisan db:seed --class="$1" --force
fi

echo "→ storage link"
[ -e public_html/storage ] || php artisan storage:link

echo "→ cache config/routes/views"
php artisan optimize

echo "→ verify"
php artisan migrate:status | grep -q Pending && echo "⚠ migrations still pending" || echo "  migrations: up to date"
php artisan media:check-files 2>/dev/null || echo "  (media:check-files skipped)"

echo "✓ deployed $(git log --oneline -1)"
