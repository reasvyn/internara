#!/usr/bin/env bash
set -euo pipefail

# Deploy from this checkout: the repo root is derived from the script location,
# so the same command works from any clone path (no hardcoded deploy dir).
DEPLOY_DIR="${DEPLOY_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
HEALTH_URL="${HEALTH_URL:-https://internara.web.id}" # product demo; override via env for other domains
BUILD_CACHE_LIMIT="${BUILD_CACHE_LIMIT:-2g}"
VERSION_TAG="${VERSION_TAG:-}"
ROLLBACK_ON_FAILURE="${ROLLBACK_ON_FAILURE:-true}"

cd "$DEPLOY_DIR"

# Serialize deployments on this host to prevent Docker container recreation conflicts
LOCK_FILE="${TMPDIR:-/tmp}/internara-deploy.lock"
exec 200>"$LOCK_FILE"
flock -w 1800 200 || { echo "==> Failed to acquire deploy lock after 1800s" >&2; exit 1; }

# Build context is the checkout itself, so the version tag is informational only
# (logging/traceability); no remote GIT_URL pinning is needed.
if [ -z "$VERSION_TAG" ] && [ -f composer.json ]; then
    RAW_VERSION=$(jq -r .version composer.json 2>/dev/null || echo "")
    if [ -n "$RAW_VERSION" ] && [ "$RAW_VERSION" != "null" ]; then
        VERSION_TAG="v${RAW_VERSION}"
    fi
fi
echo "==> Deploying version: ${VERSION_TAG:-unversioned}"

# Store current version for potential rollback
PREVIOUS_REVISION="${PREVIOUS_REVISION:-}"
if [ -z "$PREVIOUS_REVISION" ] && [ -f "$DEPLOY_DIR/.backups/last_known_good.json" ]; then
    PREVIOUS_REVISION=$(jq -r '.revision // empty' "$DEPLOY_DIR/.backups/last_known_good.json" 2>/dev/null || true)
fi
if [ -z "$PREVIOUS_REVISION" ]; then
    PREVIOUS_REVISION=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
fi
echo "==> Target rollback revision: $PREVIOUS_REVISION"

# Determine environment file for Docker Compose: prioritize explicit ENV_FILE,
# then .env.production on production hosts, falling back to .env or /etc/internara.env.
ENV_FILE="${ENV_FILE:-}"
if [ -z "$ENV_FILE" ]; then
    if [ -f "$DEPLOY_DIR/.env.production" ]; then
        ENV_FILE="$DEPLOY_DIR/.env.production"
    elif [ -f "$DEPLOY_DIR/.env" ]; then
        ENV_FILE="$DEPLOY_DIR/.env"
    elif [ -f "/etc/internara.env" ]; then
        ENV_FILE="/etc/internara.env"
    fi
elif [ ! -f "$ENV_FILE" ] && [ -f "$DEPLOY_DIR/$ENV_FILE" ]; then
    ENV_FILE="$DEPLOY_DIR/$ENV_FILE"
fi

