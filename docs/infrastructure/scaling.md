# Scaling Guide

> Last updated: 2026-06-01 Changes: feat: infrastructure optimization — scaling guide, composite
> indexes, N+1 in AttendanceManager

This document is the operational companion to [Infrastructure](infrastructure.md). It describes
_when_ and _how_ to scale Internara from a single-user dev setup to production serving 1500-2000
concurrent users.

The philosophy is **start simple, scale by measured need** — codified in
[ADR: Gradual Migration](../adr/adr-gradual-migration.md).

---

## 1. Decision Framework: When to Scale

Do NOT scale preemptively. Scale when you observe these symptoms:

| Symptom                                       | Probable Cause                           | Action                                    | Tier Trigger    |
| --------------------------------------------- | ---------------------------------------- | ----------------------------------------- | --------------- |
| SQLite "database is locked" errors            | Concurrent writes exceed SQLite capacity | Switch to MySQL/PostgreSQL                | MVP → Tier 2    |
| Page load > 500ms (50th percentile)           | Missing cache or slow queries            | Enable Redis cache, warm caches           | MVP → Tier 2    |
| Queue backlog > 100 jobs for > 5 min          | Sync queue blocking HTTP                 | Switch to Redis queue, start worker(s)    | MVP → Tier 2    |
| PHP-FPM max children reached (502 errors)     | Insufficient workers                     | Increase `pm.max_children`, add RAM       | Tier 2 → Tier 3 |
| Disk > 85% full                               | Media accumulation                       | Prune, archive, or migrate to S3          | Any tier        |
| Database CPU > 80% for > 10 min               | Query bottleneck                         | Add indexes, then add read replica        | Tier 2 → Tier 3 |
| Rate limiter blocking legitimate users at NAT | IP-only throttle                         | Switch to user-based rate limiting        | Tier 3          |
| Session cache hit rate < 90%                  | File/database session too slow           | Switch to Redis session                   | MVP → Tier 2    |
| 5xx errors during peak hours                  | Resource exhaustion                      | Profile, then vertical → horizontal scale | Any tier        |

### Do NOT optimize until:

- You have **measured** the bottleneck (Pulse, Blackfire, or similar)
- You have **confirmed** the bottleneck is in production (not dev)
- The feature causing the load is **stable** (not about to be rewritten)
- The optimization's **ROI exceeds** the complexity cost

---

## 2. Tier Transitions

### MVP → Tier 2 (<50 → 50-200 users)

**What changes:**

| Concern          | MVP (default)         | Tier 2                          |
| ---------------- | --------------------- | ------------------------------- |
| Database engine  | SQLite (auto)         | MySQL 8+ / MariaDB / PostgreSQL |
| Cache driver     | `file`                | `redis`                         |
| Session driver   | `database`            | `redis`                         |
| Queue driver     | `sync`                | `redis`                         |
| Queue workers    | none (inline)         | Supervisor (numprocs=4)         |
| Cron             | webhook fallback      | system cron                     |
| Media storage    | `public` disk (local) | local + backup to S3            |
| Monitoring       | log files only        | Pulse + SmartLogger             |
| PHP-FPM children | 10                    | 25                              |

**Steps:**

1. Provision a VPS (2 CPU, 4 GB RAM, 50 GB SSD)
2. Install PHP 8.4 + extensions (bcmath, curl, gd, intl, mbstring, openssl, pdo_mysql, xml, zip,
   opcache, redis)
3. Install MySQL 8+ or PostgreSQL 14+
4. Install Redis 7+
5. Clone codebase and run `composer install --optimize-autoloader --no-dev`
6. Copy `.env.example` to `.env` and update:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-module.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=<strong-password>

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

7. Run `php artisan migrate --force`
8. Run `php artisan storage:link`
9. Run `npm install && npm run build`
10. Configure Nginx (see [Installation](installation.md))
11. Configure Supervisor with `numprocs=4`:

```ini
[program:internara-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log
stopwaitsecs=3600
```

12. Configure system cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
13. Run `php artisan optimize`
14. Run `php artisan setup:install` and complete the wizard
15. Verify: `php artisan system:health` — all checks pass

### Tier 2 → Tier 3 (50-200 → 200-1500+ users)

**What changes:**

