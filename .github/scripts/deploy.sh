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
flock -w 600 200 || { echo "==> Failed to acquire deploy lock after 600s" >&2; exit 1; }

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
PREVIOUS_REVISION=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
echo "==> Current revision: $PREVIOUS_REVISION"

# Determine environment file for Docker Compose: prioritize explicit ENV_FILE,
# then .env.production on production/staging hosts, falling back to .env or /etc/internara.env.
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

# If .env.production exists alongside .env or container secrets on the host, backfill required compose secrets
# (APP_KEY, DB_PASSWORD, etc.) into .env.production if missing or blank.
if [ -f "$DEPLOY_DIR/.env.production" ]; then
    for secret_var in APP_KEY DB_PASSWORD DB_DATABASE DB_USERNAME DB_HOST DB_PORT; do
        VAL_PROD=$(grep -E "^${secret_var}=[^[:space:]#]+" "$DEPLOY_DIR/.env.production" || true)
        if [ -z "$VAL_PROD" ]; then
            VAL_BASE=""
            if [ -f "$DEPLOY_DIR/.env" ]; then
                VAL_BASE=$(grep -E "^${secret_var}=[^[:space:]#]+" "$DEPLOY_DIR/.env" || true)
            fi
            if [ -z "$VAL_BASE" ] && [ -f "/etc/internara.env" ]; then
                VAL_BASE=$(grep -E "^${secret_var}=[^[:space:]#]+" "/etc/internara.env" || true)
            fi
            if [ -z "$VAL_BASE" ]; then
                VAL_BASE=$(docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' internara-app 2>/dev/null | grep -E "^${secret_var}=[^[:space:]#]+" || true)
            fi
            if [ -z "$VAL_BASE" ] && [ "$secret_var" = "DB_PASSWORD" ]; then
                VAL_BASE=$(docker inspect --format '{{range .Config.Env}}{{println .}}{{end}}' internara-db 2>/dev/null | grep -E '^MYSQL_PASSWORD=[^[:space:]#]+' | sed 's/^MYSQL_PASSWORD=/DB_PASSWORD=/' || true)
            fi

            if [ -z "$VAL_BASE" ] && [ "$secret_var" = "DB_PASSWORD" ]; then
                VAL_BASE="DB_PASSWORD=Password321"
            fi
            if [ -z "$VAL_BASE" ] && [ "$secret_var" = "DB_DATABASE" ]; then
                VAL_BASE="DB_DATABASE=internara"
            fi
            if [ -z "$VAL_BASE" ] && [ "$secret_var" = "DB_USERNAME" ]; then
                VAL_BASE="DB_USERNAME=internara"
            fi

            if [ -n "$VAL_BASE" ]; then
                echo "==> Backfilling ${secret_var} into .env.production"
                { grep -v "^${secret_var}=" "$DEPLOY_DIR/.env.production" || true; } > "$DEPLOY_DIR/.env.production.tmp"
                echo "$VAL_BASE" >> "$DEPLOY_DIR/.env.production.tmp"
                mv "$DEPLOY_DIR/.env.production.tmp" "$DEPLOY_DIR/.env.production"
            fi
        fi
    done
elif [ ! -f "$DEPLOY_DIR/.env.production" ] && [ -f "$DEPLOY_DIR/.env" ]; then
    cp "$DEPLOY_DIR/.env" "$DEPLOY_DIR/.env.production"
    if ! grep -qE '^DB_PASSWORD=[^[:space:]#]+' "$DEPLOY_DIR/.env.production"; then
        echo "DB_PASSWORD=Password321" >> "$DEPLOY_DIR/.env.production"
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

echo "==> Building Docker images"
# Keep the build cache warm; images are built from the current checkout.
# Set FORCE_PULL=true only when base image refresh is intentional.
PULL_FLAG=""
if [ "${FORCE_PULL:-false}" = "true" ]; then
    PULL_FLAG="--pull"
fi
docker compose "${ENV_FILE_ARGS[@]}" build $PULL_FLAG $NO_CACHE_FLAG

echo "==> Starting containers"
# Compose recreates only services whose image/config changed; the database stays up.
if ! docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans; then
    echo "::error::docker compose up failed. Dumping container logs:"
    docker compose "${ENV_FILE_ARGS[@]}" logs --tail=100 app || true
    docker compose "${ENV_FILE_ARGS[@]}" logs --tail=100 db || true
    exit 1
fi

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
        docker compose "${ENV_FILE_ARGS[@]}" build --no-cache --pull
        docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans --force-recreate
        echo "==> Rollback completed"
    else
        echo "==> Automatic rollback disabled or no previous revision available"
        docker compose "${ENV_FILE_ARGS[@]}" ps
    fi

    exit 1
fi

rm -f "$HEALTH_TMP"

# Backups only matter while a deploy is in flight; once the new release is healthy
# they are stale by definition, so purge them. Set KEEP_BACKUPS to retain the N
# newest deploy metadata files (0 = purge everything).
KEEP_BACKUPS="${KEEP_BACKUPS:-0}"
echo "==> Purging backup artifacts (KEEP_BACKUPS=$KEEP_BACKUPS)"
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

echo "==> Deploy completed successfully"
