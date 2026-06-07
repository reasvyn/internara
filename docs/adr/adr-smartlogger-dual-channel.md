# SmartLogger Dual-Channel Logging

> Last updated: 2026-06-05 Changes: Add BaseEvent integration section, update event() signature to
> string|BaseEvent union type

## Status

Accepted

## Context

The application needs two distinct kinds of logging:

1. **System logs**: Technical debugging information — errors, warnings, performance data. Consumed
   by developers and operations teams. Stored in `storage/logs/laravel.log`. Managed by standard log
   rotation (daily, 14-day retention in production).

2. **Activity audit logs**: Business-level records — who did what, when, and to which entity.
   Consumed by administrators, auditors, and GDPR compliance workflows. Stored in the `activity_log`
   database table via Spatie's `laravel-activitylog` package. Requires higher retention (365 days)
   and must be queryable by user, action type, module, and date range.

Using Laravel's `Log::` facade directly for both purposes creates several problems:

- **No distinction** between debug noise and audit-significant events — every `Log::info()` call
  looks the same regardless of whether it's a cache miss or a student registration.
- **No structured context** — module name, entity type, event name, and causer identity are not
  attached to audit entries, making them useless for compliance investigations.
- **PII leakage** — passwords, tokens, email addresses, and phone numbers can leak into plain-text
  log files if developers accidentally log request payloads.
- **Not queryable** — grepping log files does not scale for audit investigations when an
  administrator needs to answer "who approved this registration and when?"

The Spatie `activitylog` package solves the queryability problem but adds its own challenge: it only
logs to the database, not to system log files. Errors in the activity log channel (DB connection
issues, storage full) go undetected because there is no fallback to system logs.

## Decision

All logging goes through `SmartLogger` — a fluent, dual-channel logger that routes every call to
either or both of the system log and the activity audit log, with automatic PII masking and
consistent structured context.

### Architecture

```
BaseAction::log()                    HandlesActionErrors          LogContext (middleware)
       │                                    │                           │
       │ SmartLogger::info()               │ SmartLogger::error()      │ Log::withContext()
       │ withPiiMasking()                  │ systemOnly()              │
       │ both()                            │                           │
       └──────────┬────────────────────────┘                           │
                  │                                                    │
                  ▼                                                    │
          SmartLogger::save()                                          │
              │                                                        │
        ┌─────┴──────┐                                                │
        ▼             ▼                                                │
   System Log    Activity Log     ←── both channels enriched with ─────┘
   (laravel.log)  (activity_log       request_id, user_id, role,
                   database table)     duration_ms, method, url, ip
```

### SmartLogger Fluent API

```php
// Four entry points — map to system log levels internally
SmartLogger::success('User registered')->for($user)->save();
SmartLogger::info('Profile updated')->for($user)->about($profile)->save();
SmartLogger::warning('Disk space low')->systemOnly()->save();
SmartLogger::error('Payment failed', ['txn' => 'abc'])
    ->activityOnly()
    ->save();
```

Each method returns the same fluent builder with these configuration methods:

| Method                     | Purpose                                            | Default                             |
| -------------------------- | -------------------------------------------------- | ----------------------------------- |
| `for(?Model $user)`        | Set the causer (who performed the action)          | `Auth::user()`                      |
| `about(?Model $subject)`   | Set the subject entity (what was acted upon)       | `null`                              |
| `withPayload(array)`       | Attach contextual data (before/after values, IDs)  | `[]`                                |
| `module(string)`           | Set the modules/module name (e.g., "Registration") | Auto-detected from Action namespace |
| `event(string\|BaseEvent)` | Set the event name or BaseEvent object             | Same as message string              |
| `channel(string)`          | Set a custom log channel name                      | `null`                              |
| `withPiiMasking()`         | Enable automatic PII masking on payload            | Disabled                            |

### Channel Routing

Three mutually exclusive modes control where the log entry is written:

