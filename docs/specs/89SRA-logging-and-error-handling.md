# Logging & Error Handling — SmartLogger, PII Masking & Exception Hierarchy

> **Spec ID:** 89SRA
> **Status:** Full
> **Owner:** Core
> **Depends on:** FB792, SE5Q9

## Description

Defines Internara's logging and error-handling infrastructure: the SmartLogger dual-channel
architecture (system log + activity log), PII masking, bilingual translation resolution, the
sibling exception hierarchy (`AppException` + `ModuleException`), Action-layer error handling
via `HandlesActionErrors`, and request-context injection via `LogContextMiddleware`. Every
mutation is auditable, every error is safe to display, and debugging never exposes sensitive data.

The rationale behind the dual-channel design is recorded in the
[SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md) and the sibling-tree design in the
[exception-hierarchy ADR](../adr/adr-exception-hierarchy.md); implementation contracts live in
[logging-pattern.md](../guides/arch/logging-pattern.md) and
[exception-pattern.md](../guides/arch/exception-pattern.md).

---

## 1. Problem Statements

### PS-1 — Dual Logging Requirements

The system needs two distinct log outputs: a technical system log for developer debugging
(`storage/logs/laravel.log`) and a business audit trail for school administrators
(`activity_log` table). These serve different audiences with different retention needs. A single
log channel cannot satisfy both without either polluting the audit trail with technical noise or
losing debugging detail in the database.
**→ Requirement:** FR-LOG-001 (single entry point), FR-LOG-007/008 (dual sinks),
FR-LOG-009 (graceful degradation).

### PS-2 — PII Exposure in Logs

Logs naturally capture user input, IP addresses, and request metadata. If passwords, tokens,
emails, or names appear in plain text in log files, a server breach or unauthorized log access
exposes student and teacher PII. Indonesian data protection regulations (UU PDP) require
reasonable measures to protect personal data.
**→ Requirement:** FR-LOG-021–030 (PII masking), NFR-LOG-001 (no unmasked PII).

### PS-3 — Exception Information Leakage

When an unexpected error occurs, raw PHP stack traces or database error messages must never
reach end users — schools lack technical staff who can interpret them. At the same time,
developers need full error details for debugging. The system must separate user-facing messages
from internal diagnostic information.
**→ Requirement:** FR-LOG-031–039 (sibling hierarchy), FR-LOG-046–050 (safe rendering),
NFR-LOG-002 (no internals to users).

### PS-4 — Bilingual Logging

Indonesian schools operate in both Bahasa Indonesia and English. Log entries describing business
events (e.g., "Student submitted logbook", "Assessment finalized") should be understandable by
both local staff and international developers. Manual translation of every log message is
unsustainable.
**→ Requirement:** FR-LOG-017–020 (translation resolution), NFR-LOG-012 (translatable messages).

### PS-5 — Action Layer Error Consistency

With 19 modules containing hundreds of Actions, each potentially throwing different exception
types, error handling must be consistent. Without a unified pattern, some Actions would swallow
exceptions, others would leak stack traces, and debugging would require inspecting each Action
individually.
**→ Requirement:** FR-LOG-040–045 (`HandlesActionErrors` + `fail()`/`log()`),
FR-LOG-051–055 (request context).

---

## 2. Goals & Non-Goals

### Goals

- **Single logging entry point** — all application logging flows through SmartLogger. *Why:* centralizes PII masking, channel routing, and translation so the correct usage is the easiest usage.
- **Dual-channel output** — system log (technical) + activity log (audit) from one call. *Why:* developers get debug detail and administrators get a queryable audit trail without either polluting the other.
- **PII masking by default** — every payload is masked before reaching any sink. *Why:* secure by default under UU PDP; developers opt out explicitly instead of forgetting to mask.
- **Bilingual event descriptions** — every logged business event resolves `en` + `id` text. *Why:* local staff and international support read the same audit trail in their own language.
- **Sibling exception hierarchy** — `AppException` and `ModuleException` as independent trees. *Why:* catch blocks target business-rule failures and infrastructure failures independently.
- **Uniform Action error handling** — every Action executes inside `HandlesActionErrors`. *Why:* hundreds of Actions across 19 modules behave identically on unexpected failure.
- **Request context on every entry** — `request_id`, user, duration injected automatically. *Why:* a single user action is traceable across log entries without manual timestamp correlation.

### Non-Goals

