# Self-Hosted Single-Tenant Architecture

## Status
Accepted

## Context

Internara targets vocational upper-secondary schools that operate their own IT infrastructure.
Unlike SaaS products where a single codebase serves thousands of tenants, Internara is designed
to be installed once per school — on the school's own server or shared hosting plan.

This deployment model fundamentally shapes architectural decisions:

- **No tenant isolation needed** — there is never more than one "school" per instance. No
  multi-tenant middleware, no tenant-scoped model queries, no per-tenant configuration.
- **No centralized auth** — each school manages its own users, roles, and accounts. No SSO,
  no OAuth provider, no cross-instance user management.
- **No billing or quotas** — no usage limits, no subscription tiers, no feature gating.
  Everything is available from day one.
- **Offline tolerance** — schools may have unreliable internet. The system must work on a
  local network without external connectivity.
- **Minimal IT staff** — most schools do not have a dedicated sysadmin. Installation must be
  wizard-driven, maintenance must be minimal, and backup must be simple.

Two deployment approaches were considered:

1. **Multi-tenant SaaS**: A single hosted instance serving many schools. Tenant isolation
   via database-per-tenant or `tenant_id` scoping. Centralized billing, updates, and
   maintenance. This adds significant infrastructure complexity for the development team
   and creates vendor lock-in for schools.

2. **Self-hosted single-tenant**: Each school runs their own instance. No tenant infrastructure.
   The school owns their data. Updates are manual (git pull + artisan commands). Backup is a
   file copy.

## Decision

Internara is a self-hosted, single-tenant application. Every architectural decision below
follows from this:

### Technical Implications

| Concern | Decision | Rationale |
|---|---|---|
| **Database** | SQLite default, MySQL/PG optional | No database server needed for basic operation. One file = one database. Backup is a file copy. |
| **Queue** | `sync` driver default, Redis optional | No background daemon required for basic operation. Jobs run inline during the HTTP request. |
| **Cache** | `file` or `database` driver default, Redis optional | No external service required. File cache is zero-config. |
| **Session** | `database` driver default, Redis optional | No external service required. Session table auto-created by migration. |
| **Broadcasting** | Log driver default (effectively disabled) | WebSocket server (Reverb) is optional. Notifications degrade to pull-based (page refresh). |
| **File storage** | Local disk default, S3 optional | Single-server: local storage works. Multi-server: optional S3. |
| **Auth** | Local database, bcrypt passwords | No external auth provider. Each school manages its own users. |
| **Installation** | CLI wizard (`setup:install`) + web wizard | No devops skills needed. Single command provisions the system. |
| **Backup** | File copy of SQLite + storage directory | No dump scripts needed for basic backup. |

### Feature Availability Matrix

| Feature | Default (SQLite + sync) | With Redis + Queue Worker | With Reverb |
|---|---|---|---|
| Authentication | ✅ | ✅ | ✅ |
| RBAC | ✅ | ✅ | ✅ |
| Attendance, Logbook | ✅ | ✅ | ✅ |
| Assignments, Grading | ✅ | ✅ | ✅ |
| Reports, Certificates | ✅ | ✅ | ✅ |
| Email notifications | ✅ (sync) | ✅ (async) | ✅ (async) |
| Media conversions | ✅ (sync) | ✅ (async) | ✅ (async) |
| In-app notifications | ✅ (pull/refresh) | ✅ (pull/refresh) | ✅ (real-time) |
| Pulse monitoring | ✅ (request-based) | ✅ (request-based) | ✅ (request-based) |

No feature is *disabled* in the default configuration — some are *synchronous* instead of
*asynchronous*, and real-time updates require a page refresh. Feature parity is maintained
across all deployment tiers.

### Data Sovereignty

Each school's data resides entirely on their own infrastructure:
- Student records, assessment data, company partnerships, and configuration never leave
  the school's server
- No telemetry, no usage reporting, no external API calls for core functionality
- Backup is under the school's control — file copy, S3 sync, or any standard tool

## Consequences

- **Positive**: No multi-tenant infrastructure to build or maintain. No tenant middleware,
  no tenant-scoped queries, no per-tenant configuration.
- **Positive**: Default configuration requires zero external services — SQLite + file cache
  + sync queue. A school can run the application on a $5/month shared hosting plan.
- **Positive**: Data sovereignty is absolute. The school owns their data, their backups,
  and their infrastructure. No vendor lock-in beyond the open-source codebase.
- **Positive**: Performance isolation — one school's load never affects another school.
  Each instance has dedicated resources.
- **Positive**: Simple deployment model — install once, configure, run. No multi-tenant
  orchestration, no tenant provisioning, no billing integration.
- **Negative**: No centralized management — there is no "super admin" view across schools.
  Each school's admin manages their instance independently.
- **Negative**: Updates require manual intervention on each instance — no centralized
  rollout. The school's admin must pull, run migrations, and rebuild assets.
- **Negative**: Hardware resource isolation means each school must provision their own
  server — there is no economy of scale from shared infrastructure.
- **Negative**: SQLite concurrency limits mean schools with more than ~50 concurrent users
  must switch to MySQL or PostgreSQL. This requires a configuration change and migration.

## References

- `config/database.php` — SQLite as default connection
- `.env.example` — defaults optimized for single-server deployment
- `docs/product-definition.md` — product vision, self-hosting principles
- `docs/deployment.md` — three deployment paths
- `docs/installation.md` — CLI installer, prerequisites
