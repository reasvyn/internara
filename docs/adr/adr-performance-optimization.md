# Performance & Optimization Strategy

> Last updated: 2026-06-01 Changes: initial ADR — performance boundaries, tiered optimization,
> no-regret moves vs deferred infrastructure

## Status

Accepted

## Context

Internara serves vocational schools managing fieldwork programs (PKL). The system must function
reliably across a wide range of deployment scales — from a school with 50 students on shared hosting
to a large institution with 1500-2000 active users on dedicated infrastructure.

Three realities drive this ADR:

1. **MVP development is the current phase.** Every infrastructure decision made today — Redis setup,
   queue worker configuration, S3 integration — consumes time that could be spent on features, bug
   fixes, and UX improvements. Premature optimization steals velocity without measurable benefit.

2. **Architecture must not block growth.** The system cannot require an architectural rewrite when
   the school grows from 200 to 1000 users. Configuration changes (`.env` toggles, driver swaps)
   must be sufficient for all tiers — no code changes, no migrations, no schema redesigns.

3. **Some optimizations are time-sensitive.** Adding a database index is trivial when the table has
   1000 rows but locks the table for minutes at 1M rows. Fixing N+1 queries is easy in a 10-line
   Livewire component but painful in a 200-line monster. These are _no-regret moves_ — cheap now,
   expensive later.

The codebase already supports three deployment tiers (documented in
`docs/infrastructure/infrastructure.md`):

| Tier | Target Users | Database             | Queue         | Cache         | Session       | Storage           |
| ---- | ------------ | -------------------- | ------------- | ------------- | ------------- | ----------------- |
| 1    | < 50         | SQLite / MySQL       | sync          | file          | database      | local             |
| 2    | 50-200       | MySQL                | Redis         | Redis         | Redis         | local + S3 backup |
| 3    | 200-1000+    | MySQL + read replica | Redis cluster | Redis cluster | Redis cluster | S3                |

This ADR defines what gets optimized _now_, what gets documented for _later_, and what is explicitly
_deferred_.

## Decision

### Tier 0 — Always Enforced (No-Regret Moves)

These optimizations cost nothing during development but prevent performance regressions at any
scale. They are enforced from day one.

| Optimization                          | Rationale                                                                                                      | Enforcement                                                     |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| **UUID primary keys** (v7 ordered)    | No auto-increment hotspot, merge-safe, distributed-friendly                                                    | `BaseModel` + `HasUuids` trait on all models                    |
| **Composite indexes on foreign keys** | Prevents full table scans on JOIN-heavy queries as data grows                                                  | Convention: every `foreignUuid()` column gets an explicit index |
| **Eager loading convention**          | N+1 queries are the single biggest performance risk in Livewire                                                | Code review + PHPStan (`model.lazyLoad` rule planned)           |
| **Activity log composite indexes**    | `log_name + created_at`, `subject_type + subject_id`, `causer_type + causer_id` prevent full scans at 1M+ rows | Migration to be added (C1 in known-issues)                      |
| **Cache key registry** (`CacheKeys`)  | Centralized enum prevents key collisions and makes invalidation discoverable                                   | All cache operations must use `CacheKeys` constants             |
| **Action triad separation**           | Read Actions avoid transaction overhead; Command Actions isolate writes from reads                             | Architecture review                                             |

### Tier 1 — MVP Defaults (Current)

The system works with zero external services. All features are available — some run synchronously
instead of asynchronously.

| Service       | Default                            | Acceptable Because                                                                               |
| ------------- | ---------------------------------- | ------------------------------------------------------------------------------------------------ |
| Queue         | `sync`                             | Jobs run inline. Media conversions and PDF generation block the response but function correctly. |
| Cache         | `file`                             | Single-server deployment. File cache is atomic on ext4/XFS and survives restarts.                |
| Session       | `database`                         | Session table uses UUID PKs with index. At < 50 concurrent users, session reads are negligible.  |
| Database      | SQLite                             | Excellent for single-user access patterns. WAL mode handles light concurrent reads.              |
| Pulse ingest  | `storage` (sync)                   | Request-bound recording. At low traffic, the DB write overhead is immeasurable.                  |
| Media storage | `public` (local)                   | Single server serves files directly. Symlink to `storage/app/public`.                            |
| Log retention | Daily rotation + scheduler cleanup | Logs are cheap to store; cleanup runs once daily via web-cron fallback.                          |