- **Real-time log streaming or WebSocket-based log viewing**. *Why:* school-scale debugging works against files and the queryable `activity_log` table; streaming is operational weight with no MVP need.
- **Log aggregation across multiple server instances**. *Why:* single-tenant deployment (one app per school); there is no fleet to aggregate.
- **Custom log channels beyond system and activity**. *Why:* two sinks cover the debug + audit split; extra channels multiply pruning and masking surfaces.
- **Automatic error reporting to external services (Sentry, Bugsnag)**. *Why:* external dependency with no product need at MVP; revisit only if on-call demand appears.
- **Structured JSON logging for the system log channel**. *Why:* plain structured context plus the queryable activity table is sufficient; JSON pipelines belong to a fleet operator.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` stay `—`
here: these five workflows describe human and system behavior around the logging facility, and
their code-testable consequences live on the FR rows they exercise.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-LOG-001 | Admin reviews the audit trail to see who did what, on which record, when, and from where | P0 | — | — |
| UC-LOG-002 | Developer debugs a production error from the system log while the user sees a safe message | P0 | — | — |
| UC-LOG-003 | System logs a business mutation through SmartLogger to both channels with PII masked | P0 | — | — |
| UC-LOG-004 | User hitting a business rule sees a friendly message with no technical detail | P0 | — | — |
| UC-LOG-005 | System enriches every log entry with request context via middleware | P1 | — | — |

### 3.1 Human Workflows

#### UC-LOG-001 — Admin Reviews Audit Trail

**Actor:** School administrator (authenticated, audit-log read permission).
**Preconditions:** Business mutations have been logged through SmartLogger's activity channel.
**Flow:**
1. Admin opens the audit-trail view
2. System queries the `activity_log` table (Spatie ActivityLog): causer, subject, description, timestamp, IP
3. PII fields (email, name, IP) render masked
**Postconditions:** Admin audits system actions without seeing raw PII.
**Exercises:** FR-LOG-008, FR-LOG-021–030.

#### UC-LOG-002 — Developer Debugs Production Error

**Actor:** Developer.
**Preconditions:** An unexpected exception occurred in production.
**Flow:**
1. Exception is caught by `HandlesActionErrors::withErrorHandling()`
2. Known types (`AppException`, `ModuleException`, framework mapped types) re-throw untouched
3. Unknown throwables are logged via SmartLogger (system channel, PII-masked) and wrapped in `RuntimeException`
4. Developer reads `storage/logs/laravel.log` with full context: message, file, line, module
5. User sees only a generic error page, never the trace
**Postconditions:** Developer has full diagnostics; user sees a safe message.
**Exercises:** FR-LOG-040–043, FR-LOG-046–049.

### 3.2 System Flows

#### UC-LOG-003 — SmartLogger Logs a Business Mutation

**Actor:** System (automatic, from a Command Action).
**Preconditions:** A Command Action is executing a mutation.
**Flow:**
1. Action calls `$this->log('Submitted logbook', $logbook, ['entries' => 5])`
2. `BaseAction::log()` wraps SmartLogger with module auto-detection and PII masking
3. SmartLogger writes the system log (structured context) and the activity log (audit trail)
4. Activity entry records causer, subject, payload, masked IP and User-Agent
**Postconditions:** Both channels hold consistent, PII-safe entries.
**Exercises:** FR-LOG-001–006, FR-LOG-013–016.

#### UC-LOG-004 — Business Rule Violation Returns User-Friendly Error

**Actor:** Student attempting to clock in twice (already clocked in today).
**Preconditions:** The duplicate state exists.
**Flow:**
1. Student clicks "Clock In" again
2. The Action detects the duplicate and calls `$this->fail('Already clocked in today')`
3. `fail()` throws `RejectedException`
4. The Livewire component catches it and shows a flash message — no trace, no technical detail
**Postconditions:** Student sees the plain message; no exception reaches the HTTP layer.
**Exercises:** FR-LOG-036, FR-LOG-044, FR-LOG-047.

#### UC-LOG-005 — Request Context Enriches All Log Entries

**Actor:** System (automatic, via middleware).
**Preconditions:** Any HTTP request arrives.
**Flow:**
1. `LogContextMiddleware` generates a UUID `request_id`
2. Injects `method`, `url`, `ip`, plus `user_id`/`user_role` when authenticated
3. After the response, appends `duration_ms` and `status`
4. All log entries within the request carry this context automatically
**Postconditions:** Every entry traces to one request, user, and response time.
**Exercises:** FR-LOG-051–055.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-LOG-001 | `SmartLogger` is the single entry point for all application logging — never direct `Log::` or `activity()` calls | P0 | A | Full |
| FR-LOG-002 | SmartLogger provides four severity levels: `success()`, `info()`, `warning()`, `error()` | P0 | U | Full |
| FR-LOG-003 | SmartLogger supports fluent chaining: `for()`, `about()`, `withPayload()`, `withContext()`, `module()`, `event()`, `channel()` | P0 | U | Full |
| FR-LOG-004 | Terminal method `save()` executes the pipeline in order: process event → mask PII → resolve translations → write channels | P0 | U | Full |
| FR-LOG-005 | SmartLogger supports three routing modes: `both()` (default), `systemOnly()`, `activityOnly()` | P0 | U | Full |
| FR-LOG-006 | PII masking is enabled by default (`withPiiMasking()`); disabling requires explicit `withoutPiiMasking()` | P0 | U | Full |
| FR-LOG-007 | System channel writes to `storage/logs/laravel.log` via the `Log` facade | P0 | F | Full |
| FR-LOG-008 | Activity channel writes to the `activity_log` table via Spatie `laravel-activitylog` v5 | P0 | F | Full |
| FR-LOG-009 | Activity channel is wrapped in try-catch — a database failure must not break the calling Action | P0 | F | Full |
| FR-LOG-010 | Activity channel failure is logged to the system channel with diagnostic context | P0 | F | Full |
| FR-LOG-011 | System channel is NOT wrapped in try-catch — unwritable logs surface immediately | P0 | F | Full |
| FR-LOG-012 | Activity log is skipped when no causer is resolved, unless `activityOnly()` was called | P1 | F | Full |
| FR-LOG-013 | `event()` accepts a string event name or a `BaseEvent` instance | P0 | U | Full |
| FR-LOG-014 | When a `BaseEvent` is passed, `save()` dispatches it via Laravel's `event()` helper | P0 | F | Full |
| FR-LOG-015 | The event's `toPayload()` is merged into SmartLogger's payload; manual payload takes precedence | P0 | U | Full |
| FR-LOG-016 | The event name is resolved for translation lookup regardless of string or object form | P0 | U | Full |
| FR-LOG-017 | When an event name is set, SmartLogger resolves `log.{eventName}` for the current locale | P1 | U | Full |
| FR-LOG-018 | SmartLogger also resolves the alternative locale (`en` ↔ `id`) | P1 | U | Full |
| FR-LOG-019 | Resolved translations are injected as `event_description` and `event_description_{locale}` in context | P1 | U | Full |
| FR-LOG-020 | Missing translation keys never throw — injection is silently skipped | P1 | U | Full |
| FR-LOG-021 | `PiiMasker::maskArray()` recursively masks nested arrays | P0 | U | Full |
| FR-LOG-022 | Keys containing substrings in the `MASKED_KEYS` list are replaced with `'***'` (full mask) | P0 | U | Full |
| FR-LOG-023 | The `email` key is partially masked: first 2 chars + `***@domain` | P0 | U | Full |
| FR-LOG-024 | The `phone` key is partially masked: all but the last 4 digits | P0 | U | Full |
| FR-LOG-025 | The `name` key is partially masked: first initial + last name (e.g., `J. Smith`) | P0 | U | Full |
| FR-LOG-026 | IPv4 addresses preserve the first two octets: `192.168.***.***` | P0 | U | Full |
| FR-LOG-027 | IPv6 addresses preserve the first segment: `2001:db8::****` | P1 | U | Full |
| FR-LOG-028 | User-Agent strings truncate to 50 characters with a `...` suffix | P1 | U | Full |
| FR-LOG-029 | `MASKED_KEYS` includes password, token, secret, api_key, credit_card, ssn, national_id, health_insurance, plus 20+ additional sensitive field names | P0 | U | Full |
| FR-LOG-030 | `save()` masks PII fields before writing to any channel — a unit test asserts masked output | P0 | U | Full |
| FR-LOG-031 | `AppException` is the abstract root for application/infrastructure exceptions | P0 | A | Full |
| FR-LOG-032 | `ModuleException` is the abstract root for business-rule violations | P0 | A | Full |
| FR-LOG-033 | `ModuleException` does NOT extend `AppException` — independent sibling trees under `RuntimeException` | P0 | A | Full |
| FR-LOG-034 | Both trees use the `HasExceptionContext` trait: hint, context, CLI output, PII sanitization | P0 | U | Full |
| FR-LOG-035 | The `AppException` subtree implements `statusCode()` returning the HTTP status code | P0 | U | Full |
| FR-LOG-036 | `RejectedException` extends `ModuleException` with status 400 — the only business-rule exception (supersedes legacy Conflict/NotFound/RateLimit variants) | P0 | U | Full |
| FR-LOG-037 | `ValidationFailedException` extends `ActionException` with status 422 | P0 | U | Full |
| FR-LOG-038 | `UnauthorizedException` extends `PresentationException` with status 403 | P0 | U | Full |
| FR-LOG-039 | `InfrastructureException` defaults to status 500 with `isUserFacing() = false` | P0 | U | Full |
| FR-LOG-040 | The `HandlesActionErrors` trait wraps Action execution in try-catch | P0 | F | Full |
| FR-LOG-041 | Known exception types re-throw without logging — they already carry correct semantics | P0 | F | Full |
| FR-LOG-042 | Unknown `\Throwable` instances are logged with PII masking to the system channel only | P0 | F | Full |
| FR-LOG-043 | Unknown exceptions re-throw as `RuntimeException` with the original as `$previous` | P0 | F | Full |
| FR-LOG-044 | `BaseAction::fail()` throws `RejectedException` — never `RuntimeException` — for business-rule violations | P0 | F | Full |
| FR-LOG-045 | `BaseAction::log()` auto-derives the module name from the Action namespace | P0 | F | Full |
| FR-LOG-046 | `AppException` renders with `statusCode()`; user-facing message if `isUserFacing()`, generic message otherwise | P0 | F | Full |
| FR-LOG-047 | `ModuleException` always renders as HTTP 400 with the exception message | P0 | F | Full |
| FR-LOG-048 | JSON requests receive a `{"message": "..."}` response | P0 | F | Full |
| FR-LOG-049 | Non-JSON requests receive the error view or `abort()` with the status | P0 | F | Full |
| FR-LOG-050 | Password fields are excluded from exception flashing (`dontFlash`) | P0 | F | Full |
| FR-LOG-051 | `LogContextMiddleware` generates a UUID `request_id` for every request | P1 | F | Full |
| FR-LOG-052 | The middleware injects `method`, `url`, and `ip` into the log context | P1 | F | Full |
| FR-LOG-053 | The middleware injects `user_id` and `user_role` when a user is authenticated | P1 | F | Full |
| FR-LOG-054 | The middleware measures and injects `duration_ms` and `status` after the response | P1 | F | Full |
| FR-LOG-055 | Context injection uses `Log::withContext()` so all subsequent entries carry it automatically | P1 | F | Full |

### 4.1 SmartLogger Core

#### FR-LOG-001 — Single entry point

- Any direct `Log::` or `activity()` call outside SmartLogger (and the framework's own internals) is a violation — review-enforced until a dedicated scan rule exists.
- **Verification:** code review + unit tests constructing only via SmartLogger factories.

#### FR-LOG-002 — Four severity levels

- Static factories `success/info/warning/error` accept `(string $message, array $context = [])`.
- **Verification:** unit test per factory asserting the recorded severity.

#### FR-LOG-003 — Fluent chaining

- Every setter returns `self`; full signature list in §6.1.
- **Verification:** unit test chaining the full fluent sequence before `save()`.

#### FR-LOG-004 — save() pipeline order

- Event processing (dispatch + payload merge) happens before masking so payload keys are complete; masking precedes translation injection and channel writes so no sink ever sees raw PII.
- **Verification:** unit test asserting masked output in both channels from one `save()`.

#### FR-LOG-005 — Three routing modes

- `both()` is the default for Command Actions; `systemOnly()` for technical ops and `HandlesActionErrors`; `activityOnly()` for audit-only events. Routing table in §6.2.
- **Verification:** unit tests asserting each mode writes exactly its channels.

#### FR-LOG-006 — Masking on by default

- A fresh SmartLogger instance masks; only an explicit `withoutPiiMasking()` call disables it, and that call must be justifiable in review.
- **Verification:** unit test asserting default instances mask without any opt-in.

### 4.2 Dual-Channel Routing

#### FR-LOG-007 — System channel sink

- Daily rotation with 14-day retention per the SmartLogger ADR; message plus structured context.
- **Verification:** feature test asserting file output for a `systemOnly()` save.

#### FR-LOG-008 — Activity channel sink

- Schema in §6.6 (`log_name`, `description`, subject/causer morphs, `event`, `properties`, `batch_uuid`); `log_name` carries the module name.
- **Verification:** feature test asserting one `activity_log` row per `activityOnly()` save.

#### FR-LOG-009 — Activity failure never breaks Actions

- The audit trail is compliance, not the business-critical path: a temporarily unreachable database must not fail a logbook submission.
- **Edge case:** during a full DB outage the business write itself fails anyway — this row only guarantees the *logging* write is never the cause.
- **Verification:** feature test with the activity write forced to throw; the Action still succeeds.

#### FR-LOG-010 — Activity failure is diagnosed

- The catch in FR-LOG-009 logs to the system channel with the original error context so audit loss is visible.
- **Verification:** feature test asserting a system-log entry when the activity write fails.

#### FR-LOG-011 — System failure propagates

- Unwritable system logs are critical by definition — swallowing them would hide total observability loss.
- **Verification:** review of `writeSystemLog()` (no try-catch) + feature test.

#### FR-LOG-012 — Causer-less skip

- Without a causer the audit row is unattributable noise; `activityOnly()` overrides because the caller explicitly wants the row anyway.
- **Verification:** feature test asserting no activity row for causer-less `both()`, and one row for causer-less `activityOnly()`.

### 4.3 Event Integration

#### FR-LOG-013 — String or object event

- `event(string|BaseEvent $event)`; a string names the event for translation only, an object additionally dispatches.
- **Verification:** unit test passing each form.

#### FR-LOG-014 — Dispatch on save

- Dispatch happens inside `save()` so logging and side effects stay atomic from the caller's view; see [NUCY3](NUCY3-event-system.md) for listener contracts.
- **Verification:** feature test asserting the event fired after `save()` with a `BaseEvent`.

#### FR-LOG-015 — Payload merge precedence

- `toPayload()` public properties merge under explicitly passed `withPayload()` keys — the caller always wins on collision.
- **Verification:** unit test with a colliding key asserting the manual value survives.

#### FR-LOG-016 — Name resolution for both forms

- Objects resolve via `eventName()`; strings resolve directly — either way FR-LOG-017's lookup runs.
- **Verification:** unit test asserting translation injection for both forms.

### 4.4 Translation Resolution

#### FR-LOG-017 — Current-locale lookup

- Key `log.{eventName}` via `__()` for the active locale.
- **Verification:** unit test with locale `id` asserting the Indonesian description.

#### FR-LOG-018 — Alternative-locale lookup

- The mirror locale is resolved in the same `save()` so both texts ship in one entry.
- **Verification:** unit test asserting both `event_description` and the suffixed mirror key.

#### FR-LOG-019 — Injection keys

- Exact context keys `event_description` (current) and `event_description_{locale}` (mirror).
- **Verification:** unit test asserting both keys present in written context.

#### FR-LOG-020 — Missing keys are silent

- A new event without translations yet must still log — translation debt never blocks observability.
- **Verification:** unit test with an untranslated event name asserting `save()` succeeds with no description keys.

### 4.5 PII Masking

#### FR-LOG-021 — Recursive masking

- Masking descends into nested payload arrays to any depth; key-name-based, not content-aware (per ADR).
- **Verification:** unit test with a three-level nested payload asserting masked leaves.

#### FR-LOG-022 — Full mask on sensitive keys

- Substring match against `MASKED_KEYS` (e.g., `user_password_confirmation` matches via `password`).
- **Verification:** unit test asserting `'***'` for each listed key family.

#### FR-LOG-023 — Email partial mask

- `johndoe@example.com` → `jo***@example.com`: enough to identify, not enough to harvest.
- **Verification:** unit test on `maskEmail()` output format.

#### FR-LOG-024 — Phone partial mask

- All but the last 4 digits masked so support can confirm identity by the tail.
- **Verification:** unit test on phone output format.

#### FR-LOG-025 — Name partial mask

- First initial plus full last name balances identifiability against exposure.
- **Verification:** unit test on name output format.

#### FR-LOG-026 — IPv4 partial mask

- First two octets preserved for rough geo/debugging; host octets hidden.
- **Verification:** unit test on `maskIp()` with a v4 address.

#### FR-LOG-027 — IPv6 partial mask

- First segment preserved; remainder hidden.
- **Verification:** unit test on `maskIp()` with a v6 address.

#### FR-LOG-028 — User-Agent truncation

- 50 characters plus `...` keeps browser/OS signal while bounding row size.
- **Verification:** unit test on `maskUserAgent()` with a long UA string.

#### FR-LOG-029 — MASKED_KEYS breadth

- At least the eight named families plus 20+ more; masking is key-name discipline — non-standard key names miss, so payload keys must use conventional names.
- **Verification:** unit test asserting the count and the eight named families.

#### FR-LOG-030 — Mask-before-write guarantee

- No channel write path bypasses masking; the pipeline order in FR-LOG-004 enforces it structurally.
- **Verification:** unit test feeding passwords/tokens through `save()` and asserting masked output in both sinks.

### 4.6 Exception Hierarchy

#### FR-LOG-031 — AppException root

- Abstract, extends `RuntimeException`; framework and infrastructure failures only. Full tree in §6.3.
- **Verification:** class-contract scan asserting the root and abstractness (layer `A`).

#### FR-LOG-032 — ModuleException root

- Abstract, extends `RuntimeException`; business-rule violations only.
- **Verification:** class-contract scan (layer `A`).

#### FR-LOG-033 — Sibling trees, never nested

- `catch (ModuleException)` must never catch an infrastructure failure and vice versa — if `ModuleException` extended `AppException`, a framework catch-all would silently swallow business rejections.
- **Verification:** unit test asserting `ModuleException` is not an instance of `AppException`.

#### FR-LOG-034 — Shared context trait

- `withHint()`, `withContext()`, `getHint()`, `getContext()`, `toCliOutput()`, `getSanitizedContext()`, `isUserFacing()` (default true), `shouldReport()` (default true). Signature in §6.4.
- **Verification:** unit tests calling the trait API on one exception from each tree.

#### FR-LOG-035 — statusCode() contract

- Every `AppException` leaf returns its HTTP status; the renderer in FR-LOG-046 consumes it.
- **Verification:** unit test asserting `statusCode()` per concrete AppException class.

#### FR-LOG-036 — RejectedException as the single business exception

- Duplicates, not-found-as-business-rule, rate limits, invalid transitions, invariant violations all throw `RejectedException` — the legacy `ConflictException`/`NotFoundException`/`RateLimitException` names are superseded per the exception-hierarchy ADR.
- **Verification:** unit test asserting status 400; review rejects resurrected legacy names.

#### FR-LOG-037 — ValidationFailedException

- Extends `ActionException` (400 family) with status 422 and default hint "Please check your input".
- **Verification:** unit test asserting status and hint.

#### FR-LOG-038 — UnauthorizedException

- Extends `PresentationException` (400 family) with status 403 and default hint "You do not have permission".
- **Verification:** unit test asserting status and hint.

#### FR-LOG-039 — InfrastructureException never user-facing

- Status 500, `isUserFacing() = false` — the renderer substitutes the generic message (FR-LOG-046).
- **Verification:** unit test asserting both defaults.

### 4.7 Error Handling in Actions

#### FR-LOG-040 — Uniform Action wrapper

- `withErrorHandling(callable $callback, string $context)` is the single error boundary for Action execution. Signature in §6.5.
- **Verification:** feature test executing an Action through the wrapper.

#### FR-LOG-041 — Known types pass through

- `AppException`, `ModuleException`, `RuntimeException`, `ValidationException`, `AuthorizationException`, `ModelNotFoundException`, `NotFoundHttpException` re-throw untouched — logging them would double-report expected control flow.
- **Verification:** feature test asserting each known type emerges unlogged and unwrapped.

#### FR-LOG-042 — Unknown throwables go system-only

- Logged via SmartLogger `systemOnly()` with PII masking — an unknown error must never land in the user-visible audit trail.
- **Verification:** feature test throwing an unexpected exception and asserting a masked system-log entry with no activity row.

#### FR-LOG-043 — Wrapped re-throw preserves causality

- `new RuntimeException($message, previous: $original)` — the chain stays debuggable while the type signals "unexpected".
- **Verification:** feature test asserting `$previous` identity on the re-thrown exception.

#### FR-LOG-044 — fail() means RejectedException

- `$this->fail()` is the only acceptable way to signal a business-rule violation (C8 invariant) — throwing `RuntimeException` for a rule rejection misclassifies it into the infrastructure tree.
- **Verification:** `scan_violations.py` C8 check + feature tests asserting `RejectedException` from rule violations.

#### FR-LOG-045 — Module auto-detection

- Namespace segment (e.g., `Auth`, `Journals`, `Settings`) becomes the `log_name` without the Action author passing it.
- **Verification:** feature test asserting `log_name` from two different modules.

### 4.8 Exception Rendering

#### FR-LOG-046 — AppException render path

- `UnauthorizedException` → 403, `ValidationFailedException` → 422, default → 500; non-user-facing exceptions render `__('exceptions.unexpected')`. Registered in `bootstrap/app.php` — contract in §6.7.
- **Verification:** feature tests hitting each render branch.

#### FR-LOG-047 — ModuleException render path

- Always 400 with the raw message — business rejections are user-facing by definition.
- **Verification:** feature test asserting 400 + message for a `RejectedException`.

#### FR-LOG-048 — JSON envelope

- API consumers get `{"message": "..."}` with the resolved status — no trace, no context dump.
- **Verification:** feature test with `Accept: application/json` asserting the envelope shape.

#### FR-LOG-049 — Non-JSON path

- 500 renders the `errors.500` view; other statuses `abort($status, $message)`.
- **Verification:** feature test asserting view vs abort per status.

#### FR-LOG-050 — dontFlash passwords

- `password`, `password_confirmation`, `current_password` never flash back into the session on exception.
- **Verification:** feature test asserting flashed session data excludes the three keys.

### 4.9 Request Context

#### FR-LOG-051 — Correlation ID

- UUID v4 (or v7) per request — the join key across system-log lines for one user action.
- **Verification:** feature test asserting distinct `request_id`s across two requests.

#### FR-LOG-052 — Request basics

- HTTP method, full URL, and client IP (IP masked at write time per FR-LOG-026).
- **Verification:** feature test asserting the three keys in context.

#### FR-LOG-053 — Authenticated identity

- `user_id` + `user_role` only when authenticated; guests log without them rather than with nulls.
- **Verification:** feature tests for guest (absent) and authenticated (present) requests.

#### FR-LOG-054 — Response telemetry

- Millisecond duration and final status appended post-response — the cheapest latency signal in the system.
- **Verification:** feature test asserting both keys after a handled request.

#### FR-LOG-055 — Automatic propagation

- `Log::withContext()` means Action and SmartLogger code never passes context manually.
- **Verification:** feature test asserting a downstream log line carries the request keys.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-LOG-001 | No PII (passwords, tokens, emails, IPs) appears unmasked in any log channel | 0 unmasked occurrences | P0 | U | Full |
| NFR-LOG-002 | Exception messages shown to users never contain SQL queries, file paths, or stack traces | 0 leak occurrences | P0 | F | Full |
| NFR-LOG-003 | `InfrastructureException` is never user-facing (`isUserFacing() = false`) | `false` invariant | P0 | U | Full |
| NFR-LOG-004 | Error pages display user-friendly messages, never technical details | 100% generic user surface | P1 | — | — |
| NFR-LOG-005 | Activity-log database failure never breaks the calling Action | Action succeeds | P0 | F | Full |
| NFR-LOG-006 | Activity-log failure is logged to the system channel for diagnosis | 1 diagnostic entry | P0 | F | Full |
| NFR-LOG-007 | System-log failure propagates instead of being swallowed | Exception surfaces | P0 | F | Full |
| NFR-LOG-008 | All exceptions are reported by default (`shouldReport() = true`) | `true` default | P1 | U | Full |
| NFR-LOG-009 | CLI output shows full context with PII masking for developer debugging | Masked + complete | P2 | U | Full |
| NFR-LOG-010 | Exception hierarchy stays flat — no class deeper than 3 levels under `RuntimeException` | ≤3 levels | P1 | A | Full |
| NFR-LOG-011 | Every exception class is a single file with a single responsibility | 1 class per file | P1 | A | Full |
| NFR-LOG-012 | All user-facing error messages use the `__()` translation helper | 100% wrapped | P1 | A | Full |
| NFR-LOG-013 | SmartLogger channel names are translatable via `__()` | 100% wrapped | P2 | A | Full |
| NFR-LOG-014 | Error pages are keyboard-navigable and screen-reader accessible | Manual audit pass | P2 | — | — |
| NFR-LOG-015 | Error-page status codes use semantic HTML (`<main>`, proper headings) | Manual audit pass | P2 | — | — |

### 5.1 Safety & Privacy

#### NFR-LOG-001 — Zero unmasked PII

- Covers both sinks and CLI output (`getSanitizedContext()`). **Verification:** `PiiMasker` unit tests + `scan_security.py` over log output.

#### NFR-LOG-002 — No internals to users

- Enforced structurally by the two render paths (FR-LOG-046/047) — the message reaching the user is either an explicit business string or the generic fallback. **Verification:** feature tests asserting response bodies contain no `SQLSTATE`, path, or `#0` trace frames.