| Mode             | System Log | Activity Log | Use Case                                                                                               |
| ---------------- | ---------- | ------------ | ------------------------------------------------------------------------------------------------------ |
| `both()`         | ✅ Written | ✅ Written   | Default for Command Actions. Business events that also need technical visibility.                      |
| `systemOnly()`   | ✅ Written | ❌ Skipped   | Technical operations: middleware, CLI commands, cache misses, error fallbacks.                         |
| `activityOnly()` | ❌ Skipped | ✅ Written   | Audit-only events: status changes, role assignments, setting modifications. Not relevant to debugging. |

`BaseAction::log()` uses `both()` by default — every mutation is logged to both channels.
`HandlesActionErrors` uses `systemOnly()` — unexpected errors are logged only to the system log to
avoid filling the audit trail with noise.

### PII Masking

When `withPiiMasking()` is enabled, the payload is passed through `PiiMasker::maskArray()` before it
reaches either log channel. Masking happens at the **key name** level, not at the value level, so
adding a new sensitive field to a payload automatically masks it.

**Fully masked keys** — entire value replaced with `***`: `password`, `password_confirmation`,
`current_password`, `secret`, `token`, `api_key`, `api_token`, `access_token`, `refresh_token`,
`authorization`, `credit_card`, `card_number`, `cvv`, `ssn`, `national_id` (matched by substring —
any key containing "token" is masked).

**Partially masked keys** — retains enough data for debugging without exposing PII:

| Key     | Original              | Masked              |
| ------- | --------------------- | ------------------- |
| `email` | `johndoe@example.com` | `jo***@example.com` |
| `phone` | `081234567890`        | `******7890`        |
| `name`  | `John Doe`            | `J. Doe`            |

**IP addresses** — first 2 octets preserved: `192.168.1.100` → `192.168.***.***`

**User-Agent strings** — truncated to 50 characters.

The `PiiMasker` operates recursively — nested arrays are traversed and all matching keys are masked
regardless of depth.

### Activity Log Query Scopes

The `ActivityLog` model extends Spatie's `Activity` with module-specific scopes that make the audit
trail queryable:

```php
ActivityLog::forUser($userId) // All actions by a specific user
    ->ofAction('registration_approved') // Filter by event name
    ->forModule('Registration') // Filter by module module
    ->recent(50) // Latest 50 entries
    ->get();
```

Additional scopes: `whereSubject()`, `inLog()`, `lastDays()`, `getGroupedByDay()`.

### System Log Context Enrichment

Every system log entry from HTTP requests is automatically enriched by the `LogContext` middleware
before SmartLogger writes to it:

| Context Field | Source                     | Example                                    |
| ------------- | -------------------------- | ------------------------------------------ |
| `request_id`  | Generated UUID per request | `550e8400-e29b-41d4-a716-446655440000`     |
| `method`      | HTTP method                | `POST`                                     |
| `url`         | Full request URL           | `https://internara.test/api/registrations` |
| `ip`          | Client IP address          | `192.168.1.100`                            |
| `user_id`     | Authenticated user UUID    | `uuid-of-admin`                            |
| `user_role`   | User's primary role        | `admin`                                    |
| `duration_ms` | Request duration           | `145.23`                                   |
| `status`      | HTTP response status       | `200`                                      |

This enrichment is added by middleware at the start of the request lifecycle and is automatically
included in every `Log::` call within that request — no per-action code needed. It enables
correlating a slow query with the specific request and user that triggered it.

### Graceful Degradation

The activity log channel (`writeActivityLog()`) is wrapped in a try-catch block. If the database is
unavailable or the Spatie package throws an exception, SmartLogger:

1. Logs the failure to the system log: `"Failed to write activity log"`
2. Continues without throwing — the application does not break because audit logging failed

The system log channel (`writeSystemLog()`) is not wrapped — if the log file is unwritable, the
application has a storage problem that should surface immediately.

### BaseEvent Integration

Events that need automatic logging integration extend `App\Core\Events\BaseEvent`:

