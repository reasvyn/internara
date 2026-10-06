#!/usr/bin/env bash
# Rollback to the previous deployment version.
# Restores the last backup revision and redeploys.
# Run manually on the VPS if deployment fails.
set -euo pipefail

# Repo root derived from the script location so any clone path works.
DEPLOY_DIR="${DEPLOY_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
BACKUP_DIR="${DEPLOY_DIR}/.backups"

cd "$DEPLOY_DIR"

# Find the latest backup
LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/backup_*.json 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "==> No backup found to rollback to" >&2
    exit 1
fi

echo "==> Found backup: $LATEST_BACKUP"

# Extract revision from backup
BACKUP_REVISION=$(jq -r '.revision' "$LATEST_BACKUP" 2>/dev/null || echo "")
BACKUP_TAG=$(jq -r '.tag' "$LATEST_BACKUP" 2>/dev/null || echo "")
BACKUP_TIMESTAMP=$(jq -r '.timestamp' "$LATEST_BACKUP" 2>/dev/null || echo "unknown")

if [ -z "$BACKUP_REVISION" ] || [ "$BACKUP_REVISION" = "null" ]; then
    echo "==> Backup revision is invalid" >&2
    exit 1
fi

echo "==> Rolling back to: $BACKUP_TAG ($BACKUP_REVISION)"
echo "==> Backup from: $BACKUP_TIMESTAMP"

# Restore to the backup revision
git checkout --quiet "$BACKUP_REVISION"
git reset --hard --quiet "$BACKUP_REVISION"

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

# Redeploy with the rolled-back version
echo "==> Redeploying..."
docker compose "${ENV_FILE_ARGS[@]}" build --no-cache --pull
docker compose "${ENV_FILE_ARGS[@]}" up -d --remove-orphans --force-recreate

echo "==> Waiting for health check..."
for i in {1..30}; do
    HEALTH_URL="${HEALTH_URL:-https://internara.web.id}"
    if curl -fsSL -o /dev/null "$HEALTH_URL"; then
        echo "==> Rollback successful: $HEALTH_URL reachable"
        exit 0
    fi
    sleep 2
done

echo "==> Rollback health check failed" >&2
exit 1