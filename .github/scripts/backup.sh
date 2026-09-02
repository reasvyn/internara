#!/usr/bin/env bash
# Create a backup of the current deployment before updating.
# Stores the current git revision and timestamp for rollback purposes.
# Runs on the VPS during deployment.
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-$HOME/apps/internara}"
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

# Store backup metadata
cat > "$BACKUP_DIR/backup_${TIMESTAMP}.json" << EOF
{
    "timestamp": "$TIMESTAMP",
    "revision": "$CURRENT_REVISION",
    "tag": "$CURRENT_TAG",
    "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
EOF

# Keep only last 5 backups (disk space management)
ls -t "$BACKUP_DIR"/backup_*.json 2>/dev/null | tail -n +6 | xargs -r rm -f

echo "==> Backup created: $BACKUP_DIR/backup_${TIMESTAMP}.json"
echo "$TIMESTAMP"