**Note:** `.env.example` defaults reflect these choices. They are correct for development and Tier 1
deployments. Production deployments at Tier 2+ MUST override them.

### Tier 2 — Growth Optimization (When Needed)

Trigger conditions: sustained active users > 50, OR database CPU > 60%, OR queue backlog detected by
Pulse.

| Change                                  | Effort | Impact                                                   |
| --------------------------------------- | ------ | -------------------------------------------------------- |
| `QUEUE_CONNECTION=redis` + start worker | 15 min | Async media, email, PDF — HTTP responses no longer block |
| `CACHE_STORE=redis`                     | 5 min  | Multi-process cache coherence, faster reads              |
| `SESSION_DRIVER=redis`                  | 5 min  | Session reads offloaded from DB                          |
| `PULSE_INGEST_DRIVER=redis`             | 5 min  | Pulse recording doesn't touch DB on every request        |
| `MEDIA_DISK=s3` (optional)              | 30 min | S3-compatible storage for multi-server readiness         |
| Increase `numprocs` to 4-8              | 5 min  | Queue workers match job volume                           |

**Key property:** Every Tier 2 change is an `.env` variable swap. Zero code changes, zero
deployments, zero risk of regressions.

### Tier 3 — High-Scale Optimization (1500-2000 Users)

Trigger conditions: sustained active users > 200, OR database write latency > 50ms, OR single-server
CPU consistently > 70%.

| Change                                                                       | Effort  | Impact                                                                                           |
| ---------------------------------------------------------------------------- | ------- | ------------------------------------------------------------------------------------------------ |
| **DB read replica** — add `read` host to `config/database.php`               | 1 hour  | Reads go to replica, writes to primary. Reduces primary load by ~60%.                            |
| **S3 primary storage** — `MEDIA_DISK=s3`                                     | 30 min  | Files served from CDN, not app server. Required for horizontal scaling.                          |
| **PHP-FPM tuning** — raise `pm.max_children` to 50-100 per server            | 15 min  | More concurrent requests handled. Formula: `max_children = peak_concurrent × avg_response_time`. |
| **Redis cluster** — split cache/queue/session to separate instances          | 1 hour  | No resource contention between services. Queue throughput isolated from cache eviction.          |
| **Connection pooling** — ProxySQL (MySQL) or PgBouncer (PostgreSQL)          | 2 hours | Prevents connection exhaustion at > 500 concurrent DB connections.                               |
| **User-aware rate limiting** — key by `user_id` instead of IP                | 30 min  | School NAT (single IP for 500 students) no longer triggers per-IP limits.                        |
| **Lazy loading prevention** — PHPStan rule enforcing `->load()` over `->xxx` | 30 min  | Catches N+1 queries in CI before they reach production.                                          |

### Explicitly Deferred (Not Planned)

These optimizations are not required for 1500-2000 users and are deferred unless evidence proves
otherwise.

| Optimization                           | Why Deferred                                                                             | Revisit When                                                     |
| -------------------------------------- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| **Laravel Octane** (RoadRunner/Swoole) | Single-request model is simpler to debug. FPM handles 500 concurrent with proper tuning. | Page load > 1s despite OpCache + Redis.                          |
| **Horizontal auto-scaling**            | One dedicated server handles 200-500 concurrent users. Manual scaling is sufficient.     | Sustained > 500 concurrent or availability requirements > 99.5%. |
| **CDN for static assets**              | Vite build output is ~300KB. Direct server serving is fast enough.                       | Page load > 3s for static resources.                             |
| **Database sharding**                  | Single MySQL instance handles 1M-5M rows with proper indexing.                           | > 10M rows AND > 1000 concurrent writes.                         |
| **Queue job batching**                 | Individual job dispatching is fine for current volume.                                   | > 10K jobs/hour with batch dependencies.                         |

