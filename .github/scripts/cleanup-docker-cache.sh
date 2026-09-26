#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-$HOME/apps/internara}"
CACHE_MAX_AGE="${CACHE_MAX_AGE:-168h}"
LOCK_FILE="${TMPDIR:-/tmp}/internara-deploy.lock"

# Do not prune while a release or hotfix is building/recreating containers.
exec 200>"$LOCK_FILE"
flock -w 600 200 || {
    echo "==> Failed to acquire cleanup lock after 600s" >&2
    exit 1
}

cd "$DEPLOY_DIR"

echo "==> Docker disk usage before cleanup"
docker system df

echo "==> Removing build cache older than ${CACHE_MAX_AGE}"
docker builder prune -f --filter "until=${CACHE_MAX_AGE}"

echo "==> Removing unused images older than ${CACHE_MAX_AGE}"
docker image prune -f --filter "until=${CACHE_MAX_AGE}"

echo "==> Docker disk usage after cleanup"
docker system df