# Compose fails fast when APP_KEY is missing. If .env.production exists but carries no
# usable key, backfill it from .env, /etc/internara.env, or the running container.
if [ -f "$DEPLOY_DIR/.env.production" ]; then
    APP_KEY_PROD=$(grep -E '^APP_KEY=base64:[^[:space:]#]+' "$DEPLOY_DIR/.env.production" || true)
    if [ -z "$APP_KEY_PROD" ]; then
        APP_KEY_BASE=""
        if [ -f "$DEPLOY_DIR/.env" ]; then
            APP_KEY_BASE=$(grep -E '^APP_KEY=base64:[^[:space:]#]+' "$DEPLOY_DIR/.env" || true)
        fi
        if [ -z "$APP_KEY_BASE" ] && [ -f "/etc/internara.env" ]; then
            APP_KEY_BASE=$(grep -E '^APP_KEY=base64:[^[:space:]#]+' "/etc/internara.env" || true)
        fi
        if [ -z "$APP_KEY_BASE" ]; then
            APP_KEY_BASE=$(docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' internara-app 2>/dev/null | grep -E '^APP_KEY=base64:[^[:space:]#]+' || true)
        fi
        if [ -z "$APP_KEY_BASE" ]; then
            echo "::error::APP_KEY is missing. Set APP_KEY=base64:... in .env.production or /etc/internara.env." >&2
            exit 1
        fi
        echo "==> Backfilling APP_KEY into .env.production"
        { grep -v '^APP_KEY=' "$DEPLOY_DIR/.env.production" || true; } > "$DEPLOY_DIR/.env.production.tmp"
        echo "$APP_KEY_BASE" >> "$DEPLOY_DIR/.env.production.tmp"
        mv "$DEPLOY_DIR/.env.production.tmp" "$DEPLOY_DIR/.env.production"
    fi
fi

ENV_FILE_ARGS=()
if [ -f "$DEPLOY_DIR/.env" ]; then
    ENV_FILE_ARGS+=(--env-file "$DEPLOY_DIR/.env")
fi
if [ -n "$ENV_FILE" ] && [ -f "$ENV_FILE" ] && [ "$ENV_FILE" != "$DEPLOY_DIR/.env" ]; then
    echo "==> Using environment file: $ENV_FILE"
    ENV_FILE_ARGS+=(--env-file "$ENV_FILE")
fi

NO_CACHE_FLAG=""
if [ "${NO_CACHE:-false}" = "true" ]; then
  NO_CACHE_FLAG="--no-cache"
fi

# Tag current running container image as internara-app:backup before building/recreating
CURRENT_APP_ID=$(docker compose "${ENV_FILE_ARGS[@]}" ps -q app 2>/dev/null || true)
if [ -n "$CURRENT_APP_ID" ]; then
    CURRENT_IMAGE_ID=$(docker inspect --format '{{.Image}}' "$CURRENT_APP_ID" 2>/dev/null || true)
    if [ -n "$CURRENT_IMAGE_ID" ]; then
        echo "==> Backing up active container image ($CURRENT_IMAGE_ID) as internara-app:backup"
        docker tag "$CURRENT_IMAGE_ID" internara-app:backup 2>/dev/null || true
    fi
fi

echo "==> Building Docker images"
# Keep the build cache warm; images are built from the current checkout.
# Set FORCE_PULL=true only when base image refresh is intentional.
PULL_FLAG=""
if [ "${FORCE_PULL:-false}" = "true" ]; then
    PULL_FLAG="--pull"
fi
docker compose --progress=plain "${ENV_FILE_ARGS[@]}" build $PULL_FLAG $NO_CACHE_FLAG

echo "==> Running pre-flight smoke check on candidate image before modifying active containers"
# Verify the newly built image can boot, has valid PHP syntax, and routes before touching the live service
if ! docker run --rm \
    --entrypoint /bin/sh \
    -e APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" \
    -e APP_ENV=production \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=:memory: \
    internara-app -c "php -l public/index.php && php artisan --version && php artisan route:list >/dev/null"; then
    echo "::error::Pre-flight smoke check FAILED on candidate image! Aborting deploy without modifying running container." >&2
    if [ -n "$PREVIOUS_REVISION" ] && [ "$PREVIOUS_REVISION" != "unknown" ]; then
        echo "==> Aligning workspace checkout back to $PREVIOUS_REVISION"
        git checkout --quiet "$PREVIOUS_REVISION" 2>/dev/null || true
        git reset --hard --quiet "$PREVIOUS_REVISION" 2>/dev/null || true
    fi
    exit 1
fi
echo "==> Pre-flight smoke check passed"

echo "==> Starting containers"
# Compose recreates only the app service when its image/config changed.
if ! docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans; then
    echo "::error::docker compose up failed. Dumping container logs:"
    docker compose "${ENV_FILE_ARGS[@]}" logs --tail=100 app || true
    if docker image inspect internara-app:backup >/dev/null 2>&1; then
        echo "==> Fast-recovering live service from internara-app:backup..."
        docker tag internara-app:backup internara-app
        docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans --force-recreate
    fi
    exit 1
fi

echo "==> Cleaning up dangling images"
docker image prune -f >/dev/null 2>&1 || true

echo "==> Clearing application and view cache"
docker compose "${ENV_FILE_ARGS[@]}" exec -T app php artisan optimize:clear || true

echo "==> Syncing compiled public assets to host public/build"
APP_CONTAINER_ID=$(docker compose "${ENV_FILE_ARGS[@]}" ps -q app 2>/dev/null || true)
if [ -n "$APP_CONTAINER_ID" ]; then
    mkdir -p "$DEPLOY_DIR/public/build"
    docker cp "$APP_CONTAINER_ID:/app/public/build/." "$DEPLOY_DIR/public/build/" || true
fi

# Ensure host web server (e.g. www-data) can traverse Docker root to serve assets from app_data volume
if [ -d /var/lib/docker ]; then
    chmod o+x /var/lib/docker /var/lib/docker/volumes 2>/dev/null || true
fi

echo "==> Waiting for health check (max 120s)"
HEALTH_CHECK_PASSED=false
HEALTH_TMP="${TMPDIR:-/tmp}/internara-health-body"
for i in {1..60}; do
    HTTP_CODE=$(curl -sSL -o "$HEALTH_TMP" -w "%{http_code}" -m 10 "$HEALTH_URL" 2>/dev/null || echo "000")
    if { [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; } && ! grep -q "Core system metadata (composer.json) is missing" "$HEALTH_TMP"; then
        echo "==> Deploy OK: $HEALTH_URL reachable (HTTP $HTTP_CODE, healthy body)"
        HEALTH_CHECK_PASSED=true
        break
    fi
    sleep 2
done

if [ "$HEALTH_CHECK_PASSED" = true ]; then
    echo "==> Verifying static assets are reachable and healthy..."
    ASSET_ERRORS=0

    # Ensure Livewire published assets warning is absent
    if grep -q "published Livewire assets are out of date" "$HEALTH_TMP"; then
        echo "::error::Livewire published assets are out of date on $HEALTH_URL" >&2
        ASSET_ERRORS=$((ASSET_ERRORS + 1))
    fi

    # Extract CSS and JS URLs from homepage HTML and test each
    ASSET_URLS=$(grep -oE '(src|href)="([^"]+\.(css|js)[^"]*)"' "$HEALTH_TMP" | sed -E 's/^(src|href)="([^"]+)"/\2/' | sort -u || true)
    for asset_path in $ASSET_URLS; do
        case "$asset_path" in
            http://*|https://*) TARGET_URL="$asset_path" ;;
            /*) TARGET_URL="${HEALTH_URL%/}$asset_path" ;;
            *) TARGET_URL="${HEALTH_URL%/}/$asset_path" ;;
        esac

        ASSET_CODE=$(curl -sSL -o /dev/null -w "%{http_code}" -m 10 "$TARGET_URL" 2>/dev/null || echo "000")
        if [ "$ASSET_CODE" != "200" ]; then
            echo "::error::Asset check failed ($ASSET_CODE): $TARGET_URL" >&2
            ASSET_ERRORS=$((ASSET_ERRORS + 1))
        else
            echo "==> Asset OK ($ASSET_CODE): $TARGET_URL"
        fi
    done

    if [ "$ASSET_ERRORS" -gt 0 ]; then
        echo "::error::Deploy FAILED: $ASSET_ERRORS static asset check(s) failed on $HEALTH_URL" >&2
        HEALTH_CHECK_PASSED=false
    fi
fi

if [ "$HEALTH_CHECK_PASSED" = false ]; then
    echo "==> Deploy FAILED: $HEALTH_URL not healthy after 120s (http=${HTTP_CODE})" >&2

    if [ "$ROLLBACK_ON_FAILURE" = true ]; then
        echo "==> Initiating fast automatic rollback..."
        # 1. Fast-restore active container from cached backup image if available
        if docker image inspect internara-app:backup >/dev/null 2>&1; then
            echo "==> Restoring from internara-app:backup image"
            docker tag internara-app:backup internara-app
            docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans --force-recreate
        elif [ "$PREVIOUS_REVISION" != "unknown" ]; then
            echo "==> Rebuilding previous revision $PREVIOUS_REVISION"
            git checkout --quiet "$PREVIOUS_REVISION"
            git reset --hard --quiet "$PREVIOUS_REVISION"
            docker compose "${ENV_FILE_ARGS[@]}" build --pull
            docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans --force-recreate
        fi

        # 2. Revert workspace commit
        if [ "$PREVIOUS_REVISION" != "unknown" ]; then
            echo "==> Reverting workspace git commit to $PREVIOUS_REVISION"
            git checkout --quiet "$PREVIOUS_REVISION" 2>/dev/null || true
            git reset --hard --quiet "$PREVIOUS_REVISION" 2>/dev/null || true
        fi
        echo "==> Fast rollback completed"
    else
        echo "==> Automatic rollback disabled"
        docker compose "${ENV_FILE_ARGS[@]}" ps
    fi

    exit 1
fi

rm -f "$HEALTH_TMP"

# Record healthy deployment as last known good
mkdir -p "$DEPLOY_DIR/.backups"
APP_CONTAINER_ID=$(docker compose "${ENV_FILE_ARGS[@]}" ps -q app 2>/dev/null || true)
CURRENT_IMG=""
if [ -n "$APP_CONTAINER_ID" ]; then
    CURRENT_IMG=$(docker inspect --format '{{.Image}}' "$APP_CONTAINER_ID" 2>/dev/null || true)
fi
cat > "$DEPLOY_DIR/.backups/last_known_good.json" << EOF
{
    "timestamp": "$(date +%Y%m%d_%H%M%S)",
    "revision": "$(git rev-parse HEAD 2>/dev/null || echo '')",
    "version_tag": "$VERSION_TAG",
    "image_id": "$CURRENT_IMG",
    "healthy_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF

# Retain the last N deployment backup artifacts (default: 5)
KEEP_BACKUPS="${KEEP_BACKUPS:-5}"
echo "==> Purging stale backup artifacts (retaining last $KEEP_BACKUPS)"
purge_deploy_backups() {
    BACKUP_DIR="$DEPLOY_DIR/.backups"
    if [ -d "$BACKUP_DIR" ]; then
        find "$BACKUP_DIR" -maxdepth 1 -name 'backup_*.json' -print0 \
            | sort -zr \
            | tail -z -n "+$((KEEP_BACKUPS + 1))" \
            | xargs -0r rm -f
    fi
    # Scheduled database dumps produced by the backup command.
    rm -f "$DEPLOY_DIR"/storage/app/backup/*.sql.gz
}
purge_deploy_backups
            | sort -zr \
            | tail -z -n "+$((KEEP_BACKUPS + 1))" \
            | xargs -0r rm -f
    fi
    # Scheduled database dumps produced by the backup command.
    rm -f "$DEPLOY_DIR"/storage/app/backup/*.sql.gz
}
purge_deploy_backups

echo "==> Deploy completed successfully"
