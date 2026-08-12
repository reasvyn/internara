#!/bin/sh
# Bring up the stack and run pending migrations.
# Usage: scripts/docker-up.sh
set -e

docker compose up -d

echo "[docker-up] waiting for app container..."
i=0
until docker compose exec -T app php -r 'echo "ok";' >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -ge 30 ] && echo "[docker-up] app not ready, aborting" >&2 && exit 1
    sleep 2
done

echo "[docker-up] running migrations..."
docker compose exec -T app php artisan migrate --force

echo "[docker-up] done"
