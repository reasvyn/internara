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

During a BAN-PDM accreditation visit at SMKN 2 Bandung, the assessor asked who had finalized a disputed grade and from which terminal the change came. The administrator opened the audit-trail view, which queries the `activity_log` table built on Spatie ActivityLog, and each row carried causer, subject, description, timestamp, and IP in one glance. Email, name, and IP rendered masked, so the assessor got evidence without harvesting student PII. The flow exercises FR-LOG-008 together with the masking family FR-LOG-021 through FR-LOG-030, and it only works because business mutations were logged through SmartLogger's activity channel in the first place.

#### UC-LOG-002 — Developer Debugs Production Error

A Monday-morning attendance rush once threw an unexpected database deadlock while a student stared at a spinner. The Livewire boundary inside `HandlesActionErrors::withErrorHandling()` caught it, recognized the throwable as unknown rather than one of the known types — `AppException`, `ModuleException`, and the mapped framework types — and sent it to SmartLogger on the system channel with PII masked before wrapping it in a `RuntimeException` for the framework. The developer later opened `storage/logs/laravel.log` to find message, file, line, and module waiting, while the student had seen only a generic error page with no trace. That split exercises FR-LOG-040 through FR-LOG-043 and the safe-rendering pair FR-LOG-046 through FR-LOG-049.

### 3.2 System Flows

#### UC-LOG-003 — SmartLogger Logs a Business Mutation

When a Command Action finishes a mutation, it calls `$this->log('Submitted logbook', $logbook, ['entries' => 5])` and moves on. Underneath, `BaseAction::log()` wraps SmartLogger with automatic module detection and PII masking, then SmartLogger fans out to two sinks: the system log with structured context and the activity log with causer, subject, payload, masked IP, and masked User-Agent. Both channels hold the same event in their own dialect, PII-safe on arrival. The path exercises FR-LOG-001 through FR-LOG-006 and FR-LOG-013 through FR-LOG-016.

#### UC-LOG-004 — Business Rule Violation Returns User-Friendly Error

A student at SMK Al Hidayah Cirebon tapped Clock In twice in one morning because the first tap seemed to hang. The Action saw the duplicate state and called `$this->fail('Already clocked in today')`, which threw a `RejectedException` that the Livewire component caught and rendered as a plain flash message. No trace leaked, nothing technical reached the HTTP layer, and the student simply understood the rule. That small kindness exercises FR-LOG-036, FR-LOG-044, and FR-LOG-047.

#### UC-LOG-005 — Request Context Enriches All Log Entries

Every HTTP request enters through `LogContextMiddleware`, which mints a UUID `request_id` before anything else runs. The middleware attaches `method`, `url`, and `ip`, adds `user_id` and `user_role` when someone is authenticated, and after the response appends `duration_ms` and `status`. Because injection rides on `Log::withContext()`, every line written during the request inherits that envelope without any Action passing context by hand, so one user gesture stays traceable across entries. The mechanism exercises FR-LOG-051 through FR-LOG-055.

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

A single stray `Log::info($request->all())` in a placement Action once wrote raw student passwords into `laravel.log` on a staging server, and nobody noticed for two weeks. That is the failure this rule exists to make structurally impossible. `SmartLogger` is the only legitimate doorway for application logging; direct `Log::` or `activity()` calls outside SmartLogger and the framework's own internals count as violations. Review catches them today, and a unit suite that constructs loggers only through SmartLogger factories proves the doorway holds.

#### FR-LOG-002 — Four severity levels

Inside SmartLogger the call starts with intent, not plumbing. `SmartLogger::success()`, `info()`, `warning()`, and `error()` each accept a message plus an optional context array and stamp the entry with its level before anything else runs. A unit test per factory asserts the recorded severity survives the pipeline untouched.

#### FR-LOG-003 — Fluent chaining

Early prototypes passed six positional arguments to a log helper and every call site disagreed about their order. The fluent setters — `for()`, `about()`, `withPayload()`, `withContext()`, `module()`, `event()`, `channel()`, with the full signatures fixed in §6.1 — replaced that confusion with a chain that reads left to right. Each setter returns `self`, and a unit test runs the entire chain into `save()` to prove the links compose.

