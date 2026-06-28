# ADR-005: SmartLogger Dual-Channel Logging

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format


## Description

A custom SmartLogger writes to both the system log file and the activity log database table simultaneously, with configurable PII masking and deduplication.

## Context

The application needs two distinct kinds of logging:

1. **System logs** — technical debugging information for developers and operations. Stored in `storage/logs/laravel.log` with daily rotation and 14-day retention.
2. **Activity audit logs** — business-level records for administrators, auditors, and GDPR compliance. Stored in the `activity_log` database table via Spatie's `laravel-activitylog` package with 365-day retention, queryable by user, action type, module, and date range.

Using Laravel's `Log::` facade directly for both purposes creates problems: no distinction between debug noise and audit events, no structured context, PII leakage into plain-text log files, and unqueryable audit trails.

Spatie's `activitylog` solves queryability but adds a single point of failure — errors in the database channel would be invisible without system log fallback.

## Decision

All logging goes through `SmartLogger` — a fluent, dual-channel logger that routes every call to either or both channels with automatic PII masking and consistent structured context.

### Architecture

```
BaseAction::log()                    HandlesActionErrors
       │                                    │
       │ SmartLogger::info()               │ SmartLogger::error()
       │ withPiiMasking()                  │ systemOnly()
       │ both()                            │
       └──────────┬────────────────────────┘
                  │
                  ▼
          SmartLogger::save()
              │
        ┌─────┴──────┐
        ▼             ▼
   System Log    Activity Log
   (laravel.log) (activity_log table)
```

### Fluent API

```php
SmartLogger::success('User registered')->for($user)->save();
SmartLogger::info('Profile updated')->for($user)->about($profile)->save();
SmartLogger::warning('Disk space low')->systemOnly()->save();
SmartLogger::error('Payment failed', ['txn' => 'abc'])->activityOnly()->save();
```

### Channel Routing (Three Modes)

| Mode | System Log | Activity Log | Use Case |
|---|---|---|---|
| `both()` | Written | Written | Default for Command Actions |
| `systemOnly()` | Written | Skipped | Technical operations, errors |
| `activityOnly()` | Skipped | Written | Audit-only events |

`BaseAction::log()` uses `both()` by default. `HandlesActionErrors` uses `systemOnly()` to avoid filling the audit trail with error noise.

### PII Masking

When `withPiiMasking()` is enabled, payloads pass through `PiiMasker::maskArray()` before reaching either channel. Masking is key-name-based: `password`, `token`, `secret`, `api_key` and similar keys are fully masked (`***`). Emails, phones, and names are partially masked. IP addresses preserve the first 2 octets.

### Graceful Degradation

The activity log channel is wrapped in try-catch. If the database is unavailable, SmartLogger logs the failure to the system log and continues without throwing. The system log channel is not wrapped — unwritable log files should surface immediately.

### BaseEvent Integration

Events extending `BaseEvent` integrate automatically with SmartLogger via `event()`:
1. Event dispatch happens automatically inside `save()`
2. `eventName()` provides the log translation key
3. `toPayload()` merges public properties as log payload

## Consequences

- **Positive**: Every significant business event is automatically audited with full context — compliance-ready by default.
- **Positive**: PII is masked before reaching log files, even if developers accidentally pass full request payloads.
- **Positive**: Activity logs are queryable via Eloquent scopes — no raw log file grepping for audit questions.
- **Positive**: System logs are enriched with request context (user ID, role, duration) by `LogContext` middleware.
- **Negative**: Two storage systems to manage (log files + database table). Database pruning via scheduled commands is essential.
- **Negative**: PII masking is key-name-based, not content-aware. Non-standard keys bypass masking.

## References

- `app/Core/Support/SmartLogger.php` — Fluent dual-channel logger
- `app/Core/Support/PiiMasker.php` — PII masking engine
- `app/Core/Actions/BaseAction.php` — `log()` convenience wrapper
- `app/Core/Http/Middleware/LogContext.php` — System log enrichment
- `app/Core/Models/ActivityLog.php` — Queryable activity log model
- `app/Core/Events/BaseEvent.php` — Abstract base event with SmartLogger integration
- `config/activitylog.php` — 365-day retention configuration
