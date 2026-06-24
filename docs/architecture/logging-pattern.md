# Logging & Error Handling Pattern

> **Last updated:** 2026-06-10
> **Changes:** initial metadata — no content changes
>
> Complete reference for the SmartLogger dual-channel logging system, PII masking,
> translation resolution, BaseEvent integration, and error handling in Internara.

---

## Table of Contents

1. [SmartLogger Architecture](#1-smartlogger-architecture)
2. [Fluent API](#2-fluent-api)
3. [Channel Routing](#3-channel-routing)
4. [PII Masking](#4-pii-masking)
5. [Translation Resolution](#5-translation-resolution)
6. [BaseEvent Integration](#6-baseevent-integration)
7. [BaseAction log() Shorthand](#7-baseaction-log-shorthand)
8. [HandlesActionErrors Trait](#8-handlesactionerrors-trait)
9. [Graceful Degradation](#9-graceful-degradation)

---

## 1. SmartLogger Architecture

SmartLogger is the single point of entry for all application logging. Every log call — whether from a Command Action, a Livewire component, or error handling infrastructure — routes through this class.

### Dual-Channel Model

Every `save()` call writes to **one or both** of these channels:

| Channel | Destination | Retention | Purpose |
|---------|-------------|-----------|---------|
| **System log** | `storage/logs/laravel.log` (daily rotation) | 14 days | Technical debugging for developers and operations |
| **Activity log** | `activity_log` database table (via Spatie `laravel-activitylog` v5) | 365 days | Business audit trail, queryable by user/action/module/date |

---

## 2. Fluent API

SmartLogger uses a builder pattern. Every method returns `$this` for chaining. The terminal method is always `save()`.

### Static Factory Methods

Four static constructors map to log severity:

| Method | Purpose |
|--------|---------|
| `SmartLogger::success(string $message, array $context = [])` | Success events |
| `SmartLogger::info(string $message, array $context = [])` | Informational events |
| `SmartLogger::warning(string $message, array $context = [])` | Warning conditions |
| `SmartLogger::error(string $message, array $context = [])` | Error conditions |

The `$context` array feeds directly into the system log's structured context. The `$message` serves as both the system log message and the activity log description.

### Chaining Methods

| Method | Parameter | Description |
|--------|-----------|-------------|
| `for(?Model $user)` | `$user` | Sets the causer (actor). Falls back to `Auth::user()` on `save()` |
| `about(?Model $subject)` | `$subject` | Sets the subject model the action was performed on (activity log only) |
| `withPayload(array $payload)` | `$payload` | Merges additional data into the log payload |
| `withContext(array $context)` | `$context` | Merges extra context into the system log context array |
| `module(string $name)` | `$name` | Sets the module name (used as activity log name and system context) |
| `event(string\|BaseEvent $event)` | `$event` | Associates an event name string or a `BaseEvent` instance |
| `channel(string $channel)` | `$channel` | Sets an arbitrary channel label (system context only) |
| `systemOnly()` | — | Routes only to the system log |
| `activityOnly()` | — | Routes only to the activity log |
| `both()` | — | Routes to both channels (default) |
| `withPiiMasking()` | — | Enables PII masking on payloads and request metadata |

### Terminal Method

`save()` executes in this order:

1. **`processEventPayload()`** — If `$this->event` is a `BaseEvent`, dispatch it and merge `toPayload()` into `$this->payload`
2. **`applyPiiMasking()`** — If `$this->maskPii` is true, pass `$this->payload` through `PiiMasker::maskArray()`
3. **`resolveTranslations()`** — If an event name is set, resolve bilingual descriptions via `__()` and inject into `$this->context`
4. **`resolveCauser()`** — Use `$this->causer` or fall back to `Auth::user()`
5. **`writeSystemLog()`** — If `$this->toSystem` is true
6. **`writeActivityLog()`** — If `$this->toActivity` is true and `shouldWriteActivityLog()` passes

---

## 3. Channel Routing

Three routing modes control which channel(s) receive the log entry.

| Mode | System Log | Activity Log | Intended Use |
|------|------------|--------------|--------------|
| `both()` | Written | Written | Default for `BaseAction::log()` — business mutations |
| `systemOnly()` | Written | Skipped | Technical errors, infrastructure failures, unexpected exceptions |
| `activityOnly()` | Skipped | Written | Audit-only events that don't need system log noise |

### Default Behavior

The constructor sets both flags to `true`, meaning **both channels write by default** unless overridden.

### shouldWriteActivityLog Gate

The activity log has an additional guard: if `toActivity` is `false` the activity log is skipped. If there is a causer (resolved from `for()` or `Auth::user()`), the activity log is written. If there is no causer but `activityOnly()` was called, it still writes (explicit opt-in). If there is no causer and both channels are enabled, the activity log is skipped to avoid anonymous audit entries.

---

## 4. PII Masking

When `withPiiMasking()` is called, SmartLogger applies `PiiMasker::maskArray()` to all payload data before it reaches either channel. Request metadata (IP and User-Agent) is also masked at activity log write time.

### Full Mask

Keys matching any entry in `MASKED_KEYS` are replaced with `'***'`. Matching uses `str_contains()` — a key like `old_password` or `api_token` matches because it contains the substring `password` or `token`.

### Partial Mask

Three keys are partially masked (the last visible characters are preserved):

| Key | Method | Example |
|-----|--------|---------|
| `email` | `maskEmail()` | `jo***@example.com` |
| `phone` | `maskPhone()` | `*******8901` |
| `name` | `maskName()` | `J. Smith` |

### IP & User-Agent Masking

Applied inside `resolveRequestMetadata()` when `$this->maskPii` is true:

- **IPv4**: Preserves first two octets (e.g., `192.168.***.***`)
- **IPv6**: Preserves first segment (e.g., `2001:db8::****`)
- **User-Agent**: Truncated to first 50 characters with `...`

### Recursive Masking

`maskArray()` handles nested arrays recursively, applying full and partial masks at every nesting level.

---

## 5. Translation Resolution

SmartLogger automatically resolves bilingual event descriptions when an event name is set.

### Resolution Logic

1. Get the current locale via `App::getLocale()`
2. Look up `__('log.'.$eventName)` for the current locale
3. If a translation exists, inject it as `context['event_description']`
4. Look up the **alternative locale** (en ↔ id)
5. If a translation exists, inject it as `context['event_description_'.$altLocale]`

Both descriptions are embedded in the system log context, making every log entry self-documenting in both languages regardless of the request locale. If no translation key exists, the context keys are simply not set — no error is thrown.

---

## 6. BaseEvent Integration

Events extending `BaseEvent` integrate with SmartLogger through the `event()` method.

### Automatic Dispatch

When a `BaseEvent` instance is passed to `event()`, `save()` dispatches it via Laravel's `event()` helper inside `processEventPayload()`.

### Payload Merging

The event's `toPayload()` results are merged into `$this->payload` **before** any manually-set payload. This means manually-provided payload keys take precedence over event-derived ones.

`toPayload()` extracts all public properties from the event, excluding internal properties (those starting with `__` or named `socket`). Model properties are converted to `{property}_id`, objects with `toArray()` are converted via that method, and scalar values are kept as-is.

### Event Name Resolution

If the event is a `BaseEvent`, `eventName()` provides the translation key. If the event is a string, it is used directly.

---

## 7. BaseAction log() Shorthand

Every Command and Process Action extends `BaseAction`, which provides a convenience `log()` method. This method wraps `SmartLogger::info()` with:

- **Channel**: `both()` (system + activity)
- **PII masking**: always on
- **Module**: auto-derived from the Action's namespace (takes the second namespace segment, e.g. `Auth` from `App\Auth\Login\Actions\LoginAction`)

This allows a one-line call for standard business action logging.

---

## 8. HandlesActionErrors Trait

The `HandlesActionErrors` trait, used by `BaseAction`, provides a consistent pattern for catching unexpected exceptions and logging them before rethrowing.

### Catch Strategy

Known exception types (`RuntimeException`, `AppException`, `ModuleException`, `ValidationException`, `AuthorizationException`, `ModelNotFoundException`, `NotFoundHttpException`) are re-thrown immediately without logging. All other `\Throwable` instances are caught, logged with PII masking to the system channel only, and re-thrown as `RuntimeException` with the original exception preserved as the previous exception.

### Logged Payload

When an unexpected exception is caught, the log includes the error message, the file, and the line where the exception originated.

---

## 9. Graceful Degradation

The activity log channel can fail without breaking the application. The system log channel cannot — unwritable log files should surface immediately.

### Activity Log Failure Handling

In `writeActivityLog()`, the entire Spatie activity log call is wrapped in try-catch. If the database is unreachable, the insert fails gracefully: an error is written to the system log with diagnostic context, execution continues without throwing, and the calling Action completes successfully.

### System Log Failure

The system log channel is **not** wrapped in try-catch. If `storage/logs/` is unwritable, the exception propagates — this is intentional, as a system that cannot log should surface the problem immediately.