#### FR-LOG-004 — save() pipeline order

`save()` is a small assembly line and its order is load-bearing. Event processing runs first so dispatch and payload merging see complete keys, masking runs next so translation injection and both channel writers only ever touch scrubbed data, and no sink can glimpse raw PII even for a millisecond. One unit test pushes secrets through a single `save()` and asserts masked output in both channels.

#### FR-LOG-005 — Three routing modes

Not every event deserves two audiences. A placement approval goes `both()`, the default for Command Actions, while a queue-retry heartbeat goes `systemOnly()` and a quiet permission grant goes `activityOnly()`; the routing table in §6.2 fixes which mode fits where, with `HandlesActionErrors` living on `systemOnly()`. Separate unit tests assert each mode writes exactly its own channels and nothing else.

#### FR-LOG-006 — Masking on by default

Picture a tired developer at 11pm adding a debug payload with a phone number in it. With masking on by default via `withPiiMasking()`, that mistake is harmless; only a deliberate `withoutPiiMasking()` call, defensible in review, turns protection off. A unit test builds a fresh instance, logs without opting into anything, and asserts the output is already masked.

### 4.2 Dual-Channel Routing

#### FR-LOG-007 — System channel sink

When the night-shift operator at SMK YPT Pringsewu tails the server during enrollment week, there is exactly one place to look. The system channel appends through the `Log` facade into `storage/logs/laravel.log` on a daily rotation with 14-day retention per the SmartLogger ADR, message plus structured context on every line. A feature test performs a `systemOnly()` save and asserts the file output appears.

#### FR-LOG-008 — Activity channel sink

The call travels a different road when auditors are the audience. SmartLogger writes a row into the `activity_log` table through Spatie `laravel-activitylog` v5, with the schema fixed in §6.6 — `log_name` carrying the module name alongside description, subject and causer morphs, `event`, `properties`, and `batch_uuid`. A feature test performs an `activityOnly()` save and asserts exactly one row lands.

#### FR-LOG-009 — Activity failure never breaks Actions

The audit trail is compliance evidence, not the business transaction itself. If the `activity_log` table hiccups while a student submits a logbook, the submission must still succeed — failing homework because the audit write stumbled inverts every priority the school cares about. The narrow exception is a total database outage, where the business write fails on its own; this requirement only promises the logging write is never the cause. A feature test forces the activity write to throw and asserts the Action still succeeds.

#### FR-LOG-010 — Activity failure is diagnosed

Silence about lost audit rows would rot BAN-PDM evidence quietly. So the catch that protects FR-LOG-009 immediately writes to the system channel with the original error context, making every swallowed audit write visible to whoever tails the file log. A feature test breaks the activity write and asserts that diagnostic system entry exists.

#### FR-LOG-011 — System failure propagates

An unwritable system log means total observability loss, and swallowing that would leave operators blind while everything looks green. `writeSystemLog()` therefore carries no try-catch by design: the failure surfaces immediately instead of dissolving into a fallback. Review confirms the absence of the catch, and a feature test exercises the unwritable path.

#### FR-LOG-012 — Causer-less skip

At SMKN 1 Gesi a midnight cron job once flooded the audit trail with hundreds of unattributable system rows nobody could assign to a person. Without a resolved causer, a `both()` save now skips the activity row rather than writing noise — unless the caller passed `activityOnly()`, which is an explicit statement that the row is wanted anyway. A feature test asserts both halves: no row for causer-less `both()`, one row for causer-less `activityOnly()`.

### 4.3 Event Integration

#### FR-LOG-013 — String or object event

`event()` accepts either a plain string or a `BaseEvent` instance, and the difference matters downstream. A string only names the event for translation lookup, while an object additionally dispatches through Laravel. A unit test passes each form and asserts both are accepted.

#### FR-LOG-014 — Dispatch on save

