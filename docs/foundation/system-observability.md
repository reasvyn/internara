# System Observability — Monitoring, Logging & Audit

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite to developer reference; merge from `docs/guide/22-system-observability.md`, `docs/infrastructure/observability.md`

## Description

Reference for the observability stack: SmartLogger (dual-channel logging with PII masking),
Laravel Pulse (performance dashboard), system health checks, audit trail, scheduled maintenance,
and compliance features (GDPR deletion logs, account clone detection).

---

## SmartLogger

SmartLogger is the single entry point for all application logging. It dispatches to two channels
simultaneously:

- **System log** — `storage/logs/`. Technical details: errors, stack traces, debug info.
- **Activity log** — `activity_log` table. Business audit events: who did what, when, to whom.

### Logging Modes

| Mode | System Log | Activity Log | Usage |
| ---- | ---------- | ------------ | ----- |
| `both()` (default) | Yes | Yes | General purpose in Actions |
| `systemOnly()` | Yes | No | Technical operations (middleware, CLI) |
| `activityOnly()` | No | Yes | Business audit via Actions |

### Fluent API

```php
SmartLogger::info('internship_created')
    ->by(auth()->user())        // causer
    ->on($internship)           // subject
    ->withPayload(['key' => 'val'])  // context
    ->inModule('program')
    ->activityOnly()            // or systemOnly(), both()
    ->save();
```

### PII Masking

SmartLogger integrates with `PiiMasker` to obfuscate sensitive data before logging:

| Data Type | Strategy | Example |
| --------- | -------- | ------- |
| `password`, `token`, `secret` | Full mask | `********` |
| `email` | Partial (first 2 chars + domain) | `jo***@example.com` |
| `phone` | Partial (last 4 digits) | `********1234` |
| `name` | Partial (first char) | `J***` |
| `ip` | First two octets | `192.168.xxx.xxx` |

---

## Log Configuration

### Log Channels (`config/logging.php`)

| Channel | Use Case | Rotation |
| ------- | -------- | -------- |
| `single` | Development — one file | Manual |
| `daily` | Production — daily rotation, 14 day retention | Automatic |
| `slack` | Critical errors to Slack webhook | N/A |
| `stderr` | Docker/container friendly | N/A |
| `syslog` | System syslog integration | System-managed |
| `null` | Discard (testing) | N/A |

Default:

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

### Log Context Enrichment

Every HTTP request log entry is enriched by `LogContext` middleware with:

- Unique request ID
- HTTP method and URL
- Client IP address
- Authenticated user ID and role
- Response duration (ms)
- HTTP status code

---

## Laravel Pulse

Real-time performance monitoring dashboard at `/pulse` (admin-only).

### Standard Recorders

| Recorder | What It Tracks | Threshold |
| -------- | -------------- | --------- |
| Slow Queries | Database queries | 1,000 ms |
| Slow Requests | HTTP requests | 1,000 ms |
| Slow Jobs | Queued jobs | 1,000 ms |
| Exceptions | Unhandled exceptions | Always |
| Cache | Hit/miss ratio | Always |
| Queues | Throughput | Always |

### Custom Internara Cards

| Card | Displays |
| ---- | -------- |
| Registrations Card | Total, pending, active, completed registrations |
| System Card | Total users, unread notifications |

### Recording Snapshots

```bash
php artisan pulse:record-snapshots    # Runs hourly via scheduler
```

Records registration lifecycle data and system statistics for custom Pulse cards.

---

## System Health Command

```bash
php artisan system:health             # Human-readable table
php artisan system:health --json      # JSON for monitoring tools
```

Performs15-point verification. See [System Health](system-health.md) for full check list.

---

## Audit Logs

Activity log records every significant action. Navigate to **Admin → Audit Log**.

| Category | Examples |
| -------- | -------- |
| User actions | User created, role assigned, account activated |
| Internship actions | Program created, student registered, grade finalised |
| Assessment actions | Rubric created, assessment finalised |
| System actions | Setting changed, backup created, announcement sent |

### Filtering

- Filter by user, module, action type
- Sort by date (newest/oldest)

### Retention

365 days by default. Pruned by `system:cleanup` via scheduler.

---

## System Cleanup

```bash
php artisan system:cleanup            # With confirmation prompt
php artisan system:cleanup --force    # Skip confirmation
php artisan system:cleanup --log-retention=60  # Custom retention (days)
```

### What It Cleans

| Data Source | Retention | Config |
| ----------- | --------- | ------ |
| Pulse records | 7 days | `config/pulse.php` |
| Activity log | 365 days | `config/activitylog.php` |
| Failed jobs | 7 days | `config/queue.php` |
| Notifications | 365 days | Configurable via settings |
| Expired password resets | Removed | — |
| Stale cache tags | Pruned | — |
| Orphaned media records | Removed | — |
| Old log files | 30 days | — |

---

## Cache Warming

```bash
php artisan system:cache-warm
```

Pre-warms: settings cache, brand cache, config cache, view cache, event cache.

---

## Scheduled Tasks

| Frequency | Command | Purpose |
| --------- | ------- | ------- |
| Every minute | `announcements:publish` | Publish scheduled announcements |
| Hourly | `pulse:record-snapshots` | Record Pulse custom metrics |
| Hourly | `pulse:check` | Pulse health monitoring |
| Daily | `system:cleanup` | Prune old data |
| Daily | `system:backup` | Run scheduled backup (if enabled) |

---

## Compliance Features

### GDPR Deletion Logs

When a user's data is deleted for GDPR compliance, a record is kept in **Admin → GDPR Logs**.
Ensures audit capability without retaining actual user data.

### Account Clone Detection

Scans for users with multiple accounts sharing similar identifying information (email patterns,
names, ID numbers). Helps administrators identify and merge duplicate accounts.

---

## Code Paths

- `app/Core/Support/SmartLogger.php` — SmartLogger implementation
- `app/Core/Support/PiiMasker.php` — PII masking logic
- `app/Core/Http/Middleware/LogContext.php` — Log context enrichment
- `app/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php` — Health command
- `app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php` — Cleanup command
- `app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php` — Cache warming
- `config/pulse.php` — Pulse configuration
- `config/activitylog.php` — Activity log configuration
- `config/logging.php` — Logging channel configuration

---

## Quick References

- [System Health](system-health.md) — Health check, troubleshooting, diagnostic commands
- [Backup & Recovery](backup-recovery.md) — Backup management and restoration
- `docs/foundation/system-observability.md` — Architecture-level observability design
- `docs/specs/system-requirements.md` — Database and dependency requirements
