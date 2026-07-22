# System Health, Troubleshooting & Diagnostics

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite to developer reference; merge from `docs/guide/04`, `docs/infrastructure/troubleshooting.md`

## Description

Developer reference for system health verification, routine maintenance, diagnostic commands, and
troubleshooting. Covers the `system:health` command, all operational Artisan commands, and a
categorized troubleshooting matrix.

---

## 1. Problem Statements

### PS-1 — Health Verification

Developers and sysadmins need a single command to verify all system prerequisites, database state,
and service connectivity. Manual verification of15+ checks is error-prone.

### PS-2 — Troubleshooting Reference

Common failure modes (database locks, queue stalls, cache corruption, permission issues) need a
categorized reference with diagnostic commands and resolutions.

---

## 2. System Health Command

```bash
php artisan system:health
```

### 2.1 Health Checks

| # | Check | What It Verifies | Fail Condition |
| - | ----- | ---------------- | -------------- |
| 1 | Environment | `.env` file exists | Missing `.env` |
| 2 | Setup Status | System has been installed | Wizard not completed |
| 3 | PHP Version | >= 8.4.0 | Version < 8.4.0 |
| 4 | Required Extensions | All12 extensions loaded | Missing extension |
| 5 | Recommended Extensions | Redis, PCNTL, POSIX | Missing (warning only) |
| 6 | PHP Memory | >= 128 MB | memory_limit < 128M |
| 7 | Database | Connection works, tables exist | Connection refused |
| 8 | Migrations | All migrations current | Pending migrations |
| 9 | Storage | Directories writable | Permission denied |
| 10 | Disk Space | < 85% usage | >= 85% (warn), >= 95% (fail) |
| 11 | Queue | No excessive failed jobs | > 10 failed jobs |
| 12 | Cache | Read/write works | Cache driver unreachable |
| 13 | App Key | Set and valid | Missing APP_KEY |
| 14 | Storage Link | `public/storage` symlink exists | Missing symlink |
| 15 | Maintenance Mode | Application is live | `php artisan down` active |

### 2.2 Output Formats

```bash
php artisan system:health          # Human-readable table
php artisan system:health --json   # JSON array (for monitoring tools)
```

---

## 3. Operational Commands Reference

### 3.1 Monitoring

| Command | Description |
| ------- | ----------- |
| `php artisan system:health` | 15-point health check |
| `php artisan system:health --json` | JSON output |
| `php artisan system:cache-warm` | Pre-load settings, brand, config, views, events |
| `php artisan system:cleanup` | Prune stale data |
| `php artisan queue:failed` | List failed queue jobs |
| `php artisan queue:monitor default,documents` | Queue sizes |

### 3.2 Cache Management

| Command | Description |
| ------- | ----------- |
| `php artisan optimize` | Cache config, routes, views, events (**production only**) |
| `php artisan optimize:clear` | Clear all caches |
| `php artisan cache:clear` | Clear application cache |
| `php artisan config:clear` | Clear config cache |
| `php artisan view:cache` | Compile Blade templates |
| `php artisan view:clear` | Clear compiled views |

### 3.3 Setup & Recovery

| Command | Description |
| ------- | ----------- |
| `php artisan setup:install` | Full provision: audit + migrate + signed URL |
| `php artisan setup:install --force` | Wipe database and start fresh |
| `php artisan setup:install --check-only` | Audit environment only |
| `php artisan setup:reset-token` | Regenerate signed URL |
| `php artisan admin:recover` | Recover super admin access |
| `php artisan admin:recover --key=<key>` | Recover with explicit key |
| `php artisan admin:recovery-path` | Show recovery key file location |
| `php artisan admin:recovery-show` | Display stored recovery key |
| `php artisan admin:create` | Create super admin (no existing admin) |

### 3.4 Queue Workers

| Command | Description |
| ------- | ----------- |
| `php artisan queue:work --queue=default` | Process emails, alerts, notifications |
| `php artisan queue:work --queue=documents` | Process PDF certificates, reports |
| `php artisan queue:restart` | Gracefully restart all workers |

### 3.5 Database Diagnostics

| Command | Description |
| ------- | ----------- |
| `php artisan migrate:status` | Migration status |
| `php artisan db:show` | Database info |
| `php artisan db:table` | Table details |
| `php artisan about` | Full system information |

---

## 4. Routine Maintenance

### Daily

No daily maintenance required. The system manages itself via scheduler.

### Weekly

```bash
php artisan system:health
php artisan queue:failed
```

### Monthly

```bash
php artisan system:cleanup
df -h
ls -lh storage/logs/
```

### After Each Deployment

