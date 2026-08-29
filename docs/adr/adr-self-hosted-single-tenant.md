# Self-Hosted Single-Tenant Architecture

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Deployment Guide](../guides/infra/deployment.md) and [Installation Guide](../guides/installation.md) |

## Context and Problem Statement

Internara targets vocational schools that operate their own IT infrastructure — installed once per
school on the school's own server or shared hosting, not as a SaaS serving thousands of tenants.
This shapes every trade-off: no tenant isolation needed (one "school" per instance), no centralized
auth or billing tiers, offline tolerance on a local network, and wizard-driven setup for minimal IT
staff.

**Decision Drivers:**

* Data sovereignty — student and partnership records never leave the school's server
* Zero-config defaults runnable on $3–15/month shared hosting with only MySQL/MariaDB
* No vendor lock-in or telemetry; backup as a simple file copy
* Upgrade path that does not rewrite tiers as configuration-only switches

## Considered Options

* **Multi-tenant SaaS** — single hosted instance for many schools.
  *Pros:* centralized ops, one deployment. *Cons:* tenant middleware, scoped queries,
  per-tenant config, vendor lock-in, offline fragility.*
* **Self-hosted single-tenant (chosen)** — each school runs its own instance, owns data and
  backups, updates via git pull + artisan. *Pros:* Absolute sovereignty, performance isolation,
  simplest defaults. *Cons:* no cross-school super-admin view; manual per-instance updates.*

## Decision Outcome

**Chosen option: Self-hosted single-tenant** — every architectural concern follows:

| Concern | Decision | Rationale |
|---------|----------|-----------|
| Database | SQLite dev/test, MySQL/MariaDB prod | Available on all shared hosting; SQLite for standalone dev |
| Queue | sync default, Redis optional | No daemon required |
| Cache | file/database default, Redis optional | Zero-config file cache |
| Session | database default, Redis optional | Auto-created by migration |
| Broadcasting | Log driver (disabled) | Reverb optional |
| File storage | Local default, S3 optional | Local suffices for single-server |
| Auth | Local DB, bcrypt | No external provider |
| Installation | CLI + web wizard | Single command provisions system |
| Backup | File copy of SQLite + storage | No dump scripts |

**Feature Availability** — every feature works in the default configuration; some run
synchronously (emails, media conversions block response) and real-time updates require refresh
without Reverb. No feature is disabled in any tier.

**Data Sovereignty** — student records, assessments, partnerships, and configuration never leave
the school's server; no telemetry, usage reporting, or external API calls for core features.

### Positive Consequences

* No multi-tenant infrastructure (middleware, scoped queries, per-tenant config)
* Zero external services beyond MySQL/MariaDB at default — file cache + sync queue
* Absolute data sovereignty and performance isolation

### Negative Consequences

* No cross-school management view; each admin operates independently
* Manual per-instance updates (pull, migrate, rebuild assets)
* SQLite unsuitable for production concurrency — shared hosting requires MySQL/MariaDB

## Links

* [Deployment Guide](../guides/infra/deployment.md) — three deployment paths
* [Installation Guide](../guides/installation.md) — prerequisites and setup wizard
* [Infrastructure Overview](../guides/infra/infrastructure.md) — tier definitions
* [Performance Strategy](adr-performance-optimization.md) — how single-tenant scales without rewrite
