# Blueprint 06: Backup & Disaster Recovery

## What to Back Up

| Asset | Location | Frequency | Retention |
|---|---|---|---|
| **Database** | SQLite file or MySQL/PG dump | Daily | 30 days |
| **User uploads** | `storage/app/` (local) or S3 bucket | Daily | 30 days |
| **Generated files** | `storage/app/public/` | Daily | 30 days |
| **Environment config** | `.env` (store securely, not in repo) | Per deployment | Permanent |
| **Application code** | Git repository | Per commit | Permanent |

## Database Backup

### SQLite (Development)

```bash
cp database/database.sqlite backups/$(date +%Y%m%d_%H%M%S).sqlite
```

### MySQL / PostgreSQL (Production)

```bash
# MySQL
mysqldump --single-transaction --routines --triggers \
    -u internara -p internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz

# PostgreSQL
pg_dump -U internara -h localhost internara \
    | gzip > backups/db_$(date +%Y%m%d).sql.gz
```

Automate with cron:
```cron
0 2 * * * /path/to/backup-script.sh
```

## File Backup

### Local Storage

```bash
tar -czf backups/storage_$(date +%Y%m%d).tar.gz \
    -C storage/app public private
```

### S3-Compatible Storage

If using S3 natively, enable versioning on the bucket for point-in-time
recovery. The provider handles durability automatically.

## Disaster Recovery

### Full Restoration

```bash
# 1. Restore database
gunzip -c backups/db_20260101.sql.gz | mysql -u internara -p internara

# 2. Restore files
tar -xzf backups/storage_20260101.tar.gz -C storage/app

# 3. Rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Verify
php artisan system:health
```

### Point-in-Time Recovery

For MySQL, enable binary logs:
```ini
# /etc/mysql/my.cnf
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
```

Restore to a specific time:
```bash
mysqlbinlog --stop-datetime="2026-01-01 12:00:00" \
    /var/log/mysql/mysql-bin.000001 | mysql -u internara -p internara
```

## Monitoring

| Check | Command | Alert If |
|---|---|---|
| Backup age | `find backups/ -mtime +1` | No backup in 24h |
| Disk space | `df -h /` | Usage > 90% |
| Database connectivity | `php artisan system:health` | Health check fails |

## References

- `docs/installation.md` — setup and restore steps
- `docker-compose.yml` — database volume definitions
- `app/Domain/Core/Console/Commands/HealthCommand.php` — system validation
