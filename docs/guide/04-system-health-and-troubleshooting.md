# Chapter 4: System Health & Troubleshooting

> **Last updated:** 2026-06-14

This chapter helps you monitor your Internara installation, diagnose problems, and perform routine
maintenance. You don't need to read it cover to cover — use it as a reference when something goes
wrong.

---

## 4.1 Quick Health Check

Run this command anytime to check if your system is healthy:

```bash
php artisan system:health
```

You'll see a table like this:

```
 ┌──────────────────────────────┬──────────┬────────────────────────────────────┐
 │ Service                      │ Status   │ Details                            │
 ├──────────────────────────────┼──────────┼────────────────────────────────────┤
 │ Environment                  │ OK       │ .env file detected                 │
 │ Setup Status                 │ OK       │ System installed (6 steps)         │
 │ PHP Version                  │ OK       │ PHP 8.4.5 (required: 8.4.0+)      │
 │ Extensions                   │ OK       │ All 12 required extensions loaded  │
 │ Recommended Extensions       │ WARN     │ Missing: redis, pcntl, posix       │
 │ PHP Memory Limit             │ OK       │ 256M (minimum 128M met)            │
 │ Database                     │ OK       │ mysql — connected, 54 tables       │
 │ Migration Status             │ OK       │ All 48 migrations up to date       │
 │ Storage Permissions          │ OK       │ All storage directories writable   │
 │ Disk Space                   │ OK       │ Disk 23% full                      │
 │ Queue Driver                 │ WARN     │ sync (no worker needed)            │
 │ Cache Driver                 │ OK       │ file                                │
 │ App Key                      │ OK       │ APP_KEY is set                      │
 │ Storage Link                 │ OK       │ storage link exists                │
 │ Maintenance Mode             │ OK       │ Application is live                │
 └──────────────────────────────┴──────────┴────────────────────────────────────┘
```

### Reading the Results

| Icon | Meaning | What to Do |
|---|---|---|
| **OK** | Everything is fine | Nothing |
| **WARN** | Not critical, but could be improved | Optional — see notes below |
| **FAIL** | Something is broken | Fix it before continuing |

For JSON output (useful for monitoring tools):

```bash
php artisan system:health --json
```

---

## 4.2 Common Problems & Solutions

### Cannot Access the Application

| Symptom | Cause | Solution |
|---|---|---|
| Blank white page | Storage directory not writable | `chmod -R 775 storage bootstrap/cache` |
| "419 Page Expired" | Session issue | Clear cookies, or check `SESSION_DRIVER` in `.env` |
| "503 Service Unavailable" | Maintenance mode is on | `php artisan up` |
| 404 on all pages | Web server not configured correctly | Check your web server (Nginx/Apache) configuration |

### File Uploads & Media

| Symptom | Cause | Solution |
|---|---|---|
| Images not showing | Storage link missing | `php artisan storage:link` |
| File upload fails | PHP upload limits too low | Increase `upload_max_filesize` and `post_max_size` in `php.ini` |
| PDF preview not working | Missing `ext-imagick` or `ext-gd` | Install the required PHP extension |

### Performance

| Symptom | Cause | Solution |
|---|---|---|
| Pages load slowly | Caches not enabled | `php artisan optimize` (production only) |
| "Vite manifest not found" | Frontend assets not built | `npm run build` |
| High memory usage | PHP memory limit too low | Set `memory_limit = 256M` in `php.ini` |

### Database

| Symptom | Cause | Solution |
|---|---|---|
| "Database is locked" | Using SQLite with concurrent users | Switch to MySQL or PostgreSQL |
| "Connection refused" | Database server not running | Start your database service |
| "Table not found" | Migrations not run | `php artisan migrate --force` |

### Queue & Background Jobs

| Symptom | Cause | Solution |
|---|---|---|
| Emails not sending | Queue worker not running | Start worker: `php artisan queue:work --queue=default` |
| PDF certificates not generating | Documents worker not running | Start worker: `php artisan queue:work --queue=documents` |
| Jobs failing silently | Check failed jobs | `php artisan queue:failed` |

### Setup & Installation