### Monitoring Cadence

| Metric                  | Tool             | Alert Threshold                | Action                                          |
| ----------------------- | ---------------- | ------------------------------ | ----------------------------------------------- |
| Queue backlog           | Pulse            | > 100 pending jobs for > 5 min | Add workers, check for stuck jobs               |
| DB query time           | Pulse            | P95 > 100ms                    | Check slow query log, add indexes               |
| PHP-FPM children        | `pm.status`      | > 80% of `max_children`        | Increase `max_children` or add server           |
| Disk usage              | `df -h`          | > 85%                          | Prune logs, archive old data, expand storage    |
| CPU load                | `htop` / Pulse   | > 70% sustained                | Optimize queries, scale vertically/horizontally |
| Session cache hit ratio | `redis-cli info` | < 90%                          | Check eviction policy, increase `maxmemory`     |

### When Not to Optimize

- **Before measurement.** If Pulse doesn't show a problem, don't optimize. Premature optimization
  adds complexity without evidence.
- **Before understanding the bottleneck.** Adding Redis when the real problem is an N+1 query wastes
  time and introduces a service dependency.
- **Before the feature stabilizes.** Optimizing a dashboard query that will be redesigned next
  sprint is wasted effort. Let the feature settle, then measure, then optimize.

## Consequences

### Positive

- **MVP velocity is preserved.** No wasted effort on Redis setup, queue configuration, or
  infrastructure ceremony during feature development. The `.env.example` defaults work out of the
  box for `php artisan serve`.

- **Every tier transition is a configuration change, not a code change.** The same Artisan binary,
  the same codebase, the same deployment artifact — only `.env` and `config/database.php` differ
  between a 50-user school and a 2000-user school.

- **No-regret moves are identified and documented.** Composite indexes, eager loading conventions,
  UUID v7, and CacheKeys are built into the foundation. Developers don't need to think about them —
  the framework (BaseModel, conventions, code review) handles it automatically.

- **Deferred optimizations are explicitly listed.** No ambiguity about whether "we should add
  Octane" is a good idea — the answer is "not until we measure a real bottleneck."

- **Documentation exists before the need arises.** The scaling guide
  (`docs/infrastructure/scaling.md`) describes the exact steps for each tier transition, reducing
  panic-driven decisions when performance issues emerge during a live PKL period.

### Negative

- **Some users will outgrow Tier 1 before they plan for Tier 2.** A school that starts with SQLite
  and 50 users may hit the "database is locked" error during peak hours. Mitigation: the scaling
  guide documents early warning signs and the exact migration steps to MySQL.

- **Default configuration is not production-optimal.** `.env.example` ships with
  `QUEUE_CONNECTION=sync` and `CACHE_STORE=file`. A deployer who blindly copies `.env.example` to
  production will get suboptimal performance. Mitigation: deployment checklist in
  `docs/infrastructure/deployment.md` documents exactly which values to override.

- **Tier 3 assumes Redis is available.** Schools without Redis experience may struggle with
  configuration. Mitigation: documented Redis setup guide, Docker Compose stack for Tier 2+ includes
  pre-configured Redis.

- **No-regret moves still require discipline.** Composite indexes, eager loading, and CacheKeys
  conventions are enforced by code review, not automation. Mitigation: planned PHPStan rules and
  architecture tests (when `pest-plugin-arch` stabilizes).

## References

- `docs/infrastructure/infrastructure.md` — Three deployment tiers, component sizing, scaling guide
- `docs/infrastructure/deployment.md` — Deployment steps, Supervisor config, checklist
- `docs/infrastructure/cache.md` — Cache driver configuration, OpCache, warm-up procedures
- `docs/infrastructure/queue.md` — Queue infrastructure, worker management
- `docs/infrastructure/configuration.md` — Environment variable reference
- `docs/adr/adr-self-hosted-single-tenant.md` — Foundation decision for simple defaults
- `docs/adr/adr-gradual-migration.md` — Governing principle: "good enough today > perfect next week"
- `docs/known-issues.md` — C1-C13 infrastructure audit findings
- `.env.example` — Default configuration for Tier 1 / development