| Concern       | Tier 2              | Tier 3                    |
| ------------- | ------------------- | ------------------------- |
| App servers   | 1                   | 2+ (Nginx load-balanced)  |
| Database      | single (read/write) | primary + read replica(s) |
| Redis         | single instance     | cluster (3 nodes min)     |
| Queue workers | 4 per server        | 8-16 per server           |
| Session       | Redis single        | Redis cluster             |
| Cache         | Redis single        | Redis cluster             |
| Media storage | local + backup      | S3 primary                |
| Rate limiting | IP-based            | user-based                |
| Monitoring    | Pulse               | Pulse + APM (Blackfire)   |

**Steps (in order):**

#### Phase A: Database (highest impact first)

1. Provision read replica(s) from your MySQL provider
2. Configure read/write separation in `.env` (Laravel handles this via `config/database.php`):

```env
DB_READ_HOST=replica1.host,replica2.host
DB_WRITE_HOST=primary.host
```

3. Ensure replica lag is monitored (Pulse custom recorder)

#### Phase B: Redis Cluster

1. Provision 3+ Redis nodes (or use a managed service like ElastiCache)
2. Update `.env`:

```env
REDIS_CLUSTER=true
REDIS_CLUSTER_NODES=node1:6379,node2:6379,node3:6379
```

#### Phase C: Media Storage

1. Create S3 bucket (AWS S3, MinIO, DigitalOcean Spaces, or Cloudflare R2)
2. Configure `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=internara-production
AWS_URL=https://cdn.your-module.com
```

3. Migrate existing files:

```bash
php artisan media:migrate-to-s3
```

#### Phase D: App Server Scaling

1. Provision additional app servers (same spec: 2-4 CPU, 4 GB RAM)
2. Configure Nginx as load balancer:

```nginx
upstream internara {
    least_conn;               # send to least busy server
    server app1.internal:80;
    server app2.internal:80;
    server app3.internal:80;
}

server {
    listen 443 ssl;
    server_name your-module.com;

    location / {
        proxy_pass http://internara;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

3. Configure Supervisor on each app server (numprocs=8-16 depending on RAM)

#### Phase E: Rate Limiting

Update `config/rate-limiting.php` to use user-based limits (keyed by `user_id` instead of IP):

```php
// config/rate-limiting.php
'global' => [
    'limit' => 500,
    'key' => fn () => auth()->id() ?: request()->ip(),
],
```

---

## 3. Configuration Changes Summary (.env)

All scaling transitions are achieved purely through `.env` changes — no code changes needed. This is
guaranteed by the [Infrastructure Architecture](infrastructure.md).

### From MVP to Tier 2

```env
# Before (MVP defaults)
DB_CONNECTION=sqlite
CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

# After (Tier 2)
DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=local     # S3 backup via cron
```

### From Tier 2 to Tier 3

```env
# Before (Tier 2)
DB_CONNECTION=mysql
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
FILESYSTEM_DISK=local