#### NFR-LOG-003 — Infrastructure never user-facing

- The `false` default on `InfrastructureException` cannot be flipped per-instance without review justification. **Verification:** unit test on the default.

#### NFR-LOG-004 — Friendly error surface

- Manual/visual property of the shipped error views — marked `—` because no code assertion captures "friendly". **Verification:** manual review of `errors.*` views.

### 5.2 Reliability

#### NFR-LOG-005 — Audit failure isolation

- Same guarantee as FR-LOG-009, stated as the reliability SLO: audit loss never causes business failure. **Verification:** FR-LOG-009's feature test.

#### NFR-LOG-006 — Audit failure visibility

- Same guarantee as FR-LOG-010: every swallowed audit write leaves exactly one diagnostic system entry. **Verification:** FR-LOG-010's feature test.

#### NFR-LOG-007 — System failure loudness

- Same guarantee as FR-LOG-011: total observability loss is always loud. **Verification:** FR-LOG-011's feature test.

#### NFR-LOG-008 — Report by default

- `shouldReport() = true` on the shared trait; opting out is per-class and review-visible. **Verification:** unit test on the trait default.

### 5.3 Operability & Maintainability

#### NFR-LOG-009 — Masked CLI output

- `toCliOutput()` gives terminal debugging the full context with the same masking as file sinks. **Verification:** unit test asserting masked secrets in CLI output.

