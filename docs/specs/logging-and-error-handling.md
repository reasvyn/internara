# Logging & Error Handling — SmartLogger, PII Masking & Exception Hierarchy

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering SmartLogger dual-channel
> system, PII masking, exception hierarchy, error handling in Actions, and middleware context

## Description

Complete specification of Internara's logging and error handling infrastructure. Defines the
SmartLogger dual-channel architecture (system log + activity log), PII masking rules, bilingual
translation resolution, the dual exception hierarchy (AppException + ModuleException), error
handling in the Action layer via `HandlesActionErrors`, and request context injection via
`LogContext` middleware. These subsystems ensure every mutation is auditable, every error is
safe to display, and debugging is possible without exposing sensitive data.

---

## 1. Problem Statements

### PS-1 — Dual Logging Requirements

The system needs two distinct log outputs: a technical system log for developer debugging
(`storage/logs/laravel.log`) and a business audit trail for school administrators
(`activity_log` table). These serve different audiences with different retention needs. A single
log channel cannot satisfy both without either polluting the audit trail with technical noise or
losing debugging details in the database.

### PS-2 — PII Exposure in Logs

Logs naturally capture user input, IP addresses, and request metadata. If passwords, tokens,
emails, or names appear in plain text in log files, a server breach or unauthorized log access
exposes student and teacher PII. Indonesian data protection regulations (UU PDP) require
reasonable measures to protect personal data.

### PS-3 — Exception Information Leakage

When an unexpected error occurs, raw PHP stack traces or database error messages must never reach
end users. Schools lack technical staff who can interpret these. At the same time, developers need
full error details for debugging. The system must separate user-facing messages from internal
diagnostic information.

### PS-4 — Bilingual Logging

Indonesian schools operate in both Bahasa Indonesia and English. Log entries describing business
events (e.g., "Student submitted logbook", "Assessment finalized") should be understandable by
both local staff and international developers/support. Manual translation of every log message is
unsustainable.

### PS-5 — Action Layer Error Consistency

With 22 modules containing hundreds of Actions, each potentially throwing different exception types,
error handling must be consistent. Without a unified pattern, some Actions would swallow exceptions,
others would leak stack traces, and debugging would require inspecting each Action individually.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a single logging entry point (SmartLogger) for all application logging |
| G2  | Support dual-channel output: system log (technical) + activity log (audit) |
| G3  | Automatically mask PII fields in all log output |
| G4  | Resolve bilingual event descriptions for every logged business event |
| G5  | Enforce a dual exception hierarchy with precise catch-block targeting |
| G6  | Wrap all Action execution in consistent error handling |
| G7  | Inject request context (request ID, user, duration) into every log entry |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time log streaming or WebSocket-based log viewing |
| NG2  | Log aggregation across multiple server instances |
| NG3  | Custom log channels beyond system and activity |
| NG4  | Automatic error reporting to external services (Sentry, Bugsnag) |
| NG5  | Structured JSON logging for the system log channel |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Reviews Audit Trail

**Actor:** School administrator
**Preconditions:** Admin is authenticated with audit log read permission
**Flow:**
1. Admin navigates to Activity Log page
2. System queries `activity_log` table via Spatie ActivityLog
3. Entries show: who did what, on which model, at what time, from which IP
4. PII fields (email, name, IP) are masked in the display
**Postconditions:** Admin can audit system actions without seeing raw PII

### UC-2 — Developer Debugs Production Error

**Actor:** Developer
**Preconditions:** An unexpected exception occurred in production
**Flow:**
1. Exception is caught by `HandlesActionErrors::withErrorHandling()`
2. Known exception types (AppException, ModuleException, etc.) are re-thrown as-is
3. Unknown exceptions are logged via SmartLogger with PII masking, then wrapped in RuntimeException
4. Developer reads `storage/logs/laravel.log` and sees full context: error message, file, line, module
5. User sees only a generic error page, not the stack trace
**Postconditions:** Developer has full diagnostic info; user sees safe error message

### UC-3 — SmartLogger Logs a Business Mutation