# After (Tier 3)
DB_CONNECTION=mysql        # + read replica via config/database.php
CACHE_STORE=redis          # cluster
SESSION_DRIVER=redis       # cluster
QUEUE_CONNECTION=redis     # cluster
FILESYSTEM_DISK=s3
```

---

## 4. Queue Worker Sizing

Estimate queue throughput:

| Job Type           | Peak per user/day | 200 users | 1500 users |
| ------------------ | ----------------- | --------- | ---------- |
| Email notification | 3                 | 600       | 4500       |
| Media conversion   | 1                 | 200       | 1500       |
| Report generation  | 0.2               | 40        | 300        |
| Certificate PDF    | 0.1               | 20        | 150        |
| **Daily total**    | **4.3**           | **860**   | **6450**   |

At 8 hours peak: 860/8 = 108 jobs/hour for 200 users, 6450/8 = 806 jobs/hour for 1500 users.

Worker throughput: ~50-100 jobs/min per worker process. Recommendation:

| Users     | `numprocs`    | Notes                          |
| --------- | ------------- | ------------------------------ |
| < 50      | sync (inline) | No separate worker needed      |
| 50-200    | 4             | Redis queue, Supervisor        |
| 200-500   | 8             | Increase RAM per server        |
| 500-1000  | 12            | 2 app servers × 6 workers each |
| 1000-2000 | 16            | 2 app servers × 8 workers each |

---

## 5. Monitoring Thresholds & Actions

Configure these alerts in Pulse or external monitoring:

| Metric                  | Warning   | Critical             | Action                              |
| ----------------------- | --------- | -------------------- | ----------------------------------- |
| Queue backlog           | > 50 jobs | > 100 jobs for 5 min | Add workers, check for stuck job    |
| DB query time (P95)     | > 50ms    | > 100ms              | Check slow query log, add index     |
| PHP-FPM active children | > 60%     | > 80%                | Increase max_children or add server |
| Disk usage              | > 75%     | > 85%                | Prune, archive, or expand           |
| CPU average (5 min)     | > 50%     | > 70%                | Check for runaway process, scale    |
| Redis memory            | > 60%     | > 80%                | Increase maxmemory, enable eviction |
| Session cache hit rate  | < 95%     | < 90%                | Check Redis, fallback driver health |
| Failed jobs (24h)       | > 5       | > 20                 | Investigate failing job class       |
| Backup age              | > 26h     | > 48h                | Check cron/backup script            |
| SSL cert expiry         | < 30 days | < 7 days             | Renew certificate                   |

### Recommended Monitoring Stack

| Tool                      | What                                     | Cost        |
| ------------------------- | ---------------------------------------- | ----------- |
| Laravel Pulse (built-in)  | Request, query, queue, exception metrics | Free        |
| SmartLogger (built-in)    | Business audit trail                     | Free        |
| Uptime Kuma (self-hosted) | HTTP/S uptime + SSL expiry               | Free        |
| Netdata (self-hosted)     | System metrics (CPU, RAM, disk, network) | Free        |
| Sentry (or GlitchTip)     | Error tracking (optional)                | Free-$26/mo |
| Blackfire.io              | Profiling (optional, Tier 3+)            | €59/mo      |

---

## 6. Backup Scaling

As data grows, backup strategy must evolve:

| Tier   | Database                 | Files                       | RPO | Procedure                        |
| ------ | ------------------------ | --------------------------- | --- | -------------------------------- |
| MVP    | SQLite file copy         | rsync to second disk        | 24h | Manual cron script               |
| Tier 2 | mysqldump (compressed)   | rsync to S3 nightly         | 24h | Automated cron, 30-day retention |
| Tier 3 | Percona XtraBackup (hot) | S3 versioning + replication | 1h  | Hourly incremental, daily full   |

### S3 Migration for Media

When migrating from local disk to S3:

```bash
# Install AWS CLI
# Sync existing files
aws s3 sync storage/app/public s3://internara-media/ --storage-class STANDARD_IA

# Set S3 as primary disk
# Run the migration command (if available):
php artisan media:transfer-s3
```

### Database Migration Path

```bash
# From SQLite to MySQL (Tier MVP → Tier 2)
# 1. Dump SQLite
sqlite3 database/database.sqlite .dump > dump.sql

# 2. Convert to MySQL-compatible format
sed -i 's/AUTOINCREMENT/AUTO_INCREMENT/g' dump.sql

# 3. Import to MySQL
mysql -u internara -p internara < dump.sql