#### NFR-LOG-010 — Flat hierarchy

- Depth counted from `RuntimeException`: `RuntimeException → AppException → ActionException → ValidationFailedException` is the maximum at 3. **Verification:** arch-level review / class-tree inspection.

#### NFR-LOG-011 — One class per file

- Single-responsibility exception files; no aggregate `Exceptions.php`. **Verification:** `scan_naming.py` (layer `A`).

### 5.4 Localization

#### NFR-LOG-012 — Translatable user messages

- Every string a user can see passes through `__()` with both `en` and `id` lines (D3 invariant). **Verification:** `LangChecker` + translation-file presence.

#### NFR-LOG-013 — Translatable channel names

- SmartLogger system-channel names resolve via `__()` like any user-visible string. **Verification:** translation-file presence + unit test.

### 5.5 Accessibility

#### NFR-LOG-014 — Navigable error pages

- Keyboard navigation and screen-reader announcements on the shipped error views — manual property, hence `—`. **Verification:** manual audit; owned by the UI system ([8XMYS](8XMYS-layout-and-ui-system.md)) on next pass.

#### NFR-LOG-015 — Semantic error markup

- `<main>` landmark and a proper heading hierarchy carrying the status code — manual property, hence `—`. **Verification:** manual audit alongside NFR-LOG-014.

