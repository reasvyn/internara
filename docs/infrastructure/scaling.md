# Scaling — Performance & Scaling Strategies

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

This document is the operational companion to [Infrastructure](infrastructure.md). It describes
_when_ and _how_ to scale Internara from a shared hosting setup serving 500 registered users to a
multi-server high-availability cluster serving 2000+.

User counts in this document refer to **total registered users per PKL period**, not concurrent
users. For Internara's usage patterns, peak concurrency is typically 10-15% of registered users.

The philosophy is **start simple, scale by measured need** -- codified in
[ADR: Gradual Migration](../adr/adr-gradual-migration.md).

---

## 1. Decision Framework: When to Scale

Do NOT scale preemptively. Scale when you observe these symptoms:

| Symptom                                       | Probable Cause                       | Action                                     | Tier Trigger     |
| --------------------------------------------- | ------------------------------------ | ------------------------------------------ | ---------------- |
| Page load > 1s (50th percentile) on shared    | Missing cache or slow queries        | Enable OpCache, optimize queries           | Tier 1 (Shared)  |
| MySQL connection limit errors                 | Too many concurrent PHP-FPM children | Reduce `pm.max_children`, check provider   | Tier 1 (Shared)  |
| Email sending blocks page response > 3s       | Sync queue delaying HTTP             | Switch to Redis queue, start workers       | Tier 1 -> Tier 2 |
| Media conversions make uploads feel sluggish  | Sync queue blocking                  | Switch to Redis, async conversions         | Tier 1 -> Tier 2 |
| Queue backlog > 100 jobs for > 5 min          | Sync queue blocking HTTP             | Switch to Redis queue, start workers       | Tier 1 -> Tier 2 |
| PHP-FPM max children reached (502 errors)     | Insufficient workers                 | Increase `pm.max_children`, add RAM        | Tier 2 -> Tier 3 |
| Disk > 85% full                               | Media accumulation                   | Prune, archive, or migrate to S3           | Any tier         |
| Database CPU > 80% for > 10 min               | Query bottleneck                     | Add indexes, then add read replica         | Tier 2 -> Tier 3 |
| Rate limiter blocking legitimate users at NAT | IP-only throttle                     | Switch to user-based rate limiting         | Tier 3           |
| Session cache hit rate < 90%                  | File/database session too slow       | Switch to Redis session                    | Tier 1 -> Tier 2 |
| 5xx errors during peak hours                  | Resource exhaustion                  | Profile, then vertical -> horizontal scale | Any tier         |

### Do NOT optimize until:

- You have **measured** the bottleneck (Pulse, Blackfire, or similar)
- You have **confirmed** the bottleneck is in production (not dev)
- The feature causing the load is **stable** (not about to be rewritten)
- The optimization's **ROI exceeds** the complexity cost

---

## 2. Tier Transitions

### Tier 1 (Shared) -> Tier 2 (VPS) [500 -> 500-2000 registered users]

**What changes:**