Dispatch happens inside `save()` rather than beside it, so from the caller's viewpoint logging and side effects land atomically — there is no window where the log exists but the listeners never fired. Listener contracts themselves live in [NUCY3](NUCY3-event-system.md). A feature test saves with a `BaseEvent` and asserts the event fired.

#### FR-LOG-015 — Payload merge precedence

Imagine an event whose `toPayload()` reports five logbook entries while the Action author explicitly passes `['entries' => 7]` because a correction arrived mid-flight. The manual payload must win: public properties from `toPayload()` merge underneath explicitly passed `withPayload()` keys. A unit test engineers that collision and asserts the manual value survives.

#### FR-LOG-016 — Name resolution for both forms

Whether the caller handed over an object or a bare string, translation still needs a name. Objects resolve through `eventName()`, strings resolve directly, and either way the lookup in FR-LOG-017 runs unchanged. A unit test asserts description injection for both forms.

### 4.4 Translation Resolution

#### FR-LOG-017 — Current-locale lookup

A coordinator reading the audit trail in Indonesian should see Bahasa, not developer English. When an event name is set, SmartLogger resolves `log.{eventName}` through `__()` for the active locale. A unit test with locale `id` asserts the Indonesian description appears.

#### FR-LOG-018 — Alternative-locale lookup

International support staff and local coordinators read the same row, so one language is not enough. The mirror locale (`en` against `id` and back) resolves inside the same `save()`, and both texts ship in a single entry. A unit test asserts `event_description` and its suffixed mirror key arrive together.

#### FR-LOG-019 — Injection keys

Downstream consumers parse these entries mechanically, so the key names are a contract, not a suggestion: `event_description` for the current locale and `event_description_{locale}` for the mirror. A unit test asserts both keys are present in the written context.

#### FR-LOG-020 — Missing keys are silent

A brand-new event drafted at 5pm should still be observable that evening even though nobody has written its translations yet. Missing keys never throw; injection is silently skipped and the log lands without descriptions. Translation debt must never block observability, and a unit test with an untranslated event name asserts `save()` succeeds with no description keys.

### 4.5 PII Masking

#### FR-LOG-021 — Recursive masking

Payloads nest: a placement array holds a student array that holds contact details, three levels deep. `PiiMasker::maskArray()` descends to any depth by key name rather than sniffing content, per the ADR, so a password buried inside a nested form still becomes `'***'`. A unit test feeds a three-level payload and asserts the masked leaves.

#### FR-LOG-022 — Full mask on sensitive keys

During a UU PDP review at one SMK, an auditor grepped the log archive for `password` and found a confirmation field nobody remembered logging. Substring matching against `MASKED_KEYS` closes that class of leak: `user_password_confirmation` matches via `password` and collapses to `'***'`. A unit test asserts the full mask for each listed key family.

#### FR-LOG-023 — Email partial mask

Full email addresses in logs are a harvesting gift to anyone who steals a backup. The compromise keeps the first two characters and the domain — `johndoe@example.com` becomes `jo***@example.com` — enough for a coordinator to recognize the row, useless for a spammer. A unit test on `maskEmail()` pins that output format.

#### FR-LOG-024 — Phone partial mask

Support staff confirming a parent's identity by phone need the tail, not the whole number. Every digit except the last four is masked, so the log still answers "is this the number ending 7890?" without exposing a callable full number. A unit test on the phone output format locks the behavior.

#### FR-LOG-025 — Name partial mask

A BAN-PDM evidence folder once shipped with full student names in every audit printout, and the school had to reprint the bundle. Names now collapse to first initial plus last name — `John Smith` renders `J. Smith` — which keeps rows attributable across a semester while shrinking exposure. A unit test on the name output format guards the shape.

#### FR-LOG-026 — IPv4 partial mask

The first two octets tell an operator the request came from the school network versus a mobile carrier, which is usually all debugging needs. `192.168.1.10` therefore becomes `192.168.***.***`, preserving rough origin while hiding the host. A unit test on `maskIp()` with a v4 address verifies the cut.

#### FR-LOG-027 — IPv6 partial mask