---

## 6. API / Data Contracts

### 6.1 SmartLogger Fluent API

```php
// app/Modules/Core/Services/SmartLogger.php
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

    // Terminal — pipeline: event → mask → translate → write
    public function save(): void;
}
```

### 6.2 Channel-Routing Table

| Mode | System (`laravel.log`) | Activity (`activity_log`) | Use case |
|------|------------------------|---------------------------|----------|
| `both()` | ✓ | ✓ | Default for Command Actions |
| `systemOnly()` | ✓ | — | Technical ops; errors via `HandlesActionErrors` |
| `activityOnly()` | — | ✓ | Audit-only events |

### 6.3 PII-Masking Rules

| Input | Rule | Example |
|-------|------|---------|
| Keys in `MASKED_KEYS` (password, token, secret, api_key, credit_card, ssn, national_id, health_insurance, +20 more) | Full mask `'***'` (substring match) | `user_password_confirmation` → `***` |
| `email` | First 2 chars + `***@domain` | `johndoe@example.com` → `jo***@example.com` |
| `phone` | All but last 4 digits | `+6281234567890` → `*********7890` |
| `name` | First initial + last name | `John Smith` → `J. Smith` |
| IPv4 | Preserve first two octets | `192.168.1.10` → `192.168.***.***` |
| IPv6 | Preserve first segment | `2001:db8::1` → `2001:db8::****` |
| User-Agent | Truncate to 50 chars + `...` | Long UA → 50 chars + `...` |
| Nested arrays | Recursive `maskArray()` descent | Any depth |