**Actor:** System (automatic, from a Command Action)
**Preconditions:** A Command Action is executing a business mutation
**Flow:**
1. Action calls `$this->log('Submitted logbook', $logbook, ['entries' => 5])`
2. BaseAction log() wraps SmartLogger with module auto-detection and PII masking
3. SmartLogger writes to system log (structured context) and activity log (audit trail)
4. Activity log entry includes causer (user), subject (logbook model), payload, IP, User-Agent
5. IP and User-Agent are PII-masked before storage
**Postconditions:** Both log channels have consistent, PII-safe entries

### UC-4 — Business Rule Violation Returns User-Friendly Error

**Actor:** Student attempting to clock in twice
**Preconditions:** Student is already clocked in for today
**Flow:**
1. Student clicks "Clock In" again
2. ClockInAction detects duplicate, calls `$this->fail('Already clocked in today')`
3. `fail()` throws `RejectedException` with message
4. Livewire component catches `RejectedException`, displays flash message
5. No stack trace, no technical details exposed to user
**Postconditions:** Student sees "Already clocked in today"; no exception reaches HTTP layer

### UC-5 — Request Context Enriches All Log Entries

**Actor:** System (automatic, via middleware)
**Preconditions:** Any HTTP request arrives
**Flow:**
1. `LogContext` middleware runs, generates UUID `request_id`
2. Adds `method`, `url`, `ip`, `user_id`, `user_role` to log context
3. After response, adds `duration_ms` and `status` code
4. All subsequent log entries within this request include this context automatically
**Postconditions:** Every log entry can be traced to a specific request, user, and response time

---

## 4. Functional Requirements

### SmartLogger — Core

| ID   | Requirement |
| ---- | ----------- |
| FR-SL1 | `SmartLogger` must be the single entry point for all application logging |
| FR-SL2 | Must provide four severity levels: `success()`, `info()`, `warning()`, `error()` |
| FR-SL3 | Must support fluent chaining: `for()`, `about()`, `withPayload()`, `withContext()`, `module()`, `event()`, `channel()` |
| FR-SL4 | Terminal method `save()` must execute: process event → mask PII → resolve translations → write channels |
| FR-SL5 | Must support three routing modes: `both()` (default), `systemOnly()`, `activityOnly()` |
| FR-SL6 | PII masking must be enabled by default (`withPiiMasking()`) |

### SmartLogger — Dual Channel

| ID   | Requirement |
| ---- | ----------- |
| FR-DC1 | System channel must write to `storage/logs/laravel.log` via `Log` facade |
| FR-DC2 | Activity channel must write to `activity_log` table via Spatie `laravel-activitylog` v5 |
| FR-DC3 | Activity channel must be wrapped in try-catch — database failure must not break the Action |
| FR-DC4 | Activity channel failure must log an error to the system channel with diagnostic context |
| FR-DC5 | System channel must NOT be wrapped in try-catch — unwritable logs must surface immediately |
| FR-DC6 | Activity log must be skipped when no causer is resolved (unless `activityOnly()` was called) |

### SmartLogger — Event Integration

| ID   | Requirement |
| ---- | ----------- |
| FR-EI1 | `event()` must accept a string event name or a `BaseEvent` instance |
| FR-EI2 | When `BaseEvent` is passed, `save()` must dispatch it via Laravel's `event()` helper |
| FR-EI3 | Event's `toPayload()` must be merged into SmartLogger's payload (manual payload takes precedence) |
| FR-EI4 | Event name must be resolved for translation lookup regardless of string or object |

### SmartLogger — Translation Resolution

| ID   | Requirement |
| ---- | ----------- |
| FR-TR1 | When an event name is set, SmartLogger must resolve `log.{eventName}` for current locale |
| FR-TR2 | Must also resolve for the alternative locale (en ↔ id) |
| FR-TR3 | Resolved translations must be injected as `event_description` and `event_description_{locale}` in context |
| FR-TR4 | Missing translation keys must not throw — silently skip injection |

### PII Masking