IPv6 addresses are longer but the instinct is identical: keep the routing prefix, drop the interface identifier. Only the first segment survives — `2001:db8::1` renders `2001:db8::****` — and a unit test on `maskIp()` with a v6 address confirms the boundary.

#### FR-LOG-028 — User-Agent truncation

A raw User-Agent string can run past 200 characters and bloat every `activity_log` row it touches. Truncating to 50 characters plus a `...` suffix keeps the browser-and-OS signal that matters for debugging while bounding row size. A unit test on `maskUserAgent()` with a long UA string asserts the cut.

#### FR-LOG-029 — MASKED_KEYS breadth

Masking is key-name discipline, which is both its strength and its warning. The list holds at least the eight named families — password, token, secret, api_key, credit_card, ssn, national_id, health_insurance — plus 20 or more additional sensitive names, and any payload key that avoids conventional naming slips through. That is why payload keys must use standard names. A unit test asserts both the count and the eight named families.

#### FR-LOG-030 — Mask-before-write guarantee

No channel write path is allowed to bypass masking, and the guarantee is structural rather than habitual: the FR-LOG-004 pipeline order forces every payload through the masker before any sink sees it. A unit test feeds passwords and tokens through `save()` and asserts masked output in both sinks, so a future shortcut in either writer fails loudly.

### 4.6 Exception Hierarchy

#### FR-LOG-031 — AppException root

Every framework-side and infrastructure failure descends from one abstract root that extends `RuntimeException` — never a business-rule complaint, never a validation message. The full tree is drawn in §6.3. A class-contract scan at layer `A` asserts the root exists and stays abstract.

#### FR-LOG-032 — ModuleException root

The business side mirrors the infrastructure side with its own abstract root under `RuntimeException`, reserved for rule violations like full quotas and invalid transitions. It carries no status-code machinery of its own beyond what the shared trait provides. A class-contract scan at layer `A` asserts the root and its abstractness.

#### FR-LOG-033 — Sibling trees, never nested

At SMKN 1 Sintuk Toboh Gadang a well-meaning catch-all once swallowed a quota rejection and rendered it as a 500 page, sending the coordinator hunting a server fault that was really a full workshop. Had `ModuleException` extended `AppException`, every framework catch would keep making that mistake. As siblings, `catch (ModuleException)` can only ever catch business rejections and never an infrastructure failure, and vice versa. A unit test asserts a `ModuleException` is not an instance of `AppException`.

#### FR-LOG-034 — Shared context trait

Both trees speak one dialect for diagnostics. `HasExceptionContext` supplies `withHint()`, `withContext()`, `getHint()`, `getContext()`, `toCliOutput()`, `getSanitizedContext()`, plus `isUserFacing()` defaulting true and `shouldReport()` defaulting true, with signatures fixed in §6.4. Unit tests exercise that API on one exception from each tree so neither side drifts.

#### FR-LOG-035 — statusCode() contract

Rendering cannot guess. Every `AppException` leaf returns its own HTTP status through `statusCode()`, and the renderer in FR-LOG-046 simply consumes that number without branching on class names. A unit test asserts the code for each concrete `AppException` class.

#### FR-LOG-036 — RejectedException as the single business exception

Duplicates, not-found-as-business-rule, rate limits, invalid transitions, invariant violations — all of them throw `RejectedException` at status 400, the sole business-rule voice. The legacy `ConflictException`, `NotFoundException`, and `RateLimitException` names are superseded per the exception-hierarchy ADR precisely because three extra catch branches taught developers to catch `Exception` and swallow everything. A unit test asserts the 400 status, and review rejects any resurrected legacy name.

#### FR-LOG-037 — ValidationFailedException

A student submitting a logbook with an empty date field meets this exception first. It extends `ActionException` in the 400 family, carries status 422, and defaults to the `core.exceptions.validation_failed_hint` translation ("Please review the provided data and try again." in English) so Livewire has something humane to show. A unit test asserts both the status and the resolved hint.

#### FR-LOG-038 — UnauthorizedException