| Symptom | Cause | Solution |
|---|---|---|
| Setup URL shows 403 | Token invalid or expired | `php artisan setup:reset-token` |
| Setup URL shows 404 | Already installed | `php artisan setup:install --force` to reset |
| "Class not found" | Dependencies not installed | `composer install --no-interaction` |
| "APP_KEY not set" | Key not generated | `php artisan key:generate` |

---

## 4.3 Routine Maintenance

### Daily

No daily maintenance required. The system manages itself.

### Weekly

```bash
# Check system health
php artisan system:health

# Check for any failed jobs
php artisan queue:failed
```

### Monthly

```bash
# Clean up old data (Pulse, activity logs, stale jobs)
php artisan system:cleanup

# Check disk space
df -h

# Review application logs
ls -lh storage/logs/
```

### After Each Deployment

```bash
# Run new migrations
php artisan migrate --force

# Rebuild frontend assets
npm install && npm run build

# Clear and re-cache for production
php artisan optimize
```

---

## 4.4 Useful Commands Reference

### Monitoring

| Command | What It Does |
|---|---|
| `php artisan system:health` | 15-point system health check |
| `php artisan system:health --json` | Same, but machine-readable JSON |
| `php artisan system:cache-warm` | Pre-load settings and brand values for faster response |
| `php artisan system:cleanup` | Prune stale data (Pulse, activity logs, failed jobs) |
| `php artisan queue:failed` | List failed queue jobs |
| `php artisan queue:monitor` | Monitor queue sizes |

### Cache Management

| Command | What It Does |
|---|---|
| `php artisan optimize` | Cache everything (config, routes, views, events) — **production only** |
| `php artisan optimize:clear` | Clear all caches |
| `php artisan cache:clear` | Clear the application cache only |
| `php artisan view:cache` | Compile Blade templates |
| `php artisan view:clear` | Clear compiled templates |

### Setup & Recovery

| Command | What It Does |
|---|---|
| `php artisan setup:install` | Run installer and generate setup URL |
| `php artisan setup:install --force` | Full reset — wipes database and starts fresh |
| `php artisan setup:install --check-only` | Run environment audit without installing |
| `php artisan setup:reset-token` | Generate a new setup token (before installation only) |
| `php artisan admin:recover` | Recover super admin access using stored key |
| `php artisan admin:recovery-path` | Show where the recovery key is stored |
| `php artisan admin:recovery-show` | Display the stored recovery key |
| `php artisan admin:create` | Create a new admin account from the command line |

### Queue Workers

| Command | What It Does |
|---|---|
| `php artisan queue:work --queue=default` | Process emails, alerts, notifications |
| `php artisan queue:work --queue=documents` | Process PDF certificates, reports |
| `php artisan queue:restart` | Gracefully restart all workers after deployment |

---

## 4.5 Logs & Debugging

### Application Logs

Logs are located in `storage/logs/`. The main log file is `laravel.log`:

```bash
# View the latest log entries
tail -50 storage/logs/laravel.log

# Watch logs in real time
tail -f storage/logs/laravel.log
```

### Browser Developer Tools

For frontend issues, open your browser's developer tools (F12) and check:

- **Console tab** — JavaScript errors
- **Network tab** — failed API requests (look for 4xx/5xx responses)
- **Livewire tab** (if installed) — Livewire component errors

### Getting Help

When reporting an issue, include:

1. Output of `php artisan system:health --json`
2. Relevant log entries from `storage/logs/laravel.log`
3. Steps to reproduce the problem
4. Your environment: PHP version, database type, web server

---

## 4.6 Proactive Monitoring

For production installations, consider:

- **Scheduled health checks** — add `php artisan system:health` to your cron every 5 minutes
- **Disk space alerts** — monitor disk usage; the health check warns at 85% and fails at 95%
- **Failed job alerts** — check `php artisan queue:failed` daily
- **Laravel Pulse** — built-in monitoring dashboard at `/pulse` (requires authentication)

---

---

**← Previous:** [Chapter 3: Post-Setup](03-post-setup.md)
**Next →** [Back to Manual Index](00-guide-index.md)