```php
// app/Modules/Core/Support/PiiMasker.php
final class PiiMasker
{
    public static function maskArray(array $data): array;
    public static function maskValue(string $key, mixed $value): mixed;
    public static function maskIp(?string $ip): ?string;
    public static function maskUserAgent(?string $ua): ?string;
}
```

### 6.4 Sibling Exception Tree

```
RuntimeException
├── AppException (abstract)                    — statusCode(): int, HasExceptionContext
│   ├── ActionException (abstract, 400)        — isUserFacing(): true
│   │   └── ValidationFailedException (422)    — default hint: "Please check your input"
│   ├── InfrastructureException (abstract, 500) — isUserFacing(): false
│   │   └── ActionFailedException (500)        — unexpected Action infrastructure failure
│   └── PresentationException (abstract, 400)  — isUserFacing(): true
│       └── UnauthorizedException (403)        — default hint: "You do not have permission"
└── ModuleException (abstract)                 — statusCode(): int, HasExceptionContext
    └── RejectedException (400)                — business rule violation (sole leaf)
```

```php
// app/Modules/Core/Exceptions/Concerns/HasExceptionContext.php
trait HasExceptionContext
{
    protected ?string $hint = null;
    protected array $context = [];

    public function withHint(?string $hint): static;
    public function getHint(): ?string;
    public function withContext(array $context): static;
    public function getContext(): array;
    public function toCliOutput(): string;        // formatted for terminal
    public function getSanitizedContext(): array; // PII-masked context
    public function isUserFacing(): bool;         // default: true
    public function shouldReport(): bool;         // default: true
}
```