When a student crafts a URL to another student's grade card, this is the wall. `UnauthorizedException` extends `PresentationException` in the 400 family with status 403 and the default `core.exceptions.unauthorized_hint` translation ("You are not authorized to perform this action." in English). A unit test asserts the status and the resolved hint text.

#### FR-LOG-039 — InfrastructureException never user-facing

A certificate PDF worker that loses its storage mount must never narrate mount paths to a student. `InfrastructureException` defaults to status 500 with `isUserFacing()` returning false, so the renderer in FR-LOG-046 substitutes the generic message automatically. A unit test pins both defaults.

### 4.7 Error Handling in Actions

#### FR-LOG-040 — Uniform Action wrapper

With hundreds of Actions across 19 modules, error behavior cannot be a per-author improvisation. `withErrorHandling(callable $callback, string $context)` is the single error boundary every Action executes inside, its signature fixed in §6.5. A feature test runs an Action through the wrapper to prove the boundary holds.

#### FR-LOG-041 — Known types pass through

Logging an expected rejection as if it were a crash would flood the system log with control flow. `AppException`, `ModuleException`, `RuntimeException`, `ValidationException`, `AuthorizationException`, `ModelNotFoundException`, and `NotFoundHttpException` therefore re-throw untouched — they already carry correct semantics. A feature test throws each known type and asserts it emerges unlogged and unwrapped.

#### FR-LOG-042 — Unknown throwables go system-only

An unknown error once wrote a student's session token into the queryable audit table because the handler logged to both channels by habit. Unknown `\Throwable` instances now travel `systemOnly()` with PII masking — they must never land in the user-visible audit trail. A feature test throws an unexpected exception and asserts a masked system-log entry with no accompanying activity row.

#### FR-LOG-043 — Wrapped re-throw preserves causality

The wrapper re-throws as `new RuntimeException($message, previous: $original)`, which keeps the type signaling "unexpected" while the chain stays debuggable through `$previous`. A feature test catches the re-thrown exception and asserts the identity of the original inside it.

#### FR-LOG-044 — fail() means RejectedException

Throwing `RuntimeException` for a full quota misclassifies a business fact as an infrastructure fault and routes it to the wrong renderer. `$this->fail()` is therefore the only acceptable business-rule signal under the C8 invariant — it always throws `RejectedException`. The `scan_violations.py` C8 check plus feature tests asserting `RejectedException` from rule violations keep authors honest.

#### FR-LOG-045 — Module auto-detection

Nobody passes a module name by hand anymore. The namespace segment — `Auth`, `Journals`, `Settings`, whichever Action is logging — becomes the `log_name` automatically, so a copy-pasted `log()` call cannot misattribute its row to the wrong module. A feature test logs from Actions in two different modules and asserts each `log_name`.

### 4.8 Exception Rendering

#### FR-LOG-046 — AppException render path

The renderer registered in `bootstrap/app.php` reads the status the exception already declared: `UnauthorizedException` renders 403, `ValidationFailedException` renders 422, everything else falls to 500, and non-user-facing exceptions show `__('exceptions.unexpected')` instead of their internals. The full contract sits in §6.7. Feature tests hit each branch and assert the status plus the message shown.

#### FR-LOG-047 — ModuleException render path

Business rejections are user-facing by definition — "slot full" is information, not a fault. Every `ModuleException` therefore renders as HTTP 400 carrying the raw exception message, with no generic substitution. A feature test throws a `RejectedException` and asserts the 400 plus the message.

#### FR-LOG-048 — JSON envelope

API consumers get a predictable shape and nothing else: `{"message": "..."}` with the resolved status, no trace frames, no context dump. A feature test sends `Accept: application/json` and asserts the envelope shape exactly.

#### FR-LOG-049 — Non-JSON path

A browser that hits a 500 sees the `errors.500` view, while any other status goes through `abort($status, $message)` so the standard error pages render. The split keeps catastrophic failures styled and ordinary rejections conventional. A feature test asserts view versus abort per status.

#### FR-LOG-050 — dontFlash passwords

