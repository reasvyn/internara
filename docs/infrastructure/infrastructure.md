# Infrastructure — System Architecture Overview

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

This document describes the infrastructure design for Internara — what the system looks like at each
deployment tier and how components relate. It serves as a reference for provisioning, scaling, and
maintenance.

The same codebase runs across all tiers; only configuration differs.

---

## 1. Three Deployment Tiers

Internara uses the same codebase across all tiers — only configuration changes. Tiers reflect
infrastructure capacity, not feature editions. All features work at every tier; some run
synchronously instead of asynchronously on shared hosting.

### Tier 1: Shared Hosting (Primary — Recommended Start)

The recommended starting point for most schools. Handles up to **500 registered users per PKL
period** (~50–100 peak concurrent) with no external services beyond MySQL/MariaDB and SMTP.

```
+-------------------------------------------------+
|  Budget-friendly, minimal devops                 |
|  Target: <= 500 registered users /              |
|  PKL period (~50-100 concurrent)                |
+-------------------------------------------------+
|  Web:     Apache / Nginx (cPanel)               |
|  PHP:     8.4 FPM                               |
|  DB:      MySQL / MariaDB (shared)              |
|  Queue:   sync (inline)                         |
|  Cache:   file / database                       |
|  Session: database                              |
|  Mail:    SMTP                                  |
|  Storage: local (disk)                          |
|  Reverb:  (pull-to-refresh)                     |
|  Cron:    via /cron/{secret} webhook            |
+-------------------------------------------------+
|  Est. cost: $3-15/month                         |
+-------------------------------------------------+
```

### Tier 2: VPS / Dedicated Server

When the school outgrows shared hosting or needs async queue workers, real-time updates, and
dedicated resources.

```
+-------------------------------------------------+
|  Full-featured, single-server                   |
|  Target: 500-2000 registered users              |
+-------------------------------------------------+
|  Web:     Nginx + HTTPS (Let's Encrypt)         |
|  PHP:     8.4 FPM + OpCache                     |
|  DB:      MySQL 8 / PostgreSQL 14+              |
|  Queue:   Redis (dual pipelines)                |
|  Cache:   Redis                                 |
|  Session: Redis                                 |
|  Mail:    SMTP / Mailgun / SES                  |
|  Storage: local + backup to S3                  |
|  Cron:    minutely (system cron)                |
|  Process: Supervisor (queue worker)             |
+-------------------------------------------------+
|  Est. cost: $20-80/month                        |
+-------------------------------------------------+
```

### Tier 3: High-Availability (Multi-Server)

For large institutions requiring redundancy, read replicas, and horizontal scaling.

```
+-------------------------------------------------+
|  Scalable, redundant                            |
|  Target: 2000+ registered users                 |
+-------------------------------------------------+
|  Web:     Nginx (2+ app servers)                |
|  PHP:     8.4 FPM pool (per server)             |
|  DB:      MySQL 8 + read replica                |
|  Queue:   Redis cluster (dual pipelines)        |
|  Cache:   Redis cluster                         |
|  Session: Redis cluster                         |
|  Mail:    SES / SMTP relay                      |
|  Storage: S3 / Cloudflare R2                    |
|  Cron:    single server (system cron)           |
|  Process: Supervisor on each server             |
+-------------------------------------------------+
|  Est. cost: $100-500/month                      |
+-------------------------------------------------+
```

### Feature Availability by Tier

All features are available at every tier. The difference is whether certain operations run
synchronously (inline during the HTTP request) or asynchronously (via queue workers).

| Feature                        | Tier 1 (Shared) | Tier 2 (VPS)  | Tier 3 (HA)     |
| ------------------------------ | --------------- | ------------- | --------------- |
| Authentication & RBAC          | Yes             | Yes           | Yes             |
| Journals (Attendance, Logbook) | Yes             | Yes           | Yes             |
| Assignments & Grading          | Yes             | Yes           | Yes             |
| Reports & Certificates         | Yes             | Yes           | Yes             |
| Email notifications            | Yes (sync)      | Yes (async)   | Yes (async)     |
| Media conversions              | Yes (sync)      | Yes (async)   | Yes (async)     |
| In-app notifications           | Yes (pull)      | Yes (pull)    | Yes (real-time) |
| Pulse monitoring               | Yes (request)   | Yes (request) | Yes (request)   |
| Registered users per PKL       | <= 500          | 500-2000      | 2000+           |

---

## 2. Service Architecture & Data Flow

```
Internet
    |
    v
+-------------+    +--------------+
|   Nginx     |<---|  HTTPS       |
|  (proxy)    |    |  Let's       |
|             |    |  Encrypt     |
+------+------+    +--------------+
       |
       v
+-------------+    +------------------+
|  PHP-FPM    |--->|  Storage         |
|  (app)      |    |  (local / S3)    |
|             |    |  +- avatars/     |
|             |    |  +- documents/   |
|             |    |  +- certificates/|
+------+------+    +------------------+
       |
       +-----------------+------------------+-----------------+
       v                 v                  v                 v
+------------+  +------------+  +--------------+  +--------------+
| Database   |  |   Redis    |  | Queue Worker |  | Queue Worker |
| MySQL / PG |  |  cache /   |  |  (default)   |  |  (documents) |
| SQLite*    |  |  session   |  |  emails,     |  |  PDF certs,  |
+------------+  +------------+  |  alerts      |  |  reports     |
                                +--------------+  +--------------+

* SQLite only in development / testing, never in production
```