```bash
php artisan migrate --force
npm install && npm run build
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

---

## 5. Troubleshooting Matrix

### 5.1 Application Access

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| Blank white page | Storage not writable | `chmod -R 775 storage bootstrap/cache` |
| "419 Page Expired" | Session issue | Clear cookies; check `SESSION_DRIVER` |
| "503 Service Unavailable" | Maintenance mode | `php artisan up` |
| 404 on all pages | Web server misconfigured | Check Nginx/Apache document root |
| "Whoops" after config change | Cached config stale | `php artisan optimize:clear` |

### 5.2 File Uploads & Media

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| Images not showing | Storage link missing | `php artisan storage:link` |
| File upload fails | PHP limits too low | Increase `upload_max_filesize`, `post_max_size` |
| "File too large" | Exceeds `upload_max_filesize` | Increase in `php.ini` (default: 64MB for media) |
| "Invalid file type" | MIME not allowed | Check `registerMediaCollections()` in model |
| 413 Request Entity Too Large | Nginx/Apache limit | Increase `client_max_body_size` (Nginx) |
| PDF preview not working | Missing extension | Install `ext-imagick` or `ext-gd` |

### 5.3 Database

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| "Database is locked" | SQLite concurrent writes | Switch to MySQL/PostgreSQL |
| "Connection refused" | DB server not running | `systemctl status mysql` |
| "Table not found" | Migrations not run | `php artisan migrate --force` |
| "Table already exists" | Partial migration | `php artisan migrate --force` (continues from last batch) |
| "Column already exists" | Migration conflict | Check for duplicate migration files |
| "Foreign key constraint fails" | Data dependency | Ensure related tables migrate first |
| "Syntax error" | Wrong DB engine | Check `DB_CONNECTION` matches your database |

#### SQLite-Specific

| Issue | Resolution |
| ----- | ---------- |
| "database is locked" | Enable WAL mode: `PRAGMA journal_mode=WAL;` |
| Read-only database | `chmod 664 database/database.sqlite` |
| Concurrent write contention | Switch to MySQL/MariaDB |
| File not found | `touch database/database.sqlite && php artisan migrate --force` |

### 5.4 Queue & Jobs

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| Emails not sending | Queue worker not running | `php artisan queue:work --queue=default` |
| PDF not generating | Documents worker not running | `php artisan queue:work --queue=documents` |
| Jobs failing silently | Check failed jobs | `php artisan queue:failed` |
| "Maximum execution time exceeded" | Long operation | Increase `max_execution_time` or move to queue |
| Queue not processing | `QUEUE_CONNECTION=sync` | Set to `redis` or `database` for workers |

### 5.5 Performance

| Symptom | Diagnosis | Resolution |
| ------- | --------- | ---------- |
| Pages load slowly | Cache not warmed | `php artisan optimize` |
| "Vite manifest not found" | Assets not built | `npm run build` |
| High memory usage | `memory_limit` too low | Set `memory_limit = 256M` in `php.ini` |
| N+1 queries | Check Laravel Debugbar | Add `->with()` for relationships |
| Slow queries | MySQL `slow_query_log` | Add database indexes |

### 5.6 Authentication

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| "Account locked" | 10 failed attempts | Wait for auto-unlock or admin unlock |
| "Account inactive" | Disabled by admin | Contact school administrator |
| "Invalid credentials" | Wrong email/password | Use "Forgot Password" flow |
| Redirect loop after login | Session/cache issue | Clear cookies + `php artisan optimize:clear` |
| Users logged out randomly | Session driver mismatch | Switch to `database` or `redis` driver |

#### Rate Limits

| Endpoint | Limit | Reset |
| -------- | ----- | ----- |
| Login | 5 per 60s | Wait 60s |
| Forgot password | 3 per 3600s | Wait 1h |
| Password reset | 5 per 300s | Wait 5min |
| Account recovery | 3 per 300s | Wait 5min |

### 5.7 Email

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| Timeout on send | SMTP unreachable | Check `MAIL_HOST` / `MAIL_PORT` |
| Authentication failed | Wrong credentials | Verify `MAIL_USERNAME` / `MAIL_PASSWORD` |
| "Connection refused" | SMTP port blocked | Try 587 (TLS) or 465 (SSL) |
| Emails in spam | Missing SPF/DKIM | Configure SPF, DKIM, DMARC DNS records |

### 5.8 Cache & Session

| Symptom | Cause | Resolution |
| ------- | ----- | ---------- |
| Users randomly logged out | Session lifetime too short | Increase `SESSION_LIFETIME` |
| Cookie domain mismatch | Wrong `SESSION_DOMAIN` | Set to match `APP_URL` |
| Redis connection lost | Redis service down | `systemctl status redis` |

---

## 6. Known Issues

| Issue | Status | Workaround |
| ----- | ------ | ---------- |
| SQLite concurrent write lock under heavy load | Open | Switch to MySQL/MariaDB |
| PHP memory limit for large batch certificate generation | Open | Process in smaller batches |
| PDF generation timeout on shared hosting | Open | Reduce template complexity or upgrade to VPS |

---

## 7. Diagnostic Commands

```bash
php artisan system:health              # Quick health check
php artisan about                      # Full system information
php artisan pail                       # Tail logs (dev)
tail -f storage/logs/laravel.log       # Tail logs (prod)
php artisan migrate:status             # Migration status
php artisan db:show                    # Database info
php artisan queue:failed               # List failed jobs
php artisan queue:monitor default,documents  # Queue sizes
php artisan cache:clear                # Clear cache store
php artisan config:clear               # Clear config cache
```

---

## 8. Getting Help

1. Check [GitHub Issues](https://github.com/reasvyn/internara/issues) for known problems
2. Search or post in [Discussions](https://github.com/reasvyn/internara/discussions)
3. Include in reports: `php artisan system:health --json` output, log excerpts, steps to reproduce

---

## Quick References

- `app/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php` — Health check implementation
- `app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php` — Cleanup implementation
- `app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php` — Cache warming
- `config/logging.php` — Log channel configuration
- `storage/logs/laravel.log` — Application log file
- [System Observability](system-observability.md) — Pulse, audit logs, backups
- [Upgrading](upgrading.md) — Upgrade and rollback procedures