After a failed login at a shared lab computer, the password must not linger in the flashed session where the next student could read it from old input. `password`, `password_confirmation`, and `current_password` are excluded via `dontFlash`. A feature test asserts flashed session data excludes all three keys.

### 4.9 Request Context

#### FR-LOG-051 — Correlation ID

One user action can scatter a dozen lines across the system log, and timestamps alone cannot reunite them. `LogContextMiddleware` mints a UUID v4 or v7 `request_id` per request as the join key for those lines. A feature test fires two requests and asserts distinct IDs.

#### FR-LOG-052 — Request basics

The middleware staples the HTTP method, the full URL, and the client IP onto the context before the request reaches any Action. The IP is masked at write time per FR-LOG-026, so attribution survives without exposure. A feature test asserts all three keys are present in context.

#### FR-LOG-053 — Authenticated identity

Guests and members must not look alike in the trail. `user_id` and `user_role` appear only when a user is authenticated; guest requests log without those keys rather than with nulls that invite mis-parsing. Feature tests cover both halves, absent for guests and present for the authenticated.

#### FR-LOG-054 — Response telemetry

Duration and outcome arrive after the fact: once the response is ready, the middleware appends `duration_ms` and the final `status`. It is the cheapest latency signal in the system — no profiler, no extra query, just every request reporting how long it took. A feature test asserts both keys exist after a handled request.

#### FR-LOG-055 — Automatic propagation

Because injection rides on `Log::withContext()`, Action code and SmartLogger calls never pass request context manually — every downstream line inherits the envelope for free. Removing that manual plumbing also removes the forgetting. A feature test writes a downstream log line and asserts it carries the request keys.

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

An assessor paging through a BAN-PDM folder must never stumble on a parent's phone number or a student's token. Neither sink — file nor table — nor the terminal rendering in `getSanitizedContext()` may carry unmasked PII. `PiiMasker` unit tests plus `scan_security.py` over log output prove the zero.

#### NFR-LOG-002 — No internals to users

The two render paths in FR-LOG-046 and FR-LOG-047 enforce this structurally: whatever reaches a user is either an explicit business string or the generic fallback, never a frame or a query. Feature tests assert response bodies contain no `SQLSTATE`, no file path, and no `#0` trace frame.

#### NFR-LOG-003 — Infrastructure never user-facing

The false default on `InfrastructureException` is a one-way door: flipping it per instance needs review justification, because a single user-facing 500 with a mount path undoes months of masking discipline. A unit test pins the default false.

#### NFR-LOG-004 — Friendly error surface

No assertion can capture "friendly", so this row stays manual by design with its layer marker `—`. A human opens the shipped `errors.*` views and judges tone, clarity, and the absence of technical residue.

### 5.2 Reliability

#### NFR-LOG-005 — Audit failure isolation

Stated here as the reliability SLO behind FR-LOG-009: audit loss never causes business failure, full stop. The same feature test that forces the activity write to throw and watches the Action succeed carries this row.

#### NFR-LOG-006 — Audit failure visibility

Every swallowed audit write leaves exactly one diagnostic system entry — the mirror guarantee to FR-LOG-010. Silence would let evidence gaps accumulate unnoticed until accreditation day. FR-LOG-010's feature test is the proof.

#### NFR-LOG-007 — System failure loudness

Total observability loss is always loud — the FR-LOG-011 promise restated as an SLO. A logging pipeline that fails quietly teaches operators to trust empty files. FR-LOG-011's feature test demonstrates the noise.

#### NFR-LOG-008 — Report by default

`shouldReport()` returns true on the shared trait, so every exception volunteers for reporting unless its class opts out in a review-visible declaration. There is no silent-by-default corner for a new exception to hide in. A unit test asserts the trait default.

### 5.3 Operability & Maintainability

#### NFR-LOG-009 — Masked CLI output

Terminal debugging gets the full context with the same masking as the file sinks — `toCliOutput()` formats everything an operator needs while scrubbing secrets identically. A developer pasting terminal output into a chat window therefore leaks nothing the file log would have hidden. A unit test asserts masked secrets in CLI output.