# 4. Update .env and verify
php artisan migrate --force
php artisan system:health
```

---

## 7. Common Pitfalls

| Pitfall                                        | Why                                      | Prevention                            |
| ---------------------------------------------- | ---------------------------------------- | ------------------------------------- |
| Switching to Redis without testing             | Missing `ext-redis` causes crash         | Run `php artisan system:health` first |
| Using `sync` queue past 50 users               | HTTP requests block on media conversions | Switch to Redis at Tier 2             |
| Forgetting `php artisan optimize`              | Routes/config not cached, 2-3× slower    | Include in deployment script          |
| Not warming cache after deploy                 | First 100 requests are slow              | Add `php artisan optimize` to deploy  |
| Using IP-based rate limiting at NAT            | 100+ students behind 1 IP get blocked    | Switch to user-based limiting         |
| SQLite in production with >10 concurrent users | "database is locked" errors              | Switch to MySQL at Tier 2             |
| File cache on multi-server                     | Each server has different cache state    | Use Redis at Tier 2+                  |
| Not monitoring replica lag                     | Read queries return stale data           | Add replica lag alert                 |
| S3 permissions misconfigured                   | 403 errors on media URLs                 | Test S3 access before switching       |

---

## 8. Scaling Checklists

### MVP → Tier 2 Transition Checklist

- [ ] VPS provisioned (2 CPU, 4 GB RAM, 50 GB SSD)
- [ ] PHP 8.4 + all extensions installed (`system:health` passes)
- [ ] MySQL 8+ / PostgreSQL 14+ installed and tuned
- [ ] Redis 7+ installed
- [ ] Nginx configured with HTTPS
- [ ] `.env` updated for production (debug=false, DB, Redis, queue)
- [ ] `php artisan migrate --force` succeeds
- [ ] `php artisan storage:link` created
- [ ] Frontend built: `npm run build`
- [ ] Supervisor configured (4 workers)
- [ ] System cron configured for scheduler
- [ ] `php artisan optimize` run
- [ ] `php artisan system:health` — all checks pass
- [ ] Backup automation configured
- [ ] Pulse monitoring verified
- [ ] SSL certificate installed and auto-renewal configured

### Tier 2 → Tier 3 Transition Checklist

- [ ] Database read replica provisioned and lag monitored
- [ ] Read/write separation configured in `config/database.php`
- [ ] Redis cluster provisioned (3+ nodes)
- [ ] S3 bucket created and permissions verified
- [ ] `FILESYSTEM_DISK=s3` configured in `.env`
- [ ] Existing media migrated to S3
- [ ] 2+ app servers provisioned and load-balanced
- [ ] Supervisor configured per app server (8-16 workers)
- [ ] Rate limiting switched to user-based
- [ ] User-aware rate limiter tested with concurrent users
- [ ] Session/cache verified on Redis cluster
- [ ] APM (Blackfire/Sentry) configured
- [ ] Backup strategy updated (hourly incremental)
- [ ] Replica lag alert configured
- [ ] Load test performed with target user count

---

## 9. Load Testing

Before scaling to a new tier, validate with load testing:

```bash
# Install k6 (https://k6.io)
# Run a basic smoke test
k6 run --vus 10 --duration 30s tests/Load/BasicSmoke.js

# For Tier 3 validation:
k6 run --vus 200 --duration 5m tests/Load/Tier3Validation.js
```

Create `tests/Load/BasicSmoke.js`:

```javascript
import http from 'k6/http'
import { check, sleep } from 'k6'

export default function () {
    const res = http.get('https://your-module.com/login')
    check(res, {
        'login page loads': (r) => r.status === 200,
        'load time < 500ms': (r) => r.timings.duration < 500,
    })
    sleep(1)
}
```

### Key Metrics to Track During Load Test

| Metric                  | Target       | Notes                         |
| ----------------------- | ------------ | ----------------------------- |
| P95 response time       | < 500ms      | Track per-route               |
| Error rate              | < 0.1%       | 5xx errors                    |
| PHP-FPM active children | < 80% of max | Increase workers if exceeded  |
| Database CPU            | < 60%        | Add replica if exceeded       |
| Queue backlog           | < 10         | Workers must keep up          |
| Redis hit rate          | > 95%        | Cache warming needed if below |

---

## 10. PHP-FPM Scaling Formula

```
Total RAM needed = (pm.max_children × memory_per_process) + OS overhead

Where:
  memory_per_process ≈ 40-60 MB (Internara typical)

Examples:
  50 children × 50 MB + 512 MB OS = 3.0 GB  (Tier 2)
  100 children × 50 MB + 1 GB OS = 6.0 GB    (Tier 3 per server)
```

### Connection Pool Size Estimate

```
Pool size = (peak_concurrent_users × 1.5) + buffer

Examples:
  200 users × 1.5 + 50 = 350  →  pm.max_children = 50
  1500 users × 1.5 + 100 = 2350 →  pm.max_children = 100 per server, 3 servers
```

---

## References

| Document                                                                  | Contents                                              |
| ------------------------------------------------------------------------- | ----------------------------------------------------- |
| [Infrastructure](infrastructure.md)                                       | Deployment tiers, target architecture, service layout |
| [Installation](installation.md)                                           | Detailed setup for VPS, Docker, shared hosting        |
| [Configuration](configuration.md)                                         | .env reference, all config options                    |
| [Observability](observability.md)                                         | Pulse, logging, health checks                         |
| [Queue](queue.md)                                                         | Queue drivers, Supervisor, job lifecycle              |
| [Cache](cache.md)                                                         | Cache strategy, Redis, OpCache                        |
| [ADR: Self-Hosted Single-Tenant](../adr/adr-self-hosted-single-tenant.md) | Why MVP-first                                         |
| [ADR: Gradual Migration](../adr/adr-gradual-migration.md)                 | Governing principle for scaling                       |
| [ADR: Performance Optimization](../adr/adr-performance-optimization.md)   | Performance tiers, deferred optimizations             |
