# Observability

> **Last updated:** 2026-06-13

## What Is Monitored and Why

The application monitors three categories of information:

- **Technical operations**: errors, warnings, performance metrics — is the application working?
- **Business audit trail**: who did what, when, and to whom — who approved this internship?
- **System health**: disk space, PHP extensions, database connectivity — will the app keep working?

---

## Laravel Pulse for Performance

Laravel Pulse provides a real-time performance dashboard with configurable recorders:

| Recorder      | What It Tracks                       | Threshold |
| ------------- | ------------------------------------ | --------- |
| Slow Queries  | Database queries exceeding threshold | 1,000 ms  |
| Slow Requests | HTTP requests exceeding threshold    | 1,000 ms  |
| Slow Jobs     | Queued jobs exceeding threshold      | 1,000 ms  |
| Exceptions    | All unhandled exceptions             | Always    |
| Cache         | Cache hit/miss ratio                 | Always    |
| Queues        | Queue throughput                     | Always    |

The dashboard is accessible at `/pulse` and restricted to authorized users. Pulse records are ingested synchronously by default (on every request) or asynchronously via Redis for high-traffic deployments. Old records are automatically pruned by the scheduler.

---

## SmartLogger Dual-Channel Approach

SmartLogger is the single entry point for all application logging. It dispatches to two channels simultaneously:

- **System log** — files in `storage/logs/`. Technical details: error messages, stack traces, debug information, infrastructure warnings. What an operator reads to diagnose problems.
- **Activity log** — the `activity_log` database table. Business audit events: user registered, internship approved, role assigned, setting changed. Immutable records pruned by scheduled command after retention period.

### Three Logging Modes

SmartLogger supports three modes via its fluent API:

| Mode                           | System Log | Activity Log | Usage                                        |
| ------------------------------ | ---------- | ------------ | -------------------------------------------- |
| `both()` (default)             | ✅         | ✅           | General purpose logging in Actions           |
| `systemOnly()`                 | ✅         | ❌           | Technical operations (middleware, CLI)       |
| `activityOnly()`               | ❌         | ✅           | Business audit via Actions                   |

### PII Masking

SmartLogger integrates with `PiiMasker` to automatically obfuscate sensitive data before logging:

| Data Type       | Masking Strategy                 | Example                              |
| --------------- | -------------------------------- | ------------------------------------ |
| `password`      | Full mask                        | `********`                           |
| `token`         | Full mask                        | `********`                           |
| `secret`        | Full mask                        | `********`                           |
| `credit_card`   | Full mask                        | `********`                           |
| `email`         | Partial (first 2 chars + domain) | `jo***@example.com`                  |
| `phone`         | Partial (last 4 digits shown)    | `********1234`                       |
| `name`          | Partial (first char only)        | `J***`                               |
| `ip`            | First two octets only            | `192.168.xxx.xxx`                    |

### Fluent API

```php
SmartLogger::info('internship_created')
    ->by(auth()->user())              // causer
    ->on($internship)                 // subject
    ->withPayload(['key' => 'val'])   // context
    ->inModule('program')
    ->activityOnly()                  // or systemOnly(), both()
    ->save();
```

---

## Log Channels

Logging is configured in `config/logging.php`:

| Channel  | Use Case                                      | Rotation       |
| -------- | --------------------------------------------- | -------------- |
| `single` | Development — one file                        | Manual         |
| `daily`  | Production — daily rotation, 14 day retention | Automatic      |
| `slack`  | Critical errors to Slack webhook              | N/A            |
| `stderr` | Docker/container friendly                     | N/A            |
| `syslog` | System syslog integration                     | System-managed |
| `null`   | Discard (testing)                             | N/A            |

Default configuration:

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

Activity log retention is configured in `config/activitylog.php` and pruned by the `system:cleanup` command (default 365 days).

---

## Log Context Enrichment

Every log entry from an HTTP request is automatically enriched by the `LogContext` middleware with:

- Unique request ID
- HTTP method and URL
- Client IP address
- Authenticated user ID and role
- Response duration in milliseconds
- HTTP status code

This enrichment makes log analysis significantly more useful — a slow query can be correlated with the specific request and user that triggered it.

---

## System Health Command

The `php artisan system:health` command performs a 15-point system verification:

1. Environment setup check
2. Setup status verification
3. PHP version is 8.4+
4. Required extensions are loaded
5. Recommended extensions are present
6. PHP memory limit is adequate
7. Database connection works
8. Migration status is up-to-date
9. Storage directories are writable
10. Disk usage is below thresholds
11. Queue table is accessible
12. Cache store responds to read and write
13. Application key is set and valid
14. Storage symlink exists
15. Application is not in maintenance mode

The command outputs a table with pass/fail/warning for each check, or JSON for integration with external monitoring systems (`--json`).

---

## System Cleanup Command

The `php artisan system:cleanup` command runs nightly via the scheduler and prunes:

| Data Source   | Retention | Configuration              |
| ------------- | --------- | -------------------------- |
| Pulse records | 7 days    | `config/pulse.php`         |
| Activity log  | 365 days  | `config/activitylog.php`   |
| Failed jobs   | 7 days    | `config/queue.php`         |
| Notifications | 365 days  | Configurable via settings  |

---

## Where to Find It

- `app/Core/Support/SmartLogger.php` — SmartLogger implementation
- `app/Support/PiiMasker.php` — PII masking logic
- `app/Core/Http/Middleware/LogContext.php` — log context enrichment
- `app/SysAdmin/Observability/Console/Commands/SystemHealthCommand.php` — health command
- `app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php` — cleanup command
- `app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php` — cache warming
- `config/pulse.php` — Pulse configuration
- `config/activitylog.php` — activity log configuration
- `config/logging.php` — logging channel configuration
- [Infrastructure](infrastructure.md) — tier-based infrastructure design
