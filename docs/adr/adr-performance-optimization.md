# ADR-009: Performance & Optimization Strategy

> **Status:** Accepted
> **Last updated:** 2026-06-08

## Context

Internara serves vocational schools from 50 to 2000+ users across widely varying infrastructure. Current development is in the MVP phase — every infrastructure decision (Redis, queue workers, S3) consumes time that could be spent on features. However, the architecture must not require a rewrite when a school grows from 200 to 1000 users.

Three deployment tiers are already defined:

| Tier | Users | Database | Queue | Cache | Session | Storage |
|---|---|---|---|---|---|---|
| 1 | < 50 | SQLite/MySQL | sync | file | database | local |
| 2 | 50-200 | MySQL | Redis | Redis | Redis | local + S3 |
| 3 | 200-1000+ | MySQL + replica | Redis | Redis cluster | Redis cluster | S3 |

## Decision

### Tier 0 — Always Enforced (No-Regret Moves)

These optimizations cost nothing during development but prevent regressions at any scale:

- **UUID v7 primary keys** — no auto-increment hotspot, merge-safe
- **Composite indexes on foreign keys** — prevents full table scans on JOIN-heavy queries
- **Eager loading convention** — N+1 queries are the single biggest Livewire performance risk
- **Activity log composite indexes** — prevents full scans at 1M+ rows
- **Cache key registry** (config('cache-keys')) — prevents key collisions, makes invalidation discoverable
- **Action triad separation** — Read Actions avoid transaction overhead

### Tier 1 — MVP Defaults (Current)

Zero external services required. All features available, some synchronous instead of asynchronous:

- Queue: sync (jobs run inline)
- Cache: file (atomic on ext4/XFS)
- Session: database (UUID PK with index)
- Database: SQLite with WAL mode
- Media storage: local public disk
- Pulse ingest: storage (sync, request-bound)
- Log retention: daily rotation + scheduler

### Tier 2 — Growth (Configuration Change, No Code)

Trigger: sustained > 50 active users OR database CPU > 60%.

Every change is an `.env` swap — zero code changes:

- `QUEUE_CONNECTION=redis` + start worker
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `PULSE_INGEST_DRIVER=redis`
- `MEDIA_DISK=s3` (optional)

### Tier 3 — High Scale (Configuration + Minor Infra)

Trigger: sustained > 200 active users OR DB write latency > 50ms.

- DB read replica in database config
- S3 primary storage with CDN
- PHP-FPM tuning (pm.max_children)
- Redis cluster (split cache/queue/session)
- Connection pooling (ProxySQL/PgBouncer)
- User-aware rate limiting (key by user_id)
- PHPStan lazy loading prevention rule

### Explicitly Deferred

These are deferred until evidence proves need: Laravel Octane, horizontal auto-scaling, CDN for static assets, database sharding, queue job batching.

### When Not to Optimize

1. Before measurement — if Pulse doesn't show a problem, don't optimize
2. Before understanding the bottleneck — adding Redis for an N+1 query wastes time
3. Before the feature stabilizes — optimize after it settles

## Consequences

- **Positive**: MVP velocity preserved — no wasted infrastructure ceremony during feature development.
- **Positive**: Every tier transition is a configuration change, not a code change. The same binary runs at 50 and 2000 users.
- **Positive**: No-regret moves are built into the foundation — developers don't need to think about them.
- **Positive**: Deferred optimizations are explicitly listed — no ambiguity about whether Octane is needed.
- **Negative**: Default configuration is not production-optimal — deployers must override `.env.example` values.
- **Negative**: Tier 3 assumes Redis availability — schools without Redis experience need documentation support.

## References

- `docs/infrastructure/infrastructure.md` — Three deployment tiers
- `docs/infrastructure/deployment.md` — Deployment steps and checklist
- `docs/infrastructure/cache.md` — Cache driver configuration
- `docs/infrastructure/queue.md` — Queue worker management
- `.env.example` — Default Tier 1 configuration
- `docs/adr/adr-self-hosted-single-tenant.md` — Foundation decision
- `docs/adr/adr-gradual-migration.md` — Governing principle
