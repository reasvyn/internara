# Backup & Recovery

> **Last updated:** 2026-06-14

Regular backups protect your school's data against hardware failure, accidental deletion, and security incidents. This guide covers what to back up, how to automate it, and how to restore when needed.

---

## What to Back Up

| Asset                  | Location                             | Frequency      | Retention |
| ---------------------- | ------------------------------------ | -------------- | --------- |
| **Database**           | SQLite file or MySQL/PostgreSQL dump | Daily          | 30 days   |
| **User uploads**       | `storage/app/` (local) or S3 bucket  | Daily          | 30 days   |
| **Generated files**    | `storage/app/public/`                | Daily          | 30 days   |
| **Environment config** | `.env` (store securely, not in repo) | Per deployment | Permanent |
| **Application code**   | Git repository                       | Per commit     | Permanent |

---

## Database Backup

### SQLite (Development)

```bash
cp database/database.sqlite backups/$(date +%Y%m%d_%H%M%S).sqlite
```

### MySQL / MariaDB (Production)

```bash
mysqldump --single-transaction --routines --triggers \
    -u internara -p internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz
```

The `--single-transaction` flag creates a consistent snapshot without locking tables for reads.

### PostgreSQL (Production)

```bash
pg_dump -U internara -h localhost internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz
```

### Automate with Cron

```cron
0 2 * * * /path/to/backup-script.sh
```

Example backup script:

```bash
#!/bin/bash
BACKUP_DIR=/path/to/backups
DB_NAME=internara
DB_USER=internara
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Database dump
mysqldump --single-transaction -u "$DB_USER" -p "$DB_NAME" \
    | gzip > "$BACKUP_DIR/db_$TIMESTAMP.sql.gz"

# Files
tar -czf "$BACKUP_DIR/storage_$TIMESTAMP.tar.gz" \
    -C /path/to/app/storage app public private

# Delete backups older than 30 days
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +30 -delete
find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +30 -delete
```

---

## File Backup

### Local Storage

```bash
tar -czf backups/storage_$(date +%Y%m%d).tar.gz \
    -C storage/app public private
```

### S3-Compatible Storage

If using S3 natively for file storage, enable versioning on the bucket for point-in-time recovery. The provider handles durability automatically.

---

## Full Restoration

### Step 1: Restore the Database

```bash
# MySQL / MariaDB
gunzip -c backups/db_20260101.sql.gz | mysql -u internara -p internara

# PostgreSQL
gunzip -c backups/db_20260101.sql.gz | psql -U internara -h localhost internara

# SQLite
cp backups/20260101_020000.sqlite database/database.sqlite
```

### Step 2: Restore Files

```bash
tar -xzf backups/storage_20260101.tar.gz -C /path/to/app/storage
```

### Step 3: Restore Environment

Restore `.env` from secure storage (password manager, encrypted bucket, or offline storage).

### Step 4: Rebuild Caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Verify

```bash
php artisan system:health
```

---

## Point-in-Time Recovery

For MySQL, enable binary logs to recover to a specific moment:

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

## Monitoring

| Check                 | Command                                 | Alert If              |
| --------------------- | --------------------------------------- | --------------------- |
| Backup age            | `find backups/ -mtime +1`               | No backup in 24 hours |
| Disk space            | `df -h /`                               | Usage exceeds 90%     |
| Database connectivity | `php artisan system:health`             | Health check fails    |
| Storage writable      | `touch storage/test && rm storage/test` | Write failure         |

---

## Recovery Scenarios

### Lost Database

Restore from the most recent database dump. Files remain intact. Data loss is limited to the interval between the last backup and the failure.

### Lost Files (Local Storage)

Restore files from the most recent file backup. Database records referencing those files remain consistent because media library entries are tied to model UUIDs, not file paths.

### Lost Everything (Server Failure)

1. Provision a new server following [Deployment](deployment.md)
2. Install the same application code from Git
3. Restore `.env` from secure storage
4. Restore the database from the most recent dump
5. Restore files from the most recent archive
6. Rebuild caches
7. Verify with `php artisan system:health`

### Accidental Data Deletion

If data was deleted through the application (not a database-level drop), check the audit log (`activity_log` table) to identify what was changed and by whom. Most records are soft-deleted or status-transitioned rather than hard-deleted. If the deletion was recent, restore the affected records from the previous backup.

---

## References

- [Deployment](deployment.md) — installation and server setup
- [Observability](observability.md) — monitoring and health checks
- [Installation](../guide/01-installation.md) — command reference
- [Infrastructure](infrastructure.md) — recovery objectives by tier
