# ADR-010: Self-Hosted Single-Tenant Architecture

> **Last updated:** 2026-06-13 **Changes:** sync — initial metadata sync with new format

## Description

Each school runs their own instance on their own infrastructure. No multi-tenant isolation, no
centralized auth, no billing tiers — everything works out of the box.

## Context

Internara targets vocational upper-secondary schools that operate their own IT infrastructure.
Unlike SaaS products serving thousands of tenants, Internara is installed once per school on the
school's own server or shared hosting.

This deployment model shapes every architectural decision:

- **No tenant isolation needed** — never more than one "school" per instance
- **No centralized auth** — each school manages its own users, roles, and accounts
- **No billing or quotas** — everything is available from day one
- **Offline tolerance** — schools may have unreliable internet; the system must work on a local
  network
- **Minimal IT staff** — installation must be wizard-driven, maintenance minimal, backup simple

Two approaches were evaluated:

1. **Multi-tenant SaaS** — single hosted instance serving many schools. Adds significant
   infrastructure complexity and creates vendor lock-in.
2. **Self-hosted single-tenant** — each school runs their own instance. No tenant infrastructure.
   The school owns their data. Updates are manual (git pull + artisan commands). Backup is a file
   copy.

## Decision

Internara is a **self-hosted, single-tenant** application. Every architectural decision follows from
this:

| Concern      | Decision                                     | Rationale                                                                               |
| ------------ | -------------------------------------------- | --------------------------------------------------------------------------------------- |
| Database     | SQLite dev/testing, MySQL/MariaDB production | MySQL/MariaDB available on all shared hosting plans. SQLite for standalone development. |
| Queue        | sync driver default, Redis optional          | No background daemon required.                                                          |
| Cache        | file/database default, Redis optional        | No external service. Zero-config file cache.                                            |
| Session      | database default, Redis optional             | Auto-created by migration.                                                              |
| Broadcasting | Log driver default (disabled)                | WebSocket (Reverb) optional.                                                            |
| File storage | Local disk default, S3 optional              | Single-server: local works.                                                             |
| Auth         | Local database, bcrypt passwords             | No external auth provider.                                                              |
| Installation | CLI wizard + web wizard                      | Single command provisions the system.                                                   |
| Backup       | File copy of SQLite + storage                | No dump scripts needed.                                                                 |

### Feature Availability

Every feature works in the default configuration. Some are synchronous instead of asynchronous —
email notifications and media conversions block the response but function correctly. Real-time
updates require page refresh without Reverb. No feature is disabled in any tier.

### Data Sovereignty

Student records, assessment data, company partnerships, and configuration never leave the school's
server. No telemetry, no usage reporting, no external API calls for core functionality. Backup is
under the school's control.

## Consequences

- **Positive**: No multi-tenant infrastructure to build or maintain. No tenant middleware, scoped
  queries, or per-tenant configuration.
- **Positive**: Default configuration requires zero external services beyond MySQL/MariaDB — file
  cache + sync queue. Runs on a $3-15/month shared hosting plan.
- **Positive**: Data sovereignty is absolute. The school owns their data, backups, and
  infrastructure.
- **Positive**: Performance isolation — one school's load never affects another.
- **Negative**: No centralized management or "super admin" view across all schools. Each admin
  manages independently.
- **Negative**: Updates require manual intervention on each instance — pull, migrate, rebuild
  assets.
- **Negative**: SQLite is unsuitable for production concurrency; shared hosting production requires
  MySQL/MariaDB.

## References

- `config/database.php` — SQLite as default connection
- `.env.example` — Defaults optimized for single-server deployment
- `docs/infrastructure/deployment.md` — Three deployment paths
- `docs/foundation/installation.md` — CLI installer prerequisites
