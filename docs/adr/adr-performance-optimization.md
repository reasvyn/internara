# Performance & Optimization Strategy

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Infrastructure Overview](../guides/infra/infrastructure.md) and [Deployment Guide](../guides/infra/deployment.md) |

## Context and Problem Statement

Internara serves schools from 100 to 2000+ users across widely varying infrastructure. In the
MVP phase every infrastructure choice (Redis, workers, S3) competes with feature time, yet the
architecture must not require a rewrite when a school grows from 500 to 2000 users. Three
deployment tiers are already defined:

| Tier | Users | Database | Queue | Cache | Session | Storage |
|------|-------|----------|-------|-------|---------|---------|
| 1 Shared | ≤ 500 | MySQL/MariaDB | sync | file | database | local |
| 2 VPS | 500–2000 | MySQL | Redis | Redis | Redis | local + S3 |
| 3 HA | 2000+ | MySQL + replica | Redis | Redis cluster | Redis cluster | S3 |

**Decision Drivers:**

* MVP velocity over premature infrastructure ceremony
* Tier transitions as configuration changes, not code changes
* No-regret moves that pay at any scale without ongoing cost
* Explicit deferral list to prevent speculative optimization

## Considered Options

* **Optimize late, rewrite when needed** — ship MVP without performance foundations.
  *Pros:* fastest start. *Cons:* tier transitions require rewrites; no-regret moves missed.*
* **Invest in HA from day one** — Octane, sharding, CDN, clustering upfront.
  *Pros:* headroom. *Cons:* ceremony during feature development, unused complexity.*
* **Tiered no-regret + config-only growth (chosen)** — enforce cheap universal wins; Tier 1
  defaults run on MySQL alone; Tier 2/3 are `.env` swaps.
  *Pros:* velocity now, growth without code change. *Cons:* defaults not production-optimal.*

## Decision Outcome

**Chosen option: Tiered no-regret + config-only growth.**

**Tier 0 — Always Enforced (no-regret):**

* UUID v7 PKs, composite indexes on FKs and `activity_log` (1M+ rows), eager-loading
  convention (N+1 is the primary Livewire risk), cache-key registry
  (`config/cache-keys.php`), Read Actions avoid transaction overhead.

**Tier 1 — Shared Hosting Defaults:** queue sync, cache file, session database,
MySQL/MariaDB, local public disk, Pulse ingest sync. Zero external services.

**Tier 2 — VPS Growth** — trigger: sustained > 500 users or P95 > 1s. All `.env` swaps,
zero code: `QUEUE_CONNECTION=redis` + worker, `CACHE_STORE=redis`,
`SESSION_DRIVER=redis`, `PULSE_INGEST_DRIVER=redis`, `FILESYSTEM_DISK=s3` optional.

**Tier 3 — High Scale** — trigger: sustained > 2000 users or DB write > 50ms. Read
replica, S3+CDN, PHP-FPM tuning, Redis cluster, ProxySQL/PgBouncer, user-aware rate
limiting.

**Explicitly Deferred** until evidence demands: Octane, horizontal auto-scaling, CDN for
static assets, sharding, job batching.

**When Not to Optimize:** before measurement (Pulse), before understanding the bottleneck,
before the feature stabilizes.

### Positive Consequences

* MVP velocity preserved; same binary runs at 500 and 2000 users
* No-regret moves are foundational — no developer decision required
* Deferred list removes ambiguity about premature needs

### Negative Consequences

* Default `.env.example` is not production-optimal; deployers must override
* Tier 3 assumes Redis — schools without it need documentation support

## Links

* [Infrastructure Overview](../guides/infra/infrastructure.md) — tier definitions
* [Deployment Guide](../guides/infra/deployment.md) — steps and checklist per tier
* [Cache Guide](../guides/infra/cache.md) — driver configuration and invalidation
* [Self-Hosted Single-Tenant](adr-self-hosted-single-tenant.md) — tenancy foundation this strategy builds on
