# Backup & Recovery — Account and System Recovery

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite to developer reference; merge from `docs/guide/06-admin-recovery.md`, `docs/infrastructure/backup-recovery.md`

## Description

Reference for account recovery (super admin, user passwords) and system-level backup/restore
procedures. Covers recovery key lifecycle, CLI recovery commands, backup strategies (database,
files, full), automated backup configuration, restoration procedures, and recovery scenarios.

---

## Account Recovery

### Super Admin Account

The super admin has fixed identity properties:

| Property | Value |
| -------- | ----- |
| Name | `Administrator` (not editable) |
| Username | `superadmin` (not editable) |
| Status | `PROTECTED` (cannot be deleted, locked, or suspended) |
| Access | Bypasses all permission checks via `Gate::before` |

Created during [Setup Wizard](setup-wizard.md) (Step 2) or via CLI: `php artisan admin:create`.

### Recovery Key Lifecycle

1. **Generation** — 64-character random string via `random_bytes(32)` → hex-encoded
2. **Storage** — bcrypt hash in `users` table; plaintext in `storage/app/private/.recovery-key` (chmod 0600)
3. **Usage** — required for `admin:recover` when password is lost
4. **Rotation** — new key generated after every successful recovery; previous key invalidated

### Recovery Key CLI

| Command | Description |
| ------- | ----------- |
| `php artisan admin:create` | Create super admin (only if none exists) |
| `php artisan admin:create admin@sekolah.sch.id` | Create with email prompt |
| `php artisan admin:recover` | Reset super admin password |
| `php artisan admin:recover --key=<key>` | Recover with explicit key |
| `php artisan admin:recover --regenerate-file` | Rewrite recovery key file from `--key` |
| `php artisan admin:recovery-path` | Show recovery key file location |
| `php artisan admin:recovery-show` | Display stored recovery key (logged as security event) |

### Recovery Flow

```
admin:recover
    ↓
1. Verify recovery key (file or --key flag)
    ↓
2. OTP verification (production only) — 6-digit code emailed to admin
    ↓
3. Enter new password (min 8 chars)
    ↓
4. Confirm via email address
    ↓
5. New recovery key generated — save it!
```

### Security Properties

- Recovery key never stored in plaintext in DB — bcrypt hash only
- Rate-limited: **3 attempts per 15 minutes**
- Production: OTP emailed as second factor
- All recovery attempts logged with PII masking
- Other super admins notified on recovery
- Key rotated on every successful recovery

---

## System Backup

### What to Back Up

| Asset | Location | Frequency | Retention |
| ----- | -------- | --------- | --------- |
| Database | SQLite file or MySQL/PG dump | Daily | 30 days |
| User uploads | `storage/app/` (local) or S3 | Daily | 30 days |
| Generated files | `storage/app/public/` | Daily | 30 days |
| Environment config | `.env` (not in repo) | Per deployment | Permanent |
| Application code | Git repository | Per commit | Permanent |

### Backup Commands

| Command | Description |
| ------- | ----------- |
| `php artisan system:backup` | Run backup using configured settings |
| `php artisan system:backup --type=database` | Database only |
| `php artisan system:backup --type=storage` | Uploaded files only |
| `php artisan system:backup --type=both` | Database + files |
| `php artisan system:backup --force` | Skip pre-flight checks |
| `php artisan system:backup --cleanup` | Run cleanup after backup |

### Backup Configuration (Admin → Settings → Backups)

| Setting | Description | Default |
| ------- | ----------- | ------- |
| Auto Backup | Enable scheduled automatic backups | Off |
| Frequency | How often to run backups | Daily |
| Schedule Time | Time of day for backup | 02:00 |
| Retention | Days to keep backups | 30 |
| Include Database | Include database dump | Yes |
| Include Storage | Include uploaded files | Yes |

Backup files stored at `storage/app/backup/` (not publicly accessible). Naming: `backup_{type}_{date}.{ext}`.

### Database Backup Commands (Manual)

```bash
# MySQL / MariaDB
mysqldump --single-transaction --routines --triggers \
    -u internara -p internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz

# PostgreSQL
pg_dump -U internara -h localhost internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz

# SQLite
cp database/database.sqlite backups/$(date +%Y%m%d_%H%M%S).sqlite
```

### File Backup (Manual)

```bash
tar -czf backups/storage_$(date +%Y%m%d).tar.gz \
    -C storage/app public private
```

### Automated Backup via Cron

```cron
0 2 * * * /path/to/backup-script.sh
```

Example script:

```bash
#!/bin/bash
BACKUP_DIR=/path/to/backups
DB_NAME=internara
DB_USER=internara
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mysqldump --single-transaction -u "$DB_USER" -p "$DB_NAME" \
    | gzip > "$BACKUP_DIR/db_$TIMESTAMP.sql.gz"

tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" \
    -C /path/to/app/storage app public private

find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +30 -delete
find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +30 -delete
```

---

## Restoration

### Full Restoration Procedure

```bash
# 1. Restore database
# MySQL:
gunzip -c backups/db_20260101.sql.gz | mysql -u internara -p internara
# PostgreSQL:
gunzip -c backups/db_20260101.sql.gz | psql -U internara -h localhost internara
# SQLite:
cp backups/20260101_020000.sqlite database/database.sqlite

# 2. Restore files
tar -xzf backups/storage_20260101.tar.gz -C /path/to/app/storage

# 3. Restore .env from secure storage

# 4. Rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Verify
php artisan system:health
```

### Point-in-Time Recovery (MySQL)

Enable binary logs for MySQL:

```ini
; /etc/mysql/my.cnf
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
```

Restore to a specific time:

```bash
mysqlbinlog --stop-datetime="2026-01-01 12:00:00" \
    /var/log/mysql/mysql-bin.000001 | mysql -u internara -p internara
```

---

## Recovery Scenarios

| Scenario | Approach |
| -------- | -------- |
| **Lost database** | Restore from most recent dump. Files remain intact. |
| **Lost files (local storage)** | Restore from file backup. Media library entries tied to model UUIDs, not paths. |
| **Lost everything (server failure)** | Provision new server → Git clone → restore `.env` → restore DB → restore files → rebuild caches → verify |
| **Accidental data deletion** | Check `activity_log` for audit trail. Most records soft-deleted or status-transitioned. Restore from previous backup if recent. |

---

## Monitoring

| Check | Command | Alert If |
| ----- | ------- | -------- |
| Backup age | `find backups/ -mtime +1` | No backup in 24 hours |
| Disk space | `df -h /` | Usage exceeds 90% |
| Database connectivity | `php artisan system:health` | Health check fails |
| Storage writable | `touch storage/test && rm storage/test` | Write failure |

---

## Quick References

- `app/SysAdmin/Backup/` — Backup management Livewire components
- `app/SysAdmin/Console/Commands/SystemBackupCommand.php` — Backup CLI command
- `app/Auth/Console/Commands/AdminRecoverCommand.php` — Recovery CLI command
- `app/Auth/Console/Commands/AdminCreateCommand.php` — Admin creation CLI
- `storage/app/private/.recovery-key` — Recovery key file
- `storage/app/backup/` — Backup files
- [Setup Wizard](setup-wizard.md) — Initial admin creation
- [System Health](system-health.md) — Health check and diagnostics
- [System Observability](system-observability.md) — Monitoring, audit logs
- `docs/infrastructure/deployment.md` — Deployment paths