---

## 3. Process Management

### Background Processes by Tier

| Process                      | Command                        | Tier 1 (Shared)  | Tier 2+ (VPS) | Purpose                                    |
| ---------------------------- | ------------------------------ | ---------------- | ------------- | ------------------------------------------ |
| **Queue Worker (default)**   | `queue:work --queue=default`   | sync (inline)    | Supervisor    | Emails, alerts, notifications              |
| **Queue Worker (documents)** | `queue:work --queue=documents` | sync (inline)    | Supervisor    | PDF certificates, reports                  |
| **Scheduler**                | `schedule:run`                 | `/cron/{secret}` | system cron   | Daily cleanup, cache warm, Pulse recording |

### Dual Pipeline Supervisor Configuration (Tier 2+)

Two separate queue pipelines prevent document compilation from blocking notification delivery:

- **`default` queue**: Processes emails, alerts, and general events.
- **`documents` queue**: Dedicated exclusively to compiling PDF certificates and reports.

`/etc/supervisor/conf.d/internara-worker.conf`:

```ini
[program:internara-default-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/default-worker.log
stopwaitsecs=3600

[program:internara-documents-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --queue=documents --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/documents-worker.log
stopwaitsecs=3600
```

### Cron Entry (Tier 2+)

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Web Cron Fallback (Tier 1)

For shared hosting without minute-level cron, a web-accessible endpoint triggers the scheduler:

```cron
* * * * * curl -s https://your-school.sch.id/cron/your-cron-secret-here
```

Generate the secret: `php -r "echo bin2hex(random_bytes(16));"`

---

## 4. Database Strategy

### Default by Tier

| Tier        | Engine                   | Configuration                              |
| ----------- | ------------------------ | ------------------------------------------ |
| Development | SQLite (file)            | Zero config, single file                   |
| Testing     | SQLite (in-memory)       | Fast, auto-discarded                       |
| Tier 1      | MySQL / MariaDB          | Shared hosting MySQL (limited connections) |
| Tier 2+     | MySQL 8 / PostgreSQL 14+ | Dedicated, tuned                           |

All tables use UUID v7 primary keys via Laravel's `HasUuids` trait. The `BaseModel` automatically
provides this; `User` applies it manually since it extends `Authenticatable`.

### Connection Pooling

| Engine     | Tool      | When                         |
| ---------- | --------- | ---------------------------- |
| MySQL      | ProxySQL  | > 500 concurrent connections |
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
# Run migrations on current connection
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

| Disk     | Driver | Default               | Purpose                  | Web-Accessible    |
| -------- | ------ | --------------------- | ------------------------ | ----------------- |
| `local`  | Local  | `storage/app/private` | Internal files, exports  | No                |
| `public` | Local  | `storage/app/public`  | User-facing files        | Yes (via symlink) |
| `s3`     | S3     | Bucket root           | Production cloud storage | Yes (via CDN)     |

### Storage by Tier

```
Tier 1 (Shared Hosting):
  +- Local disk -> storage/app/public -> symlinked to public/storage/

Tier 2 (VPS):
  +- Local disk for active files
  +- Periodic sync to S3 for backup

Tier 3 (Multi-Server):
  +- S3 (or compatible) as primary storage
      +- AWS S3
      +- MinIO (self-hosted)
      +- DigitalOcean Spaces
      +- Cloudflare R2
```

### Storage Link

```bash
php artisan storage:link
# Creates: public/storage -> storage/app/public
```

---

## 6. Caching & Session Layer

### Driver by Tier

| Service | Tier 1 (Shared)     | Tier 2 (VPS) | Tier 3 (HA)       |
| ------- | ------------------- | ------------ | ----------------- |
| Cache   | `file` / `database` | `redis`      | `redis` (cluster) |
| Session | `database`          | `redis`      | `redis` (cluster) |
| Queue   | `sync`              | `redis`      | `redis` (cluster) |

### Redis Database Separation

```env
REDIS_DB=0        # Cache
REDIS_CACHE_DB=1  # Cache fallback (if different from above)
# Session uses SESSION_DRIVER, queue uses its own connection
```

### Cache vs Session (Critical Distinction)

| Aspect    | Cache                        | Session                          |
| --------- | ---------------------------- | -------------------------------- |
| Purpose   | Performance optimization     | Authentication + ephemeral state |
| Security  | No restrictions              | Encrypted, HTTP-only, SameSite   |
| Data loss | Acceptable (recomputable)    | Critical (user logged out)       |
| TTL       | Per-key (seconds to forever) | Single global lifetime (120 min) |
| Backend   | Swappable independently      | Tied to security assumptions     |

---

## 7. Monitoring & Observability Stack

