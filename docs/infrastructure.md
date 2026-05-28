# Infrastructure
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


This document describes the ideal infrastructure design for Internara — what the system
looks like at each deployment tier and how components relate. It serves as a reference for
provisioning, scaling, and maintenance.

> This is the **target architecture**, not necessarily what is currently implemented.
> The codebase supports all tiers simultaneously through configuration.

---

## 1. Three Deployment Tiers

Internara is a self-hosted, single-tenant application. Schools install it on their own
infrastructure, ranging from budget shared hosting to dedicated multi-server setups.
The same codebase runs across all tiers — only configuration differs.

### Tier 1: Entry (Shared Hosting)

```
┌─────────────────────────────────────────┐
│  Budget-friendly, zero-devops           │
│  Target: small schools (< 50 users)     │
├─────────────────────────────────────────┤
│  Web:     Apache / Nginx (cPanel)       │
│  PHP:     8.4 FPM                       │
│  DB:      MySQL / MariaDB (shared)      │
│  Queue:   sync (inline)                 │
│  Cache:   file / database               │
│  Session: file / database               │
│  Mail:    SMTP                          │
│  Storage: local (disk)                  │
│  Reverb:  ❌ (pull-to-refresh)          │
│  Cron:    via /cron/{secret} webhook    │
├─────────────────────────────────────────┤
│  Est. cost: $3-15/month                 │
└─────────────────────────────────────────┘
```

### Tier 2: Standard (VPS / Dedicated Server)

```
┌─────────────────────────────────────────┐
│  Full-featured, single-server           │
│  Target: medium schools (50-200 users)  │
├─────────────────────────────────────────┤
│  Web:     Nginx + HTTPS (Let's Encrypt) │
│  PHP:     8.4 FPM + OpCache             │
│  DB:      MySQL 8 / PostgreSQL 14+      │
│  Queue:   Redis                         │
│  Cache:   Redis                         │
│  Session: Redis                         │
│  Mail:    SMTP / Mailgun / SES          │
│  Storage: local + backup to S3          │
│  Reverb:  optional (real-time notif)    │
│  Cron:    minutely (system cron)        │
│  Process: Supervisor (queue + reverb)   │
├─────────────────────────────────────────┤
│  Est. cost: $20-80/month                │
└─────────────────────────────────────────┘
```

### Tier 3: High-Availability (Multi-Server)

```
┌─────────────────────────────────────────┐
│  Scalable, redundant                    │
│  Target: large schools (200+ users)     │
├─────────────────────────────────────────┤
│  Web:     Nginx (2+ app servers)        │
│  PHP:     8.4 FPM pool (per server)     │
│  DB:      MySQL 8 + read replica        │
│  Queue:   Redis cluster                 │
│  Cache:   Redis cluster                 │
│  Session: Redis cluster                 │
│  Mail:    SES / SMTP relay              │
│  Storage: S3 / Cloudflare R2            │
│  Reverb:  dedicated server              │
│  Cron:    single server (system cron)   │
│  Process: Supervisor on each server     │
├─────────────────────────────────────────┤
│  Est. cost: $100-500/month              │
└─────────────────────────────────────────┘
```

### Feature Availability by Tier

| Feature | Tier 1 | Tier 2 | Tier 3 |
|---|---|---|---|
| Authentication & RBAC | ✅ | ✅ | ✅ |
| Attendance, Logbook | ✅ | ✅ | ✅ |
| Assignments, Grading | ✅ | ✅ | ✅ |
| Reports, Certificates | ✅ | ✅ | ✅ |
| Email notifications | ✅ (sync) | ✅ (async) | ✅ (async) |
| Media conversions | ✅ (sync) | ✅ (async) | ✅ (async) |
| In-app notifications | ✅ (pull) | ✅ (pull) | ✅ (real-time) |
| Pulse monitoring | ✅ (request) | ✅ (request) | ✅ (request) |
| Concurrent users | < 50 | 50-200 | 200-1000+ |

---

## 2. Service Architecture & Data Flow

