# Troubleshooting — Common Issues & Resolutions

> **Last updated:** 2026-07-10 **Changes:** initial — comprehensive troubleshooting reference

## Description

Systematic troubleshooting guide covering installation, authentication, queue, file uploads,
performance, and database issues. For health check reference, see
[System Health & Troubleshooting](../guide/04-system-health-and-troubleshooting.md).

---

## Installation & Setup

### `php artisan setup:install` fails

| Symptom | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| "PHP version X required" | Wrong PHP version | `php -v` → install PHP 8.4+ |
| "Extension X not found" | Missing PHP extensions | Install: `bcmath`, `ctype`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_*`, `tokenizer`, `xml` |
| "Database connection failed" | Wrong DB credentials | Check `.env` `DB_*` values |
| "Storage directory not writable" | Permission issue | `chmod -R 775 storage/ bootstrap/cache/` |
| "APP_KEY is not set" | Missing app key | `php artisan key:generate` |

### Setup wizard shows blank page

1. Check `storage/logs/laravel.log` for PHP errors
2. Verify `APP_DEBUG=true` in `.env` (temporarily)
3. Ensure `public/` is the document root
4. Check PHP memory limit ≥ 128M

---

## Authentication

### Cannot log in

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| "Account locked" | 10 failed attempts | Wait for auto-unlock or admin manual unlock via SysAdmin |
| "Account inactive" | Account disabled by admin | Contact school administrator |
| "Invalid credentials" | Wrong email/username or password | Use "Forgot Password" flow |
| Redirect loop after login | Session/cache issue | Clear browser cookies + `php artisan optimize:clear` |

### Rate limited

| Endpoint | Limit | Reset |
| -------- | ----- | ----- |
| Login | 5 attempts per 60s | Wait 60 seconds |
| Forgot password | 3 attempts per 3600s | Wait 1 hour |
| Password reset | 5 attempts per 300s | Wait 5 minutes |
| Account recovery | 3 attempts per 300s | Wait 5 minutes |

---

## Queue & Jobs

### Jobs not processing

| Check | Command/File | Expected |
| ----- | ------------ | -------- |
| Queue connection | `QUEUE_CONNECTION` in `.env` | `sync` (shared hosting) or `redis` (VPS) |
| Worker running (Tier 2+) | `supervisorctl status` | All programs RUNNING |
| Scheduler running | `crontab -l` or Supervisor | `schedule:run` every minute |
| Failed jobs | `php artisan queue:failed` | Empty or actionable |

### "Maximum execution time exceeded"

- Increase `max_execution_time` in `php.ini` (300s recommended for PDF generation)
- Move long operations to queue (ensure `QUEUE_CONNECTION` is not `sync`)
- Split batch operations into smaller chunks

---

## File Uploads

### Upload fails

| Symptom | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| "File too large" | Exceeds `upload_max_filesize` | Increase in `php.ini` (default: 64MB for media) |
| "Invalid file type" | MIME type not allowed | Check `registerMediaCollections()` in the model |
| "Disk quota exceeded" | Storage full | Free disk space or upgrade storage plan |
| 413 Request Entity Too Large | Nginx/Apache limit | Increase `client_max_body_size` (Nginx) or `LimitRequestBody` (Apache) |

---

## Performance

### Slow page loads

| Likely Cause | Diagnosis | Resolution |
| ------------ | --------- | ---------- |
| Cache not warmed | `php artisan system:health` shows cache warnings | Run `php artisan optimize` |
| N+1 queries | Check Laravel Debugbar or query log | Add `->with()` for relationships |
| Missing indexes | Slow queries in MySQL `slow_query_log` | Add database indexes |
| PHP-FPM exhausted | `pm.max_children` too low | Increase in `www.conf` |
| Shared hosting CPU spike | Too many concurrent users | Upgrade to VPS (Tier 2) |

### High memory usage

1. Check `php.ini` `memory_limit` (minimum 128M, recommended 256M)
2. For batch processing, use `chunk()` or `lazy()` instead of `get()`
3. Reduce `pm.max_children` if using dynamic FPM process manager
4. Enable OpCache: `opcache.enable=1`, `opcache.memory_consumption=128`

---

## Database

### Migration errors

| Symptom | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| "Table already exists" | Partial migration | `php artisan migrate --force` continues from last batch |
| "Column already exists" | Migration conflict | Check for duplicate migration files |
| "Foreign key constraint fails" | Data dependency | Ensure related tables are migrated first |
| "Syntax error" | Wrong database engine | Check `DB_CONNECTION` matches your database |

### Connection issues

```
SQLSTATE[HY000] [2002] Connection refused
```

1. Is the database server running? `systemctl status mysql`
2. Is the port correct? Default: 3306 (MySQL), 5432 (PostgreSQL)
3. Is the host reachable? `ping db_host` or `telnet db_host 3306`
4. Are credentials correct? Check `DB_USERNAME` / `DB_PASSWORD`
5. Does the database exist? `CREATE DATABASE internara;`

### SQLite-specific

| Issue | Resolution |
| ----- | ---------- |
| "database is locked" | Enable WAL mode: `PRAGMA journal_mode=WAL;` |
| Read-only database | `chmod 664 database/database.sqlite` |
| Concurrent write contention | Switch to MySQL/MariaDB for multi-user deployments |
| File not found | `touch database/database.sqlite && php artisan migrate --force` |

---

## Email

### Emails not sending

```bash
# Test mail configuration
php artisan tinker --execute="Mail::raw('Test', fn(\$msg) => \$msg->to('admin@school.sch.id'));"
```

| Symptom | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| Timeout on send | SMTP server unreachable | Check `MAIL_HOST` / `MAIL_PORT` |
| Authentication failed | Wrong credentials | Verify `MAIL_USERNAME` / `MAIL_PASSWORD` |
| "Connection refused" | SMTP port blocked | Try 587 (TLS) or 465 (SSL) |
| Emails in spam | Missing SPF/DKIM | Configure SPF, DKIM, and DMARC DNS records |

---

## Cache & Session

### "Whoops, something went wrong" after config change

```bash
# Clear all caches
php artisan optimize:clear
```

### Users logged out randomly

| Cause | Resolution |
| ----- | ---------- |
| Session driver set to `file` on multi-server | Switch to `database` or `redis` |
| Session lifetime too short | Increase `SESSION_LIFETIME` in `.env` (default: 120 min) |
| Cookie domain mismatch | Set `SESSION_DOMAIN` to match `APP_URL` |
| Redis connection lost | Check Redis service: `systemctl status redis` |

---

## Known Issues

| Issue | Status | Workaround |
| ----- | ------ | ---------- |
| SQLite concurrent write lock under heavy load | Open | Switch to MySQL/MariaDB |
| PHP memory limit for large batch certificate generation | Open | Process in smaller batches |
| PDF generation timeout on shared hosting | Open | Reduce template complexity or upgrade to VPS |

---

## Diagnostic Commands

```bash
# Quick health check
php artisan system:health

# Full system information
php artisan about

# View logs
php artisan pail                     # Tail logs (dev)
tail -f storage/logs/laravel.log     # Tail logs (prod)

# Database diagnostics
php artisan migrate:status           # Migration status
php artisan db:show                  # Database info
php artisan db:table                 # Table details

# Queue diagnostics
php artisan queue:failed             # List failed jobs
php artisan queue:monitor default,documents  # Queue sizes

# Cache diagnostics
php artisan cache:clear              # Clear cache store
php artisan config:clear             # Clear config cache
php artisan view:clear               # Clear compiled views
```

---

## Getting Help

If the above steps don't resolve your issue:

1. Check [GitHub Issues](https://github.com/reasvyn/internara/issues) for known problems
2. Search or post in [Discussions](https://github.com/reasvyn/internara/discussions)
3. Email [reasvyn@gmail.com](mailto:reasvyn@gmail.com) with:
   - `php artisan system:health` output
   - Relevant error messages from `storage/logs/laravel.log`
   - Steps to reproduce