| ID   | Requirement |
| ---- | ----------- |
| FR-PM1 | `PiiMasker::maskArray()` must recursively mask nested arrays |
| FR-PM2 | Keys containing substrings in `MASKED_KEYS` list must be replaced with `'***'` (full mask) |
| FR-PM3 | `email` key must be partially masked: first 2 chars + `***@domain` |
| FR-PM4 | `phone` key must be partially masked: all but last 4 digits |
| FR-PM5 | `name` key must be partially masked: first initial + last name (e.g., `J. Smith`) |
| FR-PM6 | IPv4 addresses must preserve first two octets: `192.168.***.***` |
| FR-PM7 | IPv6 addresses must preserve first segment: `2001:db8::****` |
| FR-PM8 | User-Agent strings must be truncated to 50 characters with `...` suffix |
| FR-PM9 | `MASKED_KEYS` must include: password, token, secret, api_key, credit_card, ssn, national_id, health_insurance, and 20+ additional sensitive field names |

### Exception Hierarchy

| ID   | Requirement |
| ---- | ----------- |
| FR-EH1 | `AppException` must be the abstract root for application/infrastructure exceptions |
| FR-EH2 | `ModuleException` must be the abstract root for business rule violations |
| FR-EH3 | `ModuleException` must NOT extend `AppException` (independent sibling trees) |
| FR-EH4 | Both trees must use `HasExceptionContext` trait for hint, context, CLI output, PII sanitization |
| FR-EH5 | `AppException` subtree must implement `statusCode()` returning HTTP status code |
| FR-EH6 | `RejectedException` must extend `ModuleException` with status 400 |
| FR-EH7 | `ValidationFailedException` must extend `ActionException` with status 422 |
| FR-EH8 | `UnauthorizedException` must extend `PresentationException` with status 403 |
| FR-EH9 | `InfrastructureException` must default to status 500 and `isUserFacing() = false` |

### Error Handling in Actions

| ID   | Requirement |
| ---- | ----------- |
| FR-AE1 | `HandlesActionErrors` trait must wrap Action execution in try-catch |
| FR-AE2 | Known exception types must be re-thrown without logging (they carry correct semantics) |
| FR-AE3 | Unknown `\Throwable` instances must be logged with PII masking to system channel only |
| FR-AE4 | Unknown exceptions must be re-thrown as `RuntimeException` with original as `$previous` |
| FR-AE5 | `BaseAction::fail()` must throw `RejectedException` (never `RuntimeException`) for business rule violations |
| FR-AE6 | `BaseAction::log()` must auto-derive module name from Action namespace |

### Exception Rendering (bootstrap/app.php)

| ID   | Requirement |
| ---- | ----------- |
| FR-ER1 | `AppException` must render with status from `statusCode()`, user-facing message if `isUserFacing()`, generic message otherwise |
| FR-ER2 | `ModuleException` must always render as HTTP 400 with the exception message |
| FR-ER3 | JSON requests must receive `{"message": "..."}` response |
| FR-ER4 | Non-JSON requests must receive appropriate error page or `abort()` |
| FR-ER5 | Password fields must be excluded from exception flashing (`dontFlash`) |

### LogContext Middleware

| ID   | Requirement |
| ---- | ----------- |
| FR-LC1 | Must generate a UUID `request_id` for every request |
| FR-LC2 | Must inject `method`, `url`, `ip` into log context |
| FR-LC3 | Must inject `user_id` and `user_role` when user is authenticated |
| FR-LC4 | Must measure and inject `duration_ms` and `status` after response |
| FR-LC5 | Must use `Log::withContext()` for automatic injection into all subsequent log entries |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | SmartLogger `save()` must add < 5ms overhead to Action execution |
| NFR-P2 | PII masking must process typical payloads in < 1ms |
| NFR-S1 | No PII (passwords, tokens, emails, IPs) must appear unmasked in any log channel |
| NFR-S2 | Exception messages shown to users must never contain SQL queries, file paths, or stack traces |
| NFR-S3 | `InfrastructureException` must never be user-facing (`isUserFacing() = false`) |
| NFR-R1 | Activity log database failure must not break the calling Action |
| NFR-R2 | Activity log failure must be logged to system channel for diagnosis |
| NFR-R3 | System log failure must propagate (intentional — unwritable logs are critical) |
| NFR-R4 | All exceptions must be logged by default (`shouldReport() = true`) |
| NFR-U1 | Error pages must display user-friendly messages, not technical details |
| NFR-U2 | CLI output must display full context with PII masking for developer debugging |
| NFR-M1 | Exception hierarchy must be flat — no more than 3 levels deep |
| NFR-M2 | Every exception class must be a single file with single responsibility |
| NFR-L1 | All user-facing error messages must use `__()` translation helper |
| NFR-L2 | SmartLogger system channel names must be translatable via `__()` |
| NFR-A1 | Error pages must be keyboard-navigable and screen-reader accessible |
| NFR-A2 | Error page status codes must use semantic HTML (`<main>`, proper headings) |

