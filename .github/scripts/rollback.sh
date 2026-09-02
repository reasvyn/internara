#!/usr/bin/env bash
# Rollback to the previous deployment version.
# Restores the last backup revision and redeploys.
# Run manually on the VPS if deployment fails.
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-$HOME/apps/internara}"
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

echo "==> Rolled back to $BACKUP_TAG"

# Redeploy with the rolled-back version
echo "==> Redeploying..."
docker compose build --no-cache --pull
docker compose up -d --remove-orphans --force-recreate

echo "==> Waiting for health check..."
for i in {1..30}; do
    HEALTH_URL="${HEALTH_URL:-https://internara.web.id}"
    if curl -fsS -o /dev/null "$HEALTH_URL"; then
        echo "==> Rollback successful: $HEALTH_URL reachable"
        exit 0
    fi
    sleep 2
done

echo "==> Rollback health check failed" >&2
exit 1