```php
abstract class BaseEvent
{
    use Dispatchable;

    /** Log translation key, e.g. "user_registered" */
    abstract public function eventName(): string;

    /** Auto-extract public properties for log payload:
     *  - Model values → {name}_id
     *  - Objects with toArray() → serialized
     *  - Scalars → pass through
     */
    public function toPayload(): array
    {
        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof Model) {
                $result[$key . '_id'] = $value->getKey();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            } elseif (!is_object($value)) {
                $result[$key] = $value;
            }
        }
        return $result ?? [];
    }
}
```

SmartLogger accepts `BaseEvent` objects directly via `event()`, replacing the string event name:

```php
SmartLogger::success('User registered')->event(new UserRegistered($user))->for($admin)->save();
```

When a `BaseEvent` is passed:

1. **Event dispatch**: `event($baseEvent)` is called inside `save()` — listeners react before the
   audit trail is written.
2. **Event name resolution**: `$baseEvent->eventName()` provides the log translation key (replaces
   the string argument).
3. **Payload merging**: `$baseEvent->toPayload()` is merged first, then explicit `withPayload()`
   values override — explicit payload always wins.

Updated fluent API signature:

| Method                     | Purpose                            | Default                |
| -------------------------- | ---------------------------------- | ---------------------- |
| `event(string\|BaseEvent)` | Event name key or BaseEvent object | Same as message string |

**Backward compatibility**: All existing `event('string_key')` calls work unchanged. The
`string|BaseEvent` union type is purely additive — no existing code needs modification.

### Retention

| Channel      | Retention                                           | Pruning Mechanism                                               |
| ------------ | --------------------------------------------------- | --------------------------------------------------------------- |
| System log   | 14 days (configurable)                              | Laravel `daily` driver, `system:cleanup` prunes older files     |
| Activity log | 365 days (configurable in `config/activitylog.php`) | `php artisan activitylog:clean` via scheduler, `system:cleanup` |

## Consequences

- **Positive**: Every significant business event is automatically audited with full context (who,
  what, when, which module, which entity) — compliance-ready by default.
- **Positive**: PII is masked before it reaches log files. Even if a developer accidentally passes a
  full request payload, passwords and tokens are automatically redacted.
- **Positive**: Activity logs are queryable via the database using pre-built scopes — administrators
  can answer audit questions without reading raw log files.
- **Positive**: System logs are enriched with request context (user ID, role, duration) by
  middleware — no per-action configuration needed.
- **Positive**: `SmartLogger` is the only logger in the codebase. Zero direct `Log::` facade calls
  exist in business logic. `BaseAction::log()` provides a single-line convenience wrapper for all
  Command Actions.
- **Positive**: Graceful degradation keeps the application running even when the activity log
  database is unavailable.
- **Negative**: Two storage systems to manage (log files + database table). The database table
  requires periodic pruning via `system:cleanup` and `activitylog:clean`.
- **Negative**: Fluent interface adds verbosity compared to `Log::info()`. Mitigated by
  `BaseAction::log()` for the most common case (Command Actions).
- **Negative**: If the activity log table grows without pruning, query performance degrades.
  Retention configuration and scheduled pruning are essential.
- **Negative**: PII masking is key-name-based, not content-aware. A sensitive value stored under a
  non-standard key (e.g., `cust_password` instead of `password`) is not masked.

## References

- `app/Core/Support/SmartLogger.php` — fluent dual-channel logger
- `app/Core/Support/PiiMasker.php` — PII masking engine
- `app/Core/Support/HandlesActionErrors.php` — error handling with system-only logging
- `app/Core/Actions/BaseAction.php` — `log()` method wrapping SmartLogger
- `app/Core/Http/Middleware/LogContext.php` — system log context enrichment
- `app/Core/Models/ActivityLog.php` — activity log model with query scopes
- `config/logging.php` — log channel configuration (daily, 14-day retention)
- `config/activitylog.php` — Spatie Activitylog config (365-day retention)
- `app/Core/Events/BaseEvent.php` — abstract base event with Dispatchable, eventName(), toPayload()
- `docs/infrastructure/observability.md` — observability overview (Pulse, health checks, logging)
