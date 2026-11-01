#!/usr/bin/env bash
# Create a backup of the current deployment before updating.
# Stores the current git revision and timestamp for rollback purposes.
# Runs on the VPS during deployment.
set -euo pipefail

# Repo root derived from the script location so any clone path works.
DEPLOY_DIR="${DEPLOY_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
BACKUP_DIR="${DEPLOY_DIR}/.backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

cd "$DEPLOY_DIR"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Get current version before deploy
CURRENT_REVISION=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
CURRENT_TAG=$(git describe --tags --exact-match 2>/dev/null || echo "untagged")

echo "==> Creating backup: $TIMESTAMP"
echo "  Revision: $CURRENT_REVISION"
echo "  Tag: $CURRENT_TAG"

# Tag current running container image if Docker is available
CURRENT_APP_ID=$(docker compose ps -q app 2>/dev/null || true)
CURRENT_IMAGE=""
if [ -n "$CURRENT_APP_ID" ]; then
    CURRENT_IMAGE=$(docker inspect --format '{{.Image}}' "$CURRENT_APP_ID" 2>/dev/null || true)
    if [ -n "$CURRENT_IMAGE" ]; then
        echo "  Active Image: $CURRENT_IMAGE (tagged as internara-app:backup)"
        docker tag "$CURRENT_IMAGE" internara-app:backup 2>/dev/null || true
    fi
fi

# Store backup metadata
cat > "$BACKUP_DIR/backup_${TIMESTAMP}.json" << EOF
{
    "timestamp": "$TIMESTAMP",
    "revision": "$CURRENT_REVISION",
    "tag": "$CURRENT_TAG",
    "image_id": "$CURRENT_IMAGE",
    "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF

# Update pointer to last known good deployment
cat > "$BACKUP_DIR/last_known_good.json" << EOF
{
    "timestamp": "$TIMESTAMP",
    "revision": "$CURRENT_REVISION",
    "tag": "$CURRENT_TAG",
    "image_id": "$CURRENT_IMAGE",
    "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF

# Keep last 5 backups (disk space management)
ls -t "$BACKUP_DIR"/backup_*.json 2>/dev/null | tail -n +6 | xargs -r rm -f

echo "==> Backup created: $BACKUP_DIR/backup_${TIMESTAMP}.json"
echo "$TIMESTAMP"