# Observability
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## What Is Monitored and Why

The application monitors three categories of information: technical
operations (errors, warnings, performance metrics), business audit trail
(who did what, when, and to whom), and system health (disk space, PHP
extensions, database connectivity, queue availability).

Technical monitoring answers: is the application working? Are requests slow?
Are jobs failing? Are there exceptions? Business audit answers: who approved
this internship? Who changed this user's role? When was this setting
modified? System health answers: will the application keep working? Is disk
space running low? Are all required services running?

## Laravel Pulse for Performance

Laravel Pulse provides a real-time performance dashboard with configurable
recorders:

| Recorder | What It Tracks | Threshold |
|---|---|---|
| Slow Queries | Database queries exceeding threshold | 1,000 ms |
| Slow Requests | HTTP requests exceeding threshold | 1,000 ms |
| Slow Jobs | Queued jobs exceeding threshold | 1,000 ms |
| Exceptions | All unhandled exceptions | Always |
| Cache | Cache hit/miss ratio | Always |
| Queues | Queue throughput | Always |

The dashboard is accessible at `/pulse` and restricted to authorized users.
Pulse records are ingested synchronously by default (on every request) or
asynchronously via Redis for high-traffic deployments. Data retention is
configurable — old records are automatically pruned by the scheduler.

## SmartLogger Dual-Channel Approach

The SmartLogger is the single entry point for all application logging. It
dispatches to two channels simultaneously: the system log (files in
`storage/logs/`) and the activity log (the `activity_log` database table).

The system log captures technical details — error messages, stack traces,
debug information, infrastructure warnings. This is what an operator reads
to diagnose problems.

The activity log captures business-domain audit events — user registered,
internship approved, role assigned, setting changed. This is what an auditor
reads to verify compliance. The activity log is immutable: records are never
updated or deleted through the application. Old records are pruned by a
scheduled command after the retention period.

SmartLogger supports three modes: log to both channels (general purpose),
log to system only (technical operations like middleware and CLI commands),
and log to activity only (business audit via Actions). The fluent API
attaches a causer (who), a subject (what was acted upon), a payload (context
data), a module name, and an event name. Personally identifiable information
can be automatically masked before logging.

## Log Channels

Logging is configured in `config/logging.php` with the following available
channels:

| Channel | Use Case | Rotation |
|---|---|---|
| `single` | Development — one file | Manual |
| `daily` | Production — daily rotation, 14 day retention | Automatic |
| `slack` | Critical errors to Slack webhook | N/A |
| `stderr` | Docker/container friendly | N/A |
| `syslog` | System syslog integration | System-managed |
| `null` | Discard (testing) | N/A |

The default log stack uses the `daily` channel in production:

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=debug
```

Activity log retention is configured in `config/activitylog.php` and pruned
by the `system:cleanup` command (default 365 days).

## Log Context Enrichment

Every log entry from an HTTP request is automatically enriched with
request-scoped metadata: a unique request ID, HTTP method and URL, client IP
address, authenticated user ID and role, response duration in milliseconds,
and HTTP status code. This is added by global middleware and requires no
per-action configuration.

This enrichment makes log analysis significantly more useful — a slow query
can be correlated with the specific request and user that triggered it.

## Health Command Capabilities

The health check command performs a 15-point system verification: checks
environment setup, setup status, PHP version is 8.4+, required extensions are
loaded, recommended extensions are present, PHP memory limit is adequate,
database connection works, migration status is up-to-date, storage
directories are writable, disk usage is below thresholds, queue table is
accessible, cache store responds to read and write, application key is set
and valid, storage symlink exists, and the application is not in maintenance
mode.

The command outputs a table with pass/fail/warning for each check, or JSON
for integration with external monitoring systems.

## Where to Find It

The SmartLogger is at `app/Domain/Core/Support/SmartLogger.php`. The PII
masker is at `app/Domain/Core/Support/PiiMasker.php`. The log context
middleware is at `app/Domain/Core/Http/Middleware/LogContext.php`. The health
command is at `app/Domain/Core/Console/Commands/HealthCommand.php`. The
cleanup command is at
`app/Domain/Core/Console/Commands/CleanupCommand.php`. Pulse configuration
is in `config/pulse.php`. Activity log configuration is in
`config/activitylog.php`. Logging configuration is in `config/logging.php`.