### 6.5 HandlesActionErrors Contract

```php
// app/Modules/Core/Actions/Concerns/HandlesActionErrors.php
trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed;
    // Known types re-thrown untouched: AppException, ModuleException, RuntimeException,
    //   ValidationException, AuthorizationException, ModelNotFoundException, NotFoundHttpException
    // Unknown \Throwable → SmartLogger::error()->systemOnly() (PII-masked) → re-thrown as
    //   RuntimeException with the original as $previous
}
```

```php
// app/Modules/Core/Actions/BaseAction.php (logging shorthand)
abstract class BaseAction
{
    protected function log(string $action, ?Model $subject = null, array $payload = []): void;
    // Wraps SmartLogger::info() with both() + withPiiMasking() + module() auto-derived
    // from the Action namespace + event($action) for translation resolution.

    protected function fail(string $message, array $context = []): never;
    // Throws RejectedException — the only acceptable business-rule signal (C8).

    protected function dispatchEvent(BaseEvent $event): void;
    // Queues the event for dispatch after the current DB::transaction() commits.
}
```

### 6.6 Exception Rendering (`bootstrap/app.php`)

```php
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

// $exceptions->dontFlash(['password', 'password_confirmation', 'current_password']);
```

### 6.7 Request-Context Contract

```php
// app/Modules/Core/Http/Middleware/LogContextMiddleware.php
class LogContextMiddleware
{
    public function handle(Request $request, Closure $next): Response;
    // Before: request_id (UUID), method, url, ip, user_id?, user_role? via Log::withContext()
    // After:  duration_ms, status
}
```

