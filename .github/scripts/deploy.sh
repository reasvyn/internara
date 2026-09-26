#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-$HOME/apps/internara}"
HEALTH_URL="${HEALTH_URL:-https://internara.web.id}" # product demo; override via env for other domains
BUILD_CACHE_LIMIT="${BUILD_CACHE_LIMIT:-2g}"
VERSION_TAG="${VERSION_TAG:-}"
ROLLBACK_ON_FAILURE="${ROLLBACK_ON_FAILURE:-true}"

cd "$DEPLOY_DIR"

# Serialize deployments on this host to prevent Docker container recreation conflicts
LOCK_FILE="${TMPDIR:-/tmp}/internara-deploy.lock"
exec 200>"$LOCK_FILE"
flock -w 600 200 || { echo "==> Failed to acquire deploy lock after 600s" >&2; exit 1; }

# Always use version tag for reproducible deploys.
# If VERSION_TAG is set (from workflow), ensure GIT_URL points to that tag.
if [ -n "$VERSION_TAG" ]; then
  export GIT_URL="https://github.com/reasvyn/internara.git#${VERSION_TAG}"
  echo "==> Using version tag $VERSION_TAG (GIT_URL=$GIT_URL)"
else
  # Fallback: derive version from composer.json if no tag passed (e.g. manual SSH)
  if [ -f composer.json ]; then
    RAW_VERSION=$(jq -r .version composer.json 2>/dev/null || echo "")
    if [ -n "$RAW_VERSION" ] && [ "$RAW_VERSION" != "null" ]; then
      VERSION_TAG="v${RAW_VERSION}"
      export GIT_URL="https://github.com/reasvyn/internara.git#${VERSION_TAG}"
      echo "==> Derived version tag $VERSION_TAG from composer.json (GIT_URL=$GIT_URL)"
    fi
  fi
fi

# Store current version for potential rollback
PREVIOUS_REVISION=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
echo "==> Current revision: $PREVIOUS_REVISION"

NO_CACHE_FLAG=""
if [ "${NO_CACHE:-false}" = "true" ]; then
  NO_CACHE_FLAG="--no-cache"
fi

echo "==> Building Docker images"
# Keep the VPS build cache warm; release images are already selected through GIT_URL.
# Set FORCE_PULL=true only when base image refresh is intentional.
PULL_FLAG=""
if [ "${FORCE_PULL:-false}" = "true" ]; then
    PULL_FLAG="--pull"
fi
docker compose build $PULL_FLAG $NO_CACHE_FLAG

echo "==> Starting containers"
# Compose recreates only services whose image/config changed; the database stays up.
docker compose up -d --remove-orphans

echo "==> Cleaning up dangling images"
docker image prune -f >/dev/null 2>&1 || true

echo "==> Waiting for health check (max 60s)"
HEALTH_CHECK_PASSED=false
HEALTH_TMP="${TMPDIR:-/tmp}/internara-health-body"
for i in {1..30}; do
    HTTP_CODE=$(curl -sSL -o "$HEALTH_TMP" -w "%{http_code}" -m 10 "$HEALTH_URL" 2>/dev/null || echo "000")
    if { [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; } && ! grep -q "Core system metadata (composer.json) is missing" "$HEALTH_TMP"; then
        echo "==> Deploy OK: $HEALTH_URL reachable (HTTP $HTTP_CODE, healthy body)"
        HEALTH_CHECK_PASSED=true
        break
    fi
    sleep 2
done

if [ "$HEALTH_CHECK_PASSED" = false ]; then
    echo "==> Deploy FAILED: $HEALTH_URL not healthy after 60s (http=${HTTP_CODE})" >&2

    if [ "$ROLLBACK_ON_FAILURE" = true ] && [ "$PREVIOUS_REVISION" != "unknown" ]; then
        echo "==> Initiating automatic rollback to $PREVIOUS_REVISION"
        git checkout --quiet "$PREVIOUS_REVISION"
        git reset --hard --quiet "$PREVIOUS_REVISION"
        docker compose build --no-cache --pull
        docker compose up -d --remove-orphans --force-recreate
        echo "==> Rollback completed"
    else
        echo "==> Automatic rollback disabled or no previous revision available"
        docker compose ps
    fi

    exit 1
fi

echo "==> Deploy completed successfully"