---

## 6. API / Data Contracts

### 6.1 SmartLogger

```php
// app/Core/Services/SmartLogger.php
final class SmartLogger
{
    // Static factories
    public static function success(string $message, array $context = []): self;
    public static function info(string $message, array $context = []): self;
    public static function warning(string $message, array $context = []): self;
    public static function error(string $message, array $context = []): self;

    // Fluent setters
    public function for(?Model $user): self;
    public function about(?Model $subject): self;
    public function withPayload(array $payload): self;
    public function withContext(array $context): self;
    public function module(string $name): self;
    public function event(string|BaseEvent $event): self;
    public function channel(string $channel): self;

    // Channel routing
    public function systemOnly(): self;
    public function activityOnly(): self;
    public function both(): self;

    // PII control
    public function withPiiMasking(): self;
    public function withoutPiiMasking(): self;

    // Terminal
    public function save(): void;
}
```

### 6.2 PiiMasker

```php
// app/Core/Support/PiiMasker.php
final class PiiMasker
{
    // Full mask — keys containing substrings in MASKED_KEYS → '***'
    // Partial mask — email, phone, name → partially visible
    public static function maskArray(array $data): array;
    public static function maskValue(string $key, mixed $value): mixed;

    // Network/UA masking
    public static function maskIp(?string $ip): ?string;
    public static function maskUserAgent(?string $ua): ?string;
}
```

### 6.3 Exception Hierarchy

```
RuntimeException
├── AppException (abstract)                    — statusCode(): int, HasExceptionContext
│   ├── ActionException (abstract, 400)        — isUserFacing(): true
│   │   └── ValidationFailedException (422)    — default hint: "Please check your input"
│   ├── InfrastructureException (abstract, 500) — isUserFacing(): false
│   └── PresentationException (abstract, 400)  — isUserFacing(): true
│       └── UnauthorizedException (403)        — default hint: "You do not have permission"
└── ModuleException (abstract)                 — statusCode(): int, HasExceptionContext
    └── RejectedException (400)                — business rule violation
```

### 6.4 HasExceptionContext Trait

```php
// app/Core/Exceptions/Concerns/HasExceptionContext.php
trait HasExceptionContext
{
    protected ?string $hint = null;
    protected array $context = [];

    public function withHint(?string $hint): static;
    public function getHint(): ?string;
    public function withContext(array $context): static;
    public function getContext(): array;
    public function toCliOutput(): string;        // formatted for terminal
    public function getSanitizedContext(): array;  // PII-masked context
    public function isUserFacing(): bool;         // default: true
    public function shouldReport(): bool;         // default: true
}
```

### 6.5 HandlesActionErrors Trait

```php
// app/Core/Actions/Concerns/HandlesActionErrors.php
trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed;
    // Known types re-thrown: AppException, ModuleException, RuntimeException,
    //   ValidationException, AuthorizationException, ModelNotFoundException, NotFoundHttpException
    // Unknown \Throwable → logged via SmartLogger (systemOnly, PII-masked) → re-thrown as RuntimeException
}
```

### 6.6 BaseAction Log Shorthand

```php
// app/Core/Actions/BaseAction.php
abstract class BaseAction
{
    protected function log(string $action, ?Model $subject = null, array $payload = []): void;
    // Wraps SmartLogger::info() with:
    //   - both() channels
    //   - withPiiMasking()
    //   - module() auto-derived from namespace (e.g., Auth, Journals, Settings)
    //   - event($action) for translation resolution

    protected function fail(string $message, array $context = []): never;
    // Throws RejectedException — the only acceptable way to signal business rule violation

    protected function dispatchEvent(BaseEvent $event): void;
    // Queues event for dispatch after current DB::transaction() commits
}
```

