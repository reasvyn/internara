# Blueprint Index

Blueprint documents are focused, implementation-oriented references for specific
infrastructure and configuration concerns. Each blueprint covers one topic in
isolation — install it, configure it, deploy it, or troubleshoot it.

## Infrastructure & Setup

| # | Blueprint | What It Covers |
|---|---|---|
| 01 | [System Requirements](01-system-requirements.md) | PHP version, required extensions, recommended extensions, verification |
| 02 | [Server Deployment](02-server-deployment.md) | Nginx, Apache, PHP-FPM, Supervisor, queue worker, scheduler, Reverb, Docker |
| 03 | [Environment Configuration](03-environment-configuration.md) | Three-tier config (.env / code / runtime), dev vs prod, security hardening |
| 04 | [Database Engine Setup](04-database-engine-setup.md) | SQLite default, MySQL 8, PostgreSQL 14, known differences, connection pooling |

## Storage & Media

| # | Blueprint | What It Covers |
|---|---|---|
| 05 | [File Storage & Media Library](05-file-storage-setup.md) | Storage disks, media collections, image conversions, S3, queue |
| 06 | [Backup & Disaster Recovery](06-backup-disaster-recovery.md) | Database/files backups, restoration, monitoring |

## Operations

| # | Blueprint | What It Covers |
|---|---|---|
| 07 | [Localization & Multi-Language](07-localization.md) | Locale system, translation files, adding languages |
| 08 | [Logging & Observability](08-logging-observability.md) | Log channels, SmartLogger, Pulse, health checks |
| 09 | [Authentication & RBAC](09-authentication-rbac.md) | Auth flow, roles, permissions, policies |
| 10 | [Performance Tuning](10-performance-tuning.md) | Caching, database, queue, PHP-FPM, OpCache, frontend |

## Deployment

| # | Blueprint | What It Covers |
|---|---|---|
| 11 | [Shared Hosting Deployment](11-shared-hosting-deployment.md) | Low-end hosting, feature cuts, manual deployment, cron fallback |

## Reading Order

1. Start with **Blueprint 01** to verify your environment meets requirements.
2. Follow **Blueprint 03** to configure your `.env` and database connection.
3. Run migrations and the setup wizard.
4. Use **Blueprint 02** (VPS) or **Blueprint 11** (shared hosting) when deploying to production.
5. Refer to **Blueprint 04** when choosing or migrating between database engines.

## Conventions

Each blueprint follows the same structure:

```
## Problem / Context
Why this topic matters and when you need it.

## Step-by-Step
Concrete commands, configuration blocks, and code snippets.

## Verification
How to confirm it works (commands, expected output, health checks).

## References
Links to relevant source files, ADRs, and related blueprints.
```

## References

- `docs/en/architecture.md` — 12-layer architecture that these blueprints support
- `docs/en/installation.md` — quick-start guide
- `docs/en/infrastructure.md` — deployment overview