```
Internet
    │
    ▼
┌─────────────┐    ┌──────────────┐
│   Nginx     │◄───│  HTTPS       │
│  (proxy)    │    │  Let's       │
│             │    │  Encrypt     │
└──────┬──────┘    └──────────────┘
       │
       ▼
┌─────────────┐    ┌──────────────────┐
│  PHP-FPM    │───►│  Storage         │
│  (app)      │    │  (local / S3)    │
│             │    │  ├─ avatars/      │
│             │    │  ├─ documents/    │
│             │    │  └─ certificates/ │
└──────┬──────┘    └──────────────────┘
       │
       ├─────────────────┬──────────────────┬─────────────────┐
       ▼                 ▼                  ▼                 ▼
┌────────────┐  ┌────────────┐  ┌──────────────┐  ┌──────────────┐
│ Database   │  │   Redis    │  │ Queue Worker │  │   Reverb     │
│ MySQL / PG │  │  cache /   │  │  (async)     │  │  WebSocket   │
│ SQLite*    │  │  session   │  │              │  │  (optional)  │
└────────────┘  └────────────┘  └──────────────┘  └──────────────┘

* SQLite only in Tier 1 / development / testing
```

---

## 3. Process Management

Three background processes must be running in production (Tier 2+). In Tier 1,
queue runs synchronously, and the scheduler is triggered via HTTP webhook.

| Process | Command | Tier 1 | Tier 2+ | Purpose |
|---|---|---|---|---|
| **Queue Worker** | `queue:work --sleep=3 --tries=3` | sync (inline) | Supervisor | Notifications, media conversions, mail |
| **Scheduler** | `schedule:run` | `/cron/{secret}` | system cron | Daily cleanup, cache warm, Pulse recording |
| **Reverb** | `reverb:start` | ❌ | Supervisor | Real-time WebSocket notifications |

### Supervisor Configuration (Tier 2+)

`/etc/supervisor/conf.d/internara-worker.conf`:
```ini
[program:internara-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log
stopwaitsecs=3600
```

### Cron Entry (Tier 2+)

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Web Cron Fallback (Tier 1)

For shared hosting without minute-level cron, a web-accessible endpoint
triggers the scheduler:

```cron
* * * * * curl -s https://your-domain.com/cron/your-cron-secret-here
```

Generate the secret: `php -r "echo bin2hex(random_bytes(16));"`

---

## 4. Database Strategy

### Default by Tier

| Tier | Engine | Configuration |
|---|---|---|
| Development | SQLite (file) | Zero config, single file |
| Testing | SQLite (in-memory) | Fast, auto-discarded |
| Tier 1 | MySQL / MariaDB | Shared hosting MySQL (limited connections) |
| Tier 2+ | MySQL 8 / PostgreSQL 14+ | Dedicated, tuned |

### Connection Pooling

| Engine | Tool | When |
|---|---|---|
| MySQL | ProxySQL | > 500 concurrent connections |
| PostgreSQL | PgBouncer | > 200 concurrent connections |

### Read/Write Separation (Tier 3)

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['replica1.host', 'replica2.host'],
    ],
    'write' => [
        'host' => ['primary.host'],
    ],
],
```

### Migration Strategy

```bash
# Switch engine
php artisan migrate

# When migrating from SQLite to MySQL with existing data:
# 1. Export SQLite data
# 2. Configure MySQL in .env
# 3. Run migrations
# 4. Import data
```

---

## 5. Storage Strategy

### Disk Definitions

| Disk | Driver | Default | Purpose | Web-Accessible |
|---|---|---|---|---|
| `local` | Local | `storage/app/private` | Internal files, exports | ❌ |
| `public` | Local | `storage/app/public` | User-facing files | ✅ (via symlink) |
| `s3` | S3 | Bucket root | Production cloud storage | ✅ (via CDN) |

### Storage by Tier

```
Tier 1:
  └─ Local disk → storage/app/public → symlinked to public/storage/

Tier 2:
  ├─ Local disk for active files
  └─ Periodic rsync / s3cmd to S3 for backup

