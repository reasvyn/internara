#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/home/andreas/apps/internara}"
HEALTH_URL="${HEALTH_URL:-https://internara.web.id}"
BUILD_CACHE_LIMIT="${BUILD_CACHE_LIMIT:-2g}"

cd "$DEPLOY_DIR"

docker compose up -d --build --remove-orphans

docker image prune -f >/dev/null 2>&1 || true
docker builder prune -f --keep-storage "$BUILD_CACHE_LIMIT" >/dev/null 2>&1 || true

for i in {1..30}; do
    if curl -fsS -o /dev/null "$HEALTH_URL"; then
        echo "deploy ok: $HEALTH_URL reachable"
        exit 0
    fi
    sleep 2
done

echo "deploy failed: $HEALTH_URL not reachable after 60s" >&2
docker compose ps
exit 1
