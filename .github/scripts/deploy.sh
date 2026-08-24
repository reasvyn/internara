#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/home/andreas/apps/internara}"
HEALTH_URL="${HEALTH_URL:-https://internara.web.id}" # product demo; override via env for other domains
BUILD_CACHE_LIMIT="${BUILD_CACHE_LIMIT:-2g}"
VERSION_TAG="${VERSION_TAG:-}"

cd "$DEPLOY_DIR"

# Always use version tag for reproducible deploys.
# If VERSION_TAG is set (from workflow), ensure GIT_URL points to that tag.
if [ -n "$VERSION_TAG" ]; then
  export GIT_URL="https://github.com/reasvyn/internara.git#${VERSION_TAG}"
  echo "Using version tag $VERSION_TAG (GIT_URL=$GIT_URL)"
else
  # Fallback: derive version from composer.json if no tag passed (e.g. manual SSH)
  if [ -f composer.json ]; then
    RAW_VERSION=$(jq -r .version composer.json 2>/dev/null || echo "")
    if [ -n "$RAW_VERSION" ] && [ "$RAW_VERSION" != "null" ]; then
      VERSION_TAG="v${RAW_VERSION}"
      export GIT_URL="https://github.com/reasvyn/internara.git#${VERSION_TAG}"
      echo "Derived version tag $VERSION_TAG from composer.json (GIT_URL=$GIT_URL)"
    fi
  fi
fi

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