Tier 3:
  └─ S3 (or compatible) as primary storage
      ├─ AWS S3
      ├─ MinIO (self-hosted)
      ├─ DigitalOcean Spaces
      └─ Cloudflare R2
```

### What Gets Stored Where

| Data | Tier 1-2 | Tier 3 | Via |
|---|---|---|---|
| User avatars | `public` disk / media library | S3 / media library | `$user->getFirstMediaUrl('avatar')` |
| Uploaded documents | `public` disk / media library | S3 / media library | `$doc->getFirstMediaUrl('file')` |
| Certificate PDFs | `public/certificates/` directory | S3 `certificates/` | Direct file path |
| Brand assets | `public/brand/` directory | S3 `brand/` | `brand('logo')` helper |
| Temporary uploads | `local` disk (Livewire temp) | `local` disk | Auto-cleaned |

### Storage Link

```bash
php artisan storage:link
# Creates: public/storage → storage/app/public
```

---

## 6. Caching & Session Layer

### Driver by Tier

| Service | Tier 1 | Tier 2 | Tier 3 |
|---|---|---|---|
| Cache | `file` / `database` | `redis` | `redis` (cluster) |
| Session | `file` / `database` | `redis` | `redis` (cluster) |
| Queue | `sync` | `redis` | `redis` (cluster) |

### Redis Database Separation

A single Redis instance can serve all three services by using separate database
numbers:

```env
REDIS_DB=0        # Cache
REDIS_CACHE_DB=1  # Cache fallback (if different from above)
REDIS_QUEUE_DB=2  # Queue
# Session uses CACHE_STORE driver by default
```

### Cache vs Session (Critical Distinction)

| Aspect | Cache | Session |
|---|---|---|
| Purpose | Performance optimization | Authentication + ephemeral state |
| Security | No restrictions | Encrypted, HTTP-only, SameSite |
| Data loss | Acceptable (recomputable) | Critical (user logged out) |
| TTL | Per-key (seconds to forever) | Single global lifetime (120 min) |
| Backend | Swappable independently | Tied to security assumptions |

---

## 7. Monitoring & Observability Stack

```
┌───────────────────────────────────────────────────────┐
│                   Observability Stack                  │
├───────────────────────────────────────────────────────┤
│  Laravel Pulse    → Slow queries, requests, jobs,      │
│                     exceptions, cache, queue throughput │
│  SmartLogger      → Business audit (activity_log)      │
│  Log files        → System errors (daily rotation)      │
│  system:health    → 15-point verification (CLI + JSON) │
│  system:cleanup   → Prune stale data (nightly cron)    │
└───────────────────────────────────────────────────────┘
```

### Retention

| Data Source | Retention | Pruning |
|---|---|---|
| Pulse records | 7 days | Automatic by scheduler |
| Activity log | 365 days | `php artisan activitylog:clean` via scheduler |
| System logs | 14 days | `daily` log driver rotation |
| Failed jobs | 7 days | `queue:prune-failed` via scheduler |

---

## 8. Backup & DR

### Recovery Objectives

| Metric | Tier 1 | Tier 2 | Tier 3 |
|---|---|---|---|
| RPO (data loss tolerance) | 24 hours | 24 hours | 1 hour |
| RTO (restoration time) | 4 hours | 2 hours | 30 minutes |

### Backup Schedule

```
02:00 daily  ── Database dump (mysqldump / pg_dump)
03:00 daily  ── File archive (tar.gz of storage/)
04:00 daily  ── Push offsite (rsync to S3 / backup server)
```

### Retention Policy

| Frequency | Retention |
|---|---|
| Daily | 30 days |
| Weekly | 12 weeks |
| Monthly | 12 months |
| Yearly | Permanent (regulatory) |

### What to Restore (in order)

1. **Database** — restore from dump: `mysql -u internara -p internara < backup.sql`
2. **Files** — restore storage archive: `tar -xzf backup.tar.gz -C storage/`
3. **Environment** — restore `.env` from secure storage
4. **Caches** — rebuild: `php artisan optimize`

---

## 9. Scaling Guide

### When to Scale What

| Symptom | Scale | Action |
|---|---|---|
| PHP-FPM max children reached | Vertical (app) | Increase RAM, raise `pm.max_children` |
| Database CPU > 80% | Vertical (DB) | Larger DB server, add read replica |
| SQLite "database is locked" | Engine | Switch to MySQL / PostgreSQL |
| Queue backlog growing | Worker | Add Supervisor `numprocs`, switch to Redis |
| Disk > 85% full | Storage | Add storage, enable S3, prune old data |
| Reverb connections > 1000 | Horizontal | Dedicated Reverb server, load balance |
| Page load > 500ms | Cache | Enable Redis, warm caches, add OpCache |

### Vertical vs Horizontal

| Tier | Vertical (bigger server) | Horizontal (more servers) |
|---|---|---|
| 1→2 | ✅ Sufficient for < 200 users | ❌ Not needed |
| 2→3 | ❌ Diminishing returns | ✅ Required for redundancy |
| 3+ | ✅ Database only | ✅ App + queue + reverb |

---

## 10. Security Posture

### Network

- HTTPS only (Let's Encrypt / Cloudflare)
- Firewall: allow ports 80, 443, (8080 for Reverb if external)
- Fail2ban for SSH
- CSP, HSTS, X-Frame-Options headers applied by middleware

### Application

- Rate limiting on auth endpoints (login: 5/60s, forgot password: 3/3600s)
- Account lockout after 10 failed attempts
- Session: HTTP-only, SameSite=Lax, Secure in production
- CSRF: built-in via Laravel, auto-handled by Livewire

### Maintenance

- OS updates: unattended-upgrades (security patches only)
- PHP: minor version auto (8.4.x)
- Laravel: `composer update` (manual, tested before deploy)
- Frontend: `npm update && npm run build` (manual)

---

## 11. Component Sizing Reference

| Component | Tier 1 | Tier 2 | Tier 3 |
|---|---|---|---|
| **CPU** | 1-2 shared | 2-4 dedicated | 2-4 per app server, 4-8 DB |
| **RAM** | 256 MB - 512 MB | 4 GB | 4 GB per app, 16 GB DB |
| **Storage** | 5-10 GB | 50 GB SSD | 100 GB SSD + S3 |
| **PHP-FPM children** | 5-10 | 25 | 25-50 per server |
| **Database buffer** | Shared host limit | 2 GB (MySQL) | 8 GB (MySQL) |
| **Redis memory** | N/A | 512 MB | 2 GB (cluster) |
| **Bandwidth** | 1 TB/mo | 2 TB/mo | 5 TB/mo |

---

## 12. Production Readiness Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `APP_KEY` set to random 32-char base64 string
- [ ] Database migrated: `php artisan migrate --force`
- [ ] Public storage link: `php artisan storage:link`
- [ ] Queue worker running (Supervisor or systemd)
- [ ] Scheduler cron entry configured
- [ ] OpCache enabled and configured
- [ ] All caches warmed: `php artisan optimize`
- [ ] Frontend assets built: `npm run build`
- [ ] HTTPS configured and enforced
- [ ] `php artisan system:health` passes with no FAIL
- [ ] Backup automation configured
- [ ] Monitoring set up (Pulse, log retention)
- [ ] Fail2ban for SSH access
- [ ] Regular update schedule documented

---

## Where to Find It

| Concern | Document |
|---|---|
| Deployment steps (VPS, Docker, shared) | [Deployment](deployment.md) |
| Environment configuration | [Configuration](configuration.md) |
| Backup & restore procedures | [Backup & Recovery](backup-recovery.md) |
| Performance monitoring, Pulse, logging | [Observability](observability.md) |
| File storage, S3, media library | [Filesystem](filesystem.md) |
| Queue infrastructure, worker management | [Queue](queue.md) |
| Cache management, OpCache | [Cache](cache.md) |
| Session configuration, security | [Session](session.md) |