#### NFR-LOG-010 — Flat hierarchy

Depth is counted from `RuntimeException`, and the ceiling is three: `RuntimeException → AppException → ActionException → ValidationFailedException` is the deepest legal shape. Anything deeper means a middle layer has started freelancing as taxonomy. Arch-level review with class-tree inspection enforces the flatness.

#### NFR-LOG-011 — One class per file

An aggregate `Exceptions.php` once hid four unrelated failures behind one import, and a rename broke three modules at once. Exception files now hold a single class with a single responsibility each. `scan_naming.py` at layer `A` proves the split.

### 5.4 Localization

#### NFR-LOG-012 — Translatable user messages

Every string a user can see passes through `__()` with both `en` and `id` lines present, under the D3 invariant — a Javanese-speaking coordinator and an English-speaking contributor read the same rejection in their own language. `LangChecker` plus translation-file presence checks carry the proof.

#### NFR-LOG-013 — Translatable channel names

Channel names surface in tooling and audit exports, so they translate like any user-visible string through `__()`. Translation-file presence plus a unit test asserting the resolution keeps a new channel from shipping English-only.

### 5.5 Accessibility

#### NFR-LOG-014 — Navigable error pages

Keyboard navigation and screen-reader announcements on the shipped error views are manual properties, hence the `—` layer marker. When the UI system in [8XMYS](8XMYS-layout-and-ui-system.md) takes its next pass, it owns the manual audit that proves a blind coordinator can still understand a 500.

#### NFR-LOG-015 — Semantic error markup

The `<main>` landmark and a proper heading hierarchy carrying the status code give assistive technology something honest to announce. Like its sibling above, this is a manual property marked `—`, verified by the same manual audit alongside NFR-LOG-014.

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

Early prototypes used one tree, and every handler had to inspect depth to tell a full quota from a dead database — most handlers stopped bothering and caught `Exception`. Splitting into `AppException` for framework failures and `ModuleException` for business rejections as siblings under `RuntimeException` gives each catch a precise target, reaffirming [SE5Q9](SE5Q9-base-classes.md) and the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md). The price is that exception authors must choose the right tree, which review catches but no compiler enforces.

#### DD-LOG-002 — SmartLogger over Direct Log Calls

Before the single entry point, developers forgot masking or wrote to the wrong channel in roughly every third Action, and audit rows arrived half-masked. Routing all logging through `SmartLogger` centralized masking, routing, and translation in one place, and the fluent API made the correct call the shortest call to write. One abstraction layer remains, kept honest by how concisely it reads.

#### DD-LOG-003 — Audit Failure Never Breaks Business

Failing a logbook submission because the audit write hiccuped inverts the school's priorities — homework first, evidence second. Activity writes are therefore try-caught with failures redirected to the system channel only. Audit rows can still be lost during a real database outage, and the diagnostic entry from FR-LOG-010 is what makes that loss visible instead of silent.

#### DD-LOG-004 — Secure by Default Masking

Masking stays on unless `withoutPiiMasking()` is called explicitly, and `BaseAction::log()` always masks, because protection that requires remembering fails exactly when developers are tired. Partial masking for email, phone, and name preserves enough signal for debugging while satisfying UU PDP expectations. The accepted cost is slightly thinner detail in masked fields.

#### DD-LOG-005 — Shared Context Trait

Livewire catch blocks call `getHint()` on whatever they caught without caring which tree it fell from, and artisan commands render either tree through `toCliOutput()`. Sharing `HasExceptionContext` — hint, context, CLI output, sanitization — across both trees gives handlers one interface and keeps masking uniform. The trait couples the trees, but with pure utility methods and no business logic the coupling never bites.

#### DD-LOG-006 — Middleware Request Tracing

Tracing one student's double-submit across 19 modules by timestamp and IP correlation was miserable enough that developers stopped trying. A global `LogContextMiddleware` mints a UUID `request_id` and injects request and response metadata into every entry, restoring the thread for roughly a tenth of a millisecond per request — overhead nobody has ever been able to measure twice.

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
