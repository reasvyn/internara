# Infrastructure Documentation

Deployment, configuration, CI/CD, database, and operations.

- **[Infrastructure Overview](infrastructure.md)** — Deployment options, background process architecture, database and storage
- **[Deployment](deployment.md)** — Three deployment paths (VPS, Docker, shared hosting), production checklist
- **[CI/CD Pipeline](ci-cd.md)** — GitHub Actions workflow, quality gates, artifact management, secrets
- **[Configuration](configuration.md)** — Three-tier configuration system, environment variables, dev vs production
- **[Database Schema](database.md)** — Design philosophy, UUID primary keys, SQLite default, engine comparison, index strategy
- **[Caching Strategy](cache.md)** — Centralized key registry, invalidation, Redis, OpCache
- **[Filesystem](filesystem.md)** — Storage architecture, Spatie Media Library integration, file locations, image conversions
- **[Media Library](media-library.md)** — Collections, conversions, file size limits, queue integration, S3-compatible cloud storage
- **[Routes & Middleware](routes.md)** — Route structure, 17 module-split route files, middleware groups, naming conventions
- **[Session](session.md)** — Session configuration, drivers, security considerations
- **[Notifications](notification.md)** — Multi-channel notification system, CustomDatabaseChannel, mail deliverability, SPF/DKIM
- **[Queue & Workers](queue.md)** — Queue drivers, worker management, Supervisor configuration, job lifecycle, retry/backoff
- **[Testing Guide](testing.md)** — Testing philosophy, feature vs unit test distinction, LazilyRefreshDatabase, code coverage
- **[Observability](observability.md)** — Monitoring categories, Laravel Pulse integration, SmartLogger dual-channel, health checks
- **[Troubleshooting](troubleshooting.md)** — Common issues, diagnostic commands, resolution steps across all subsystems
- **[Scaling Guide](scaling.md)** — Scaling from MVP to 2000+ users, tier transitions, load testing, monitoring thresholds
- **[Backup & Recovery](backup-recovery.md)** — Backup strategies, database dumps, file backup, restoration, point-in-time recovery
- **[Localization](localization.md)** — Supported languages, translation structure, locale resolution, community contribution guide
- **[Developer Tools](tools.md)** — Python scan scripts, CLI flags, output schema, skill integration