### 6.7 Exception Rendering (bootstrap/app.php)

```php
// bootstrap/app.php — exception rendering
$exceptions->render(function (AppException $e, Request $request) {
    $status = match (true) {
        $e instanceof UnauthorizedException => 403,
        $e instanceof ValidationFailedException => 422,
        default => 500,
    };
    $message = $e->isUserFacing() ? $e->getMessage() : __('exceptions.unexpected');

    if ($request->expectsJson()) {
        return response()->json(['message' => $message], $status);
    }
    if ($status === 500) {
        return response()->view('errors.500', ['message' => $message], 500);
    }
    abort($status, $message);
});

$exceptions->render(function (ModuleException $e, Request $request) {
    $message = $e->getMessage();
    if ($request->expectsJson()) {
        return response()->json(['message' => $message], 400);
    }
    abort(400, $message);
});
```

### 6.8 LogContext Middleware

```php
// app/Core/Http/Middleware/LogContext.php
class LogContext
{
    public function handle(Request $request, Closure $next): Response;
    // Injects: request_id (UUID), method, url, ip, user_id, user_role
    // After response: duration_ms, status
    // Uses Log::withContext() for automatic injection
}
```

### 6.9 Activity Log Schema (Satie laravel-activitylog v5)

```
activity_log
├── id            BIGINT UNSIGNED  PRIMARY KEY  — auto-increment
├── log_name      VARCHAR(255)    NULLABLE      — module name (e.g., "Journals")
├── description   TEXT            NOT NULL      — human-readable action description
├── subject_type  VARCHAR(255)    NULLABLE      — Eloquent model class
├── subject_id    VARCHAR(36)     NULLABLE      — model UUID
├── event         VARCHAR(255)    NULLABLE      — event name (e.g., "submitted")
├── causer_type   VARCHAR(255)    NULLABLE      — causer model class
├── causer_id     VARCHAR(36)     NULLABLE      — causer user UUID
├── properties    JSON            NULLABLE      — payload, masked IP, masked User-Agent
├── batch_uuid    CHAR(36)        NULLABLE      — batch grouping
└── created_at    TIMESTAMP       NULLABLE      — when the action occurred
    └── INDEXES: subject_type+subject_id, causer_type+causer_id, created_at
```

---

## 7. Design Decisions

### DD-1 — Dual Exception Hierarchy (Reaffirmed from base-classes DD-1)

**Decision:** Two separate exception trees: `AppException` (framework) and `ModuleException` (business).
**Rationale:** Allows precise catch-block targeting. `catch (ModuleException)` catches only business
rule violations; `catch (AppException)` catches only infrastructure/presentation failures. If
ModuleException extended AppException, a catch-all would silently swallow business rejections.
**Trade-off:** More exception classes, but prevents the "catch everything as RuntimeException"
anti-pattern and makes error handling explicit.

### DD-2 — SmartLogger as Single Entry Point

**Decision:** All logging routes through `SmartLogger`, never direct `Log::` or `activity()` calls.
**Rationale:** Centralizes PII masking, channel routing, and translation resolution. Without a
single entry point, developers would forget to enable PII masking or write to the wrong channel.
SmartLogger's fluent API makes the correct usage the easiest usage.
**Trade-off:** One extra abstraction layer. Mitigated by the fluent API being concise and self-documenting.

### DD-3 — Activity Log Failure Does Not Break Actions

**Decision:** Activity log database writes are wrapped in try-catch; failures are logged to system
log only.
**Rationale:** The audit trail is a compliance feature, not a business-critical path. If the
database is temporarily unreachable, the actual business operation (e.g., submitting a logbook)
should still succeed. Breaking the operation for an audit log write would be a worse user
experience.
**Trade-off:** Audit entries may be lost during database outages. Mitigated by the system log
recording the failure with diagnostic context.

### DD-4 — PII Masking by Default