| Concern          | Tier 1 (Shared)       | Tier 2 (VPS)                      |
| ---------------- | --------------------- | --------------------------------- |
| Server           | Shared hosting        | VPS (2 CPU, 4 GB RAM, 50 GB SSD)  |
| Database engine  | MySQL / MariaDB       | MySQL 8+ / PostgreSQL (dedicated) |
| Cache driver     | `file`                | `redis`                           |
| Session driver   | `database`            | `redis`                           |
| Queue driver     | `sync`                | `redis`                           |
| Queue workers    | none (inline)         | Supervisor (dual pipelines)       |
| Cron             | webhook fallback      | system cron                       |
| Media storage    | `public` disk (local) | local + backup to S3              |
| Monitoring       | log files only        | Pulse + SmartLogger               |
| PHP-FPM children | 10                    | 25                                |
| Web server       | Apache / Nginx        | Nginx + HTTPS (Let's Encrypt)     |

**Steps:**

1. Provision a VPS (2 CPU, 4 GB RAM, 50 GB SSD)
2. Install PHP 8.4 + extensions (bcmath, curl, gd, intl, mbstring, openssl, pdo_mysql, xml, zip,
   opcache, redis)
3. Install MySQL 8+ or PostgreSQL 14+
4. Install Redis 7+
5. Clone codebase and run `composer install --optimize-autoloader --no-dev`
6. Configure `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-school.sch.id

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
10. Configure Nginx (see [Deployment](deployment.md))
11. Configure Supervisor with dual pipeline workers:

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

12. Configure system cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
13. Run `php artisan optimize`
14. Run `php artisan setup:install` if not already completed
15. Verify: `php artisan system:health` -- all checks pass

### Tier 2 -> Tier 3 (VPS -> HA) [500-2000 -> 2000+ registered users]

**What changes:**

| Concern       | Tier 2 (VPS)        | Tier 3 (HA)               |
| ------------- | ------------------- | ------------------------- |
| App servers   | 1                   | 2+ (Nginx load-balanced)  |
| Database      | single (read/write) | primary + read replica(s) |
| Redis         | single instance     | cluster (3 nodes min)     |
| Queue workers | 2 per pipeline      | 4-8 per pipeline          |
| Session       | Redis single        | Redis cluster             |
| Cache         | Redis single        | Redis cluster             |
| Media storage | local + backup      | S3 primary                |
| Rate limiting | IP-based            | user-based                |
| Monitoring    | Pulse               | Pulse + APM (Blackfire)   |

**Steps (in order):**

#### Phase A: Database

1. Provision read replica(s) from your MySQL provider
2. Configure read/write separation:

```env
DB_READ_HOST=replica1.host,replica2.host
DB_WRITE_HOST=primary.host
```

3. Monitor replica lag (Pulse custom recorder)

#### Phase B: Redis Cluster

1. Provision 3+ Redis nodes (or use managed ElastiCache)
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
AWS_URL=https://cdn.your-school.sch.id
```

3. Migrate existing files to S3:

```bash
# Sync files to S3 bucket
aws s3 sync storage/app/public s3://internara-production/ --storage-class STANDARD_IA

# Run the migration command to update media library paths
php artisan media:migrate-to-s3
```

#### Phase D: App Server Scaling

1. Provision additional app servers (same spec: 2-4 CPU, 4 GB RAM)
2. Configure Nginx as load balancer with `least_conn`:

```nginx
upstream internara {
    least_conn;
    server app1.internal:80;
    server app2.internal:80;
    server app3.internal:80;
}

server {
    listen 443 ssl;
    server_name your-school.sch.id;

    location / {
        proxy_pass http://internara;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

3. Configure Supervisor on each app server (4-8 workers per pipeline depending on RAM)

#### Phase E: Rate Limiting

Update `RateLimiter::for()` calls to use user-based limits:

```php
RateLimiter::for('global', function (Request $request) {
    return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
});
```

---

## 3. Configuration Changes Summary (.env)

All scaling transitions are achieved purely through `.env` changes -- no code changes needed.

### From Tier 1 (Shared) to Tier 2 (VPS)

```env
# Before (Tier 1 defaults)
DB_CONNECTION=mysql
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

### From Tier 2 (VPS) to Tier 3 (HA)

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

| Job Type           | Peak per user/day | 500 users | 2000 users |
| ------------------ | ----------------- | --------- | ---------- |
| Email notification | 3                 | 1500      | 6000       |
| Media conversion   | 1                 | 500       | 2000       |
| Report generation  | 0.2               | 100       | 400        |
| Certificate PDF    | 0.1               | 50        | 200        |
| **Daily total**    | **4.3**           | **2150**  | **8600**   |

Worker throughput: ~50-100 jobs/min per worker process.

| Registered users | Workers per pipeline | Driver  | Notes                     |
| ---------------- | -------------------- | ------- | ------------------------- |
| <= 500           | sync (inline)        | `sync`  | No separate worker needed |
| 500-1000         | 2                    | `redis` | Supervisor on single VPS  |
| 1000-2000        | 4                    | `redis` | Increase RAM per server   |
| 2000+            | 6-8                  | `redis` | 2+ app servers x 3-4 each |

---

## 5. Monitoring Thresholds & Actions

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
| Blackfire.io              | Profiling (optional, Tier 3+)            | EUR 59/mo   |

---

## 6. PHP-FPM Scaling Formula

```
Total RAM needed = (pm.max_children x memory_per_process) + OS overhead

Where:
  memory_per_process ~ 40-60 MB (Internara typical)

Examples:
  10 children x 50 MB + 256 MB OS = 756 MB   (Tier 1, Shared Hosting)
  25 children x 50 MB + 512 MB OS = 1.8 GB    (Tier 2, VPS)
  50 children x 50 MB + 1 GB OS = 3.5 GB      (Tier 3 per server)
```

### Connection Pool Size Estimate

```
Pool size = (peak_concurrent_users x 1.5) + buffer

Examples:
  500 registered users (~75 concurrent) x 1.5 + 25 = 138  -> pm.max_children = 10-15 (Tier 1)
  1000 registered users (~150 concurrent) x 1.5 + 50 = 275 -> pm.max_children = 25 (Tier 2)
  2000 registered users (~300 concurrent) x 1.5 + 50 = 500 -> pm.max_children = 50 per server (Tier 3)
```

---

## 7. Common Pitfalls

| Pitfall                                        | Why                                      | Prevention                            |
| ---------------------------------------------- | ---------------------------------------- | ------------------------------------- |
| Switching to Redis without testing             | Missing `ext-redis` causes crash         | Run `php artisan system:health` first |
| Using `sync` queue past 500 registered users   | HTTP requests block on media conversions | Switch to Redis at Tier 2             |
| Forgetting `php artisan optimize`              | Routes/config not cached, 2-3x slower    | Include in deployment script          |
| Not warming cache after deploy                 | First 100 requests are slow              | Add `php artisan optimize` to deploy  |
| Using IP-based rate limiting at NAT            | 100+ students behind 1 IP get blocked    | Switch to user-based limiting         |
| MySQL on shared hosting with too many children | Provider kills your MySQL connections    | Set pm.max_children appropriately     |
| File cache on multi-server                     | Each server has different cache state    | Use Redis at Tier 2+                  |
| Not monitoring replica lag                     | Read queries return stale data           | Add replica lag alert                 |
| S3 permissions misconfigured                   | 403 errors on media URLs                 | Test S3 access before switching       |

---

## 8. Scaling Checklists

### Tier 1 (Shared) Performance Optimization Checklist

- [ ] OpCache enabled and configured (`opcache.memory_consumption=256`)
- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `php artisan optimize` run (config, route, view cache)
- [ ] MySQL slow query log enabled and reviewed
- [ ] PHP-FPM `pm.max_children` tuned for provider limits
- [ ] Media conversion set to thumbnail-only (reduced processing)
- [ ] Backup automation configured
- [ ] Pulse monitoring verified
- [ ] `php artisan system:health` -- all checks pass

### Tier 1 -> Tier 2 Transition Checklist

- [ ] VPS provisioned (2 CPU, 4 GB RAM, 50 GB SSD)
- [ ] PHP 8.4 + all extensions installed (`system:health` passes)
- [ ] MySQL 8+ / PostgreSQL 14+ installed and tuned
- [ ] Redis 7+ installed
- [ ] Nginx configured with HTTPS
- [ ] `.env` updated for production (debug=false, DB, Redis, queue)
- [ ] `php artisan migrate --force` succeeds
- [ ] `php artisan storage:link` created
- [ ] Frontend built: `npm run build`
- [ ] Supervisor configured (dual pipelines, numprocs=2 each)
- [ ] System cron configured for scheduler
- [ ] `php artisan optimize` run
- [ ] `php artisan system:health` -- all checks pass
- [ ] Backup automation configured
- [ ] Pulse monitoring verified
- [ ] SSL certificate installed and auto-renewal configured

### Tier 2 -> Tier 3 Transition Checklist

- [ ] Database read replica provisioned and lag monitored
- [ ] Read/write separation configured in `config/database.php`
- [ ] Redis cluster provisioned (3+ nodes)
- [ ] S3 bucket created and permissions verified
- [ ] `FILESYSTEM_DISK=s3` configured in `.env`
- [ ] Existing media migrated via `php artisan media:migrate-to-s3`
- [ ] 2+ app servers provisioned and load-balanced
- [ ] Supervisor configured per app server (dual pipelines)
- [ ] Rate limiting switched to user-based
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
# Create a smoke test file (e.g., tests/Load/BasicSmoke.js) and run:
k6 run --vus 10 --duration 30s path/to/BasicSmoke.js

# For Tier 3 validation:
k6 run --vus 200 --duration 5m path/to/Tier3Validation.js
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

## References

| Document                                                                  | Contents                                              |
| ------------------------------------------------------------------------- | ----------------------------------------------------- |
| [Infrastructure](infrastructure.md)                                       | Deployment tiers, target architecture, service layout |
| [Installation](../guide/01-installation.md)                               | Prerequisites, command reference, troubleshooting     |
| [Deployment](deployment.md)                                               | Server config, Supervisor, Docker, shared hosting     |
| [Configuration](configuration.md)                                         | .env reference, all config options                    |
| [Observability](observability.md)                                         | Pulse, logging, health checks                         |
| [Queue](queue.md)                                                         | Queue drivers, Supervisor, job lifecycle              |
| [Cache](cache.md)                                                         | Cache strategy, Redis, OpCache                        |
| [ADR: Self-Hosted Single-Tenant](../adr/adr-self-hosted-single-tenant.md) | Why shared-hosting-first                              |
| [ADR: Gradual Migration](../adr/adr-gradual-migration.md)                 | Governing principle for scaling                       |
| [ADR: Performance Optimization](../adr/adr-performance-optimization.md)   | Performance tiers, deferred optimizations             |