### 6.8 Activity-Log Schema (Spatie `laravel-activitylog` v5)

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

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—`;
these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-LOG-001 | Dual exception hierarchy — `AppException` (framework) and `ModuleException` (business) as siblings | P0 | — | — |
| DD-LOG-002 | SmartLogger as the single logging entry point | P0 | — | — |
| DD-LOG-003 | Activity-log failure degrades gracefully and never breaks Actions | P0 | — | — |
| DD-LOG-004 | PII masking enabled by default, explicit opt-out | P0 | — | — |
| DD-LOG-005 | `HasExceptionContext` shared trait across both exception trees | P1 | — | — |
| DD-LOG-006 | `LogContextMiddleware` for per-request tracing | P1 | — | — |

### 7.1 Structure

#### DD-LOG-001 — Dual Exception Hierarchy over a Single Tree

**Decision:** Two separate trees — `AppException` (framework) and `ModuleException` (business) — as siblings under `RuntimeException` (reaffirms [SE5Q9](SE5Q9-base-classes.md) and the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md)).
**Rationale:** Precise catch targeting: `catch (ModuleException)` catches only business rejections, `catch (AppException)` only infrastructure/presentation failures. A single tree forces every handler to inspect the hierarchy to discriminate.
**Trade-off:** Exception authors must choose the correct tree — misclassification is a review catch, not a compiler catch.

#### DD-LOG-002 — SmartLogger over Direct Log Calls

**Decision:** All logging routes through `SmartLogger`; direct `Log::` / `activity()` calls are violations.
**Rationale:** Centralizes masking, routing, and translation — without one entry point developers forget masking or write to the wrong channel, and the fluent API makes correct usage the easiest usage.
**Trade-off:** One abstraction layer; mitigated by the concise, self-documenting fluent API.

#### DD-LOG-003 — Audit Failure Never Breaks Business

**Decision:** Activity writes are try-caught; failures log to the system channel only.
**Rationale:** The audit trail is compliance, not the critical path — failing a logbook submission because the audit write hiccuped is the worse outcome.
**Trade-off:** Audit entries can be lost during DB outages; mitigated by the diagnostic system entry (FR-LOG-010).

#### DD-LOG-004 — Secure by Default Masking

**Decision:** Masking on unless `withoutPiiMasking()` is called explicitly; `BaseAction::log()` always masks.
**Rationale:** Developers must consciously choose exposure rather than accidentally forget protection; partial masking (email/phone/name) preserves enough signal for debugging.
**Trade-off:** Slightly reduced log detail in masked fields — accepted for UU PDP compliance.

#### DD-LOG-005 — Shared Context Trait

**Decision:** Both trees share `HasExceptionContext` (hint, context, CLI output, sanitization).
**Rationale:** One interface for handlers regardless of tree — Livewire catch blocks call `getHint()` on anything; CLI gets formatted output from both trees; sanitization is uniform.
**Trade-off:** Trait coupling between trees; acceptable — pure utility methods, no business logic.

#### DD-LOG-006 — Middleware Request Tracing

**Decision:** Global `LogContextMiddleware` mints a UUID `request_id` and injects request/response metadata into every entry.
**Rationale:** Across 19 modules, tracing one user action across entries needs a correlation ID — otherwise debugging means manual timestamp/user/IP correlation.
**Trade-off:** One middleware (~0.1ms overhead) — negligible.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| SmartLogger adoption | 100% of business mutations logged | `grep` for `->log(` across Actions — all via `BaseAction::log()` |
| Dual-channel coverage | System + activity for business events | SmartLogger default is `both()` |
| Module attribution | Every entry carries a module name | `module()` auto-derived from Action namespace |
| Unmasked PII in logs | 0 occurrences | `PiiMasker` unit tests + `scan_security.py` |
| Masked-key breadth | 25+ sensitive field names in `MASKED_KEYS` | `PiiMasker::MASKED_KEYS` count |
| User-visible traces | 0 stack traces / SQL / paths to users | `bootstrap/app.php` render paths + response-body tests |
| Exception depth | No class deeper than 3 levels | Class-tree inspection |
| Request tracing | 100% of requests carry `request_id` | `LogContextMiddleware` on every request |
| Audit-failure isolation | Action succeeds on activity DB outage | `writeActivityLog()` try-catch feature test |

---

## 9. Roadmap

### Prerequisites

[FB792](FB792-tech-stack.md) (Spatie ActivityLog v5 + Log stack) and [SE5Q9](SE5Q9-base-classes.md)
(`BaseAction`, exception roots, `HasExceptionContext`) — this spec's contracts are implemented on
those bases.

### Build Guide

This spec is satisfied continuously: every Action in every module logs through `BaseAction::log()`,
signals rule violations through `fail()` (`RejectedException`), and executes inside
`HandlesActionErrors`. [logging-pattern.md](../guides/arch/logging-pattern.md) and
[exception-pattern.md](../guides/arch/exception-pattern.md) are the living implementation
references; this spec is the authoritative contract.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [event-system.md](NUCY3-event-system.md) | Event dispatch logs via SmartLogger; listeners run inside the request context |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume key-name-based masking is sufficient because payload keys follow conventional sensitive names (`password`, `token`, …); non-standard keys miss until renamed | Accepted | Maintainer | — |
| A-2 | We assume a single-tenant deployment, so log aggregation across instances is out of scope for this spec's lifetime | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — why two channels + masking
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — why sibling trees + selection guide
- [ADR: Base-class mandate](../adr/adr-base-class-mandate.md) — `BaseAction`/`BaseModel` enforcement
- [Logging pattern](../guides/arch/logging-pattern.md) — SmartLogger and PiiMasker contracts
- [Exception pattern](../guides/arch/exception-pattern.md) — hierarchy, trait, and selection guide
- [Conventions](../conventions.md) — C8 (`fail()`/`RejectedException`) and D3 (i18n) invariants
- [Architecture spec](D2FT3-architecture.md) — governing 4-layer model this spec builds inside
- [Base classes spec](SE5Q9-base-classes.md) — `BaseAction`, exception roots, shared traits
- [Event system spec](NUCY3-event-system.md) — `BaseEvent` integration consumed in §4.3
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements this spec's rows serve
