#!/usr/bin/env bash
# Smoke test: ensure the application boots with a clean production build.
# Runs in GitHub Actions CI only, on the CI sqlite database (not the VPS).
set -euo pipefail

DB_FILE="${DB_FILE:-database/database.sqlite}"

echo "==> Preparing smoke-test database ($DB_FILE)"
rm -f "$DB_FILE"
touch "$DB_FILE"

echo "==> Running migrations"
php artisan migrate --force

echo "==> Verifying routes resolve"
php artisan route:list --json > /dev/null

echo "==> Smoke test passed"