**Decision:** PII masking is enabled by default on every SmartLogger instance; opt-out requires
explicit `withoutPiiMasking()`.
**Rationale:** Secure by default — developers must consciously choose to expose PII, not
accidentally forget to mask it. The `BaseAction::log()` shorthand always enables masking.
**Trade-off:** Slightly reduced debugging information in some logs. Mitigated by partial masking
(email, phone, name) preserving enough for identification without full exposure.

### DD-5 — HasExceptionContext Shared Trait

**Decision:** Both exception trees use the same `HasExceptionContext` trait for hint, context, and
CLI output.
**Rationale:** Consistent interface for exception handling regardless of tree. Livewire catch blocks
can call `getHint()` on any exception. CLI output (tinker, artisan commands) gets formatted output
from both trees. PII sanitization is applied uniformly.
**Trade-off:** Trait coupling between trees. Acceptable because the trait provides pure utility
methods with no business logic.

### DD-6 — LogContext Middleware for Request Tracing

**Decision:** A global `LogContext` middleware generates a UUID `request_id` and injects request
metadata into every log entry.
**Rationale:** In a system with 22 modules, tracing a single user action across multiple log entries
requires a correlation ID. Without request context, debugging production issues requires manual
correlation of timestamps, user IDs, and IP addresses.
**Trade-off:** One extra middleware in the stack (~0.1ms overhead). Negligible.

---

## 8. Success Metrics

### 8.1 Logging Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| SmartLogger usage | 100% of business mutations logged | `grep -r "SmartLogger\|->log(" app/` — all Actions use BaseAction::log() |
| Dual channel | System + activity for business events | SmartLogger default is `both()` |
| Module attribution | Every log entry has module name | `module()` auto-derived from Action namespace |

### 8.2 PII Protection

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Masked fields | 25+ sensitive field names in MASKED_KEYS | `PiiMasker::MASKED_KEYS` count |
| Email masking | `jo***@example.com` format | `PiiMasker::maskEmail()` unit test |
| IP masking | First 2 octets preserved | `PiiMasker::maskIp()` unit test |
| No raw PII in logs | Zero unmasked passwords/tokens in log output | `python3 scripts/scan_security.py` |

### 8.3 Error Safety

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No stack traces to users | 100% of error pages show generic messages | `bootstrap/app.php` exception rendering |
| Exception hierarchy | No class deeper than 3 levels | Class tree inspection |
| Known types re-thrown | No logging for expected exceptions | `HandlesActionErrors` catch strategy |

### 8.4 Reliability

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Activity log graceful failure | Action succeeds on DB outage | SmartLogger `writeActivityLog()` try-catch |
| System log critical | Unwritable logs propagate | No try-catch on `writeSystemLog()` |
| Request tracing | 100% of requests have request_id | `LogContext` middleware on every request |

---

## Quick References

- `app/Core/Services/SmartLogger.php` — single entry point for all logging (336 lines)
- `app/Core/Support/PiiMasker.php` — PII masking rules and methods (187 lines)
- `app/Core/Exceptions/AppException.php` — abstract root for application exceptions
- `app/Core/Exceptions/ModuleException.php` — abstract root for business exceptions
- `app/Core/Exceptions/RejectedException.php` — business rule violation (most common)
- `app/Core/Exceptions/ValidationFailedException.php` — validation failure (422)
- `app/Core/Exceptions/UnauthorizedException.php` — permission denied (403)
- `app/Core/Exceptions/Concerns/HasExceptionContext.php` — shared trait (hint, context, CLI)
- `app/Core/Actions/BaseAction.php` — `log()`, `fail()`, `dispatchEvent()` methods
- `app/Core/Actions/Concerns/HandlesActionErrors.php` — Action error wrapping trait
- `app/Core/Http/Middleware/LogContext.php` — request context injection middleware
- `bootstrap/app.php` — exception rendering configuration
- `docs/architecture/exception-pattern.md` — dual hierarchy rationale and patterns
- `docs/architecture/logging-pattern.md` — SmartLogger architecture, PII masking, translation
- `docs/specs/core-foundation.md` — foundation spec (§6.3, §DD-1)