```
+-------------------------------------------------------+
|                   Observability Stack                  |
+-------------------------------------------------------+
|  Laravel Pulse    -> Slow queries, requests, jobs,      |
|                     exceptions, cache, queue throughput |
|  SmartLogger      -> Dual-channel (system + activity)   |
|  Log files        -> System errors (daily rotation)     |
|  system:health    -> 15-point verification (CLI + JSON) |
|  system:cleanup   -> Prune stale data (nightly cron)    |
+-------------------------------------------------------+
```

SmartLogger supports three modes: `both()` (default -- logs to both system and activity channels),
`systemOnly()` (technical operations), and `activityOnly()` (business audit). PII masking
automatically obfuscates `password`, `token`, `secret`, `credit_card` (full); `email`, `phone`,
`name` (partial); IP addresses (first two octets).

### Retention

| Data Source   | Retention | Pruning                            |
| ------------- | --------- | ---------------------------------- |
| Pulse records | 7 days    | Automatic by scheduler             |
| Activity log  | 365 days  | `system:cleanup` via scheduler     |
| System logs   | 14 days   | `daily` log driver rotation        |
| Failed jobs   | 7 days    | `queue:prune-failed` via scheduler |

---

## 8. Backup & DR

### Recovery Objectives

| Metric                    | Tier 1 (Shared) | Tier 2 (VPS) | Tier 3 (HA) |
| ------------------------- | --------------- | ------------ | ----------- |
| RPO (data loss tolerance) | 24 hours        | 24 hours     | 1 hour      |
| RTO (restoration time)    | 4 hours         | 2 hours      | 30 minutes  |

### Backup Schedule

```
02:00 daily  -- Database dump (mysqldump / pg_dump)
03:00 daily  -- File archive (tar.gz of storage/)
04:00 daily  -- Push offsite (rsync to S3 / backup server)
```

### Retention Policy

| Frequency | Retention              |
| --------- | ---------------------- |
| Daily     | 30 days                |
| Weekly    | 12 weeks               |
| Monthly   | 12 months              |
| Yearly    | Permanent (regulatory) |

See [Backup & Recovery](../foundation/backup-recovery.md) for detailed restoration procedures.

---

## 9. Security Posture

### Network

- HTTPS only (Let's Encrypt / Cloudflare)
- Firewall: allow ports 80, 443
- Fail2ban for SSH
- CSP, HSTS, X-Frame-Options headers applied by middleware

### Application

- Rate limiting on auth endpoints (login: 5/60s, forgot password: 3/3600s)
- Account lockout after 10 failed attempts
- Session: HTTP-only, SameSite=Lax, Secure in production
- CSRF: built-in via Laravel, auto-handled by Livewire

---

## 10. Component Sizing Reference

| Component            | Tier 1 (Shared)   | Tier 2 (VPS)  | Tier 3 (HA)                |
| -------------------- | ----------------- | ------------- | -------------------------- |
| **CPU**              | 1-2 shared        | 2-4 dedicated | 2-4 per app server, 4-8 DB |
| **RAM**              | 256 MB - 512 MB   | 4 GB          | 4 GB per app, 16 GB DB     |
| **Storage**          | 5-10 GB           | 50 GB SSD     | 100 GB SSD + S3            |
| **PHP-FPM children** | 5-10              | 25            | 25-50 per server           |
| **Database buffer**  | Shared host limit | 2 GB (MySQL)  | 8 GB (MySQL)               |
| **Redis memory**     | N/A               | 512 MB        | 2 GB (cluster)             |
| **Bandwidth**        | 1 TB/mo           | 2 TB/mo       | 5 TB/mo                    |

---

## 11. Production Readiness Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `APP_KEY` set to random 32-char base64 string
- [ ] Database migrated: `php artisan migrate --force`
- [ ] Public storage link: `php artisan storage:link`
- [ ] Queue workers running (Supervisor with dual pipelines) -- only Tier 2+
- [ ] Scheduler cron entry configured (system cron or webhook)
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

| Concern                                        | Document                                    |
| ---------------------------------------------- | ------------------------------------------- |
| Installation & prerequisites                   | [Installation](../foundation/installation.md) |
| Deployment steps (shared hosting, VPS, Docker) | [Deployment](deployment.md)                 |
| Environment configuration                      | [Configuration](configuration.md)           |
| Database design & engine comparison            | [Database](database.md)                     |
| Cache management & OpCache                     | [Cache](cache.md)                           |
| Session configuration & security               | [Session](session.md)                       |
| Backup & restore procedures                    | [Backup & Recovery](../foundation/backup-recovery.md)     |
| Performance monitoring, Pulse, logging         | [Observability](../foundation/system-observability.md)           |
| File storage, S3, media library                | [Filesystem](filesystem.md)                 |
| Queue infrastructure, worker management        | [Queue](queue.md)                           |
| Notification channels & delivery               | [Notifications](notification.md)            |
| Routing & middleware                           | [Routes](routes.md)                         |
| Localization & i18n                            | [Localization](localization.md)             |
| Testing & CI/CD                                | [Testing](testing.md)                       |
| Scaling guide                                  | [Scaling](scaling.md)                       |
