# Core Domain
> Last updated: 2026-05-27
> Changes: feat(core): implement HasValidationRules contract for shared validation


## Purpose

Core is the architectural foundation — every domain depends on it, it depends on no domain.
Core has zero business logic. Its sole purpose is to provide the structural and infrastructural
guarantees that every business domain builds upon.

Core is organized into four distinct layers, each with a specific responsibility:

```
Layer 3 — Contracts       Interfaces that business domains implement and consume
Layer 4 — Base Classes    Abstract classes that every domain class extends
Layer 4 — Infrastructure  Cross-cutting utilities (logging, cache, security, PII)
Layer 4 — Framework       Laravel-specific bridges (middleware, channels, commands)
```

---

## Design Principles

### 1. Zero Dependency on Business Domains

Core MUST NOT import any class from `App\Domain\{BusinessDomain}\*`. Its dependencies are
limited to:

- PHP 8.4+ standard library
- Laravel framework (`Illuminate\*`)
- Spatie packages (`Spatie\Activitylog\*`, `Spatie\Permission\*`)
- Composer packages (dompdf, livewire, etc.)

This rule is absolute and enforced by code review. Violation means the imported
functionality belongs in a Core contract or a dedicated domain.

### 2. Contracts over Concrete Implementations

Cross-domain communication uses interfaces defined in Core, never concrete classes from
other domains. A domain that needs to send notifications depends on `SendsNotifications`
(the interface), not on `SendNotificationAction` (the implementation). Binding happens
in `DomainServiceProvider`.

### 3. Base Classes Enforce Consistency

Every architectural layer has exactly one base class in Core. All domain classes in that
layer must extend it. This guarantees:

- UUID primary keys on all models (via `BaseModel`)
- Transaction-wrapped mutations with audit logging (via `BaseAction`)
- Testable business rules with zero framework dependencies (via `BaseEntity`)
- Consistent role and ownership authorization (via `BasePolicy`)
- Standardized CRUD behavior with search, filter, sort, pagination (via `BaseRecordManager`)

The single exception is `User` model, which must extend `Authenticatable` (Laravel
requirement) but manually applies the same UUID conventions.

### 4. Failures Are Classified by Origin

Two parallel exception trees exist because two fundamentally different failure modes exist:

| Origin | Root | Example |
|---|---|---|
| Framework / infrastructure | `AppException` | Database down, validation failed, rate limited |
| Business rule violation | `DomainException` | Invalid state transition, duplicate registration |

Domain catch blocks target `DomainException` without accidentally catching framework
errors, and vice versa. Both trees use a shared `HasExceptionContext` trait for consistent
hint, context, and CLI-friendly output.

### 5. All Logging Goes Through a Single Gateway

`SmartLogger` is the sole entry point for all logging. It writes to two channels
simultaneously:

- **System log** (file) — technical debugging, errors, performance
- **Activity log** (database) — business audit trail, compliance

PII masking is applied automatically at the key-name level before data reaches either
channel. This guarantees that passwords, tokens, and personal identifiers never appear
in plain-text log files.

### 6. Cache Keys Are Registered in a Single Source of Truth

Every cache key in the application is declared as a constant in `CacheKeys`. No inline
string literals. This makes cache dependencies discoverable, prevents key collisions,
and enables systematic invalidation. Each constant documents its TTL and invalidation
trigger.

### 7. Gradual Migration Is a First-Class Concern

Core provides patterns that support incremental adoption of architectural ideals:

- `Data` DTO supports `fromArray()` so Action inputs can migrate from `array` to typed
  DTO without breaking existing callers
- `HandlesActionErrors` trait can be used independently of `BaseAction` for Read Actions
  that still need error boundary protection
- Events and listeners are optional — side effects can start inline in Actions and be
  extracted into listeners when a second reaction is needed

---

## Layer 3: Contracts

Contracts are interfaces that define communication protocols between domains. Core
defines them, business domains implement them, and any domain can consume them through
Laravel's service container.

### Enum Contracts

| Contract | Responsibility | Implemented By |
|---|---|---|
| `LabelEnum` | Human-readable label for UI display (`label(): string`) | All enums |
| `StatusEnum` | State machine lifecycle (`canTransitionTo()`, `validTransitions()`, `isTerminal()`) | Status enums (account, internship, report, etc.) |
| `ColorableEnum` | CSS color variant for UI badges (`color(): string`) | Status enums with visual variants |

Every enum in the codebase implements `LabelEnum`. State machine enums additionally
implement `StatusEnum`. This separates the concern of "what label to display" from
"what transitions are allowed" — a status enum can be used for both UI rendering and
lifecycle validation without mixing responsibilities.

### Service Contracts

| Contract | Responsibility | Bound To |
|---|---|---|
| `SendsNotifications` | Dispatch a notification to a user | `SendNotificationAction` (User domain) |

Service contracts follow the interface-segregation principle — single method, narrow
purpose. Binding is configured in `DomainServiceProvider::register()`.

### Proposed Addition: Validation Rules Contract

```php
interface HasValidationRules
{
    public static function rules(?string $excludeId = null): array;
    public static function messages(): array;
}
```

This formalizes the pattern of sharing validation rules between Livewire Form Objects
and HTTP Form Requests. Entities that carry business rules can also expose validation
rules, eliminating duplication across UI layers.

---

## Layer 4: Base Classes

### BaseModel

Abstract Eloquent model that every domain model extends. Provides:

- UUID primary key generation via `HasUuids` (ordered UUIDs for B-tree efficiency)
- Non-incrementing key type (`string`)
- Consistent `$keyType` across all models

The `User` model is the sole exception — it extends `Authenticatable` for Laravel's
authentication system but applies `HasUuids` manually and overrides `getIncrementing()`
and `getKeyType()` to maintain UUID consistency.

Foreign keys use `foreignUuid()->constrained()` in all migrations.

### BaseAction

Abstract action class for Command and Process Actions. Provides:

- `transaction()` — wraps mutations in a database transaction
- `log()` — dual-channel audit logging via SmartLogger with PII masking
- `moduleName()` — auto-detects the owning domain from namespace

Read Actions do not extend BaseAction — they have no need for transactions or logging.
Process Actions extend BaseAction and compose multiple Command/Read Actions via
constructor injection.

### BaseEntity

Abstract readonly class for business rule objects. Provides:

- `fromModel(Model): static` — the single bridge between persistence and domain logic
- Forces `final readonly` subclass contract

Subclasses contain zero framework dependencies — no Eloquent, no Facades, no Container.
Business rules are methods that operate on constructor-injected state only.

### BaseState

Extension of BaseEntity for state machine entities. Adds:

- `isState(string $state): bool` — checks if current status matches
- `isStateIn(array $states): bool` — checks if current status is within a set

State transitions are validated by `StatusEnum`, not by the entity. The entity captures
the current snapshot; the enum defines allowed transitions.

### BasePolicy

Abstract authorization class. Bundles two traits:

| Trait | Methods |
|---|---|
| `AuthorizesRoles` | `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `hasAnyOfRoles()` |
| `AuthorizesOwnership` | `isOwner()`, `isOwnerOrAdmin()`, `isRelatedThrough()` |

Super admin bypasses all gates via `Gate::before()` — no policy check runs against
super admins.

### BaseRecordManager

Abstract Livewire component for CRUD list pages. Provides:

- Pagination via `WithPagination`
- Sortable columns with whitelist protection
- Text search with overrideable `applySearch()`
- Filterable with overrideable `applyFilters()`
- Record selection for bulk operations
- `performBulkAction()` with optional transaction wrapping
- `performMassAction()` for query-scoped operations

Subclasses only need to define `headers()` (column definitions) and `query()` (base
query builder).

### BaseController

Abstract marker class for HTTP controllers. Currently empty — exists to maintain
layer consistency and provide a target for future cross-cutting concerns (response
formatting, request timing, etc.).

### FormRequest

Abstract form request that extends Laravel's `FormRequest`. Overrides
`failedValidation()` to throw `ValidationFailedException` (an AppException subtype)
instead of Laravel's default redirect behavior. This ensures consistent error handling
regardless of whether the request came from a browser or an API client.

---

## Layer 4: Infrastructure / Support

### SmartLogger (Dual-Channel Logger)

Design:

- **Entry points**: `SmartLogger::success()`, `info()`, `warning()`, `error()` —
  four static factories that create a fluent builder
- **Channel routing**: `systemOnly()`, `activityOnly()`, `both()` — three modes
  that control where the log entry is written
- **Context enrichment**: `for()`, `about()`, `withPayload()`, `module()`, `event()` —
  fluent setters for structured context
- **PII masking**: `withPiiMasking()` — automatically masks sensitive keys before
  writing to either channel
- **Graceful degradation**: Activity log channel is wrapped in try-catch; system log
  is not (storage failure should surface immediately)

Default for Command Actions: `both()` via `BaseAction::log()`. Default for error
handling: `systemOnly()` via `HandlesActionErrors`.

### PiiMasker

Design:

- **Key-name-based masking**: matches sensitive keys by substring (e.g., any key
  containing "password" or "token" is fully masked)
- **Full mask** (14 key patterns): passwords, tokens, API keys, credit cards, SSNs
- **Partial mask** (3 key patterns): email (j***@domain), phone (****1234), name (J. Doe)
- **IP masking**: preserves first 2 octets (192.168.***.***)
- **User-Agent truncation**: limits to 50 characters
- **Recursive traversal**: nested arrays are fully traversed

### HandlesActionErrors

Design:

- Trait used by `BaseAction` for Command and Process Actions
- Wraps callback in try-catch that passes through known exceptions (AppException,
  DomainException, ValidationException, AuthorizationException, etc.)
- Catches unexpected `\Throwable`, logs to system-only via SmartLogger, rethrows as
  `RuntimeException`
- Prevents unexpected exceptions from leaking internal state

### CacheKeys

Design:

- Central registry of all cache keys as typed string constants
- Key naming: `{domain}.{purpose}[.{qualifier}]` — e.g., `setup.is_installed`,
  `notification.unread:{userId}`
- Each key documents TTL and invalidation trigger in comments
- No inline string literals anywhere in the codebase

### PasswordRules

Design:

- Single source of truth for password validation policy
- `default()` — returns Laravel `Password` rule object (min 8, mixed case, numbers)
- `defaultAsArray()` — returns equivalent rules as an array (for form request validation)

### Integrity

Design:

- Optional runtime check that verifies `composer.json` author attribution
- In production environment: halts with 403 if author is modified
- In development/testing: degrades to a warning log — does not block

---

## Layer 4: Framework Integration

### CustomDatabaseChannel

Design:

- Custom Laravel notification channel for in-app notifications
- Receives structured data from `toCustomDatabase()` method on notification classes
- Dispatches to `SendsNotifications` contract (bound to `SendNotificationAction`)
- Decouples notification storage from any specific business domain

### Middleware

| Middleware | Responsibility | Applied To |
|---|---|---|
| `SecurityHeaders` | Injects CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy headers | All web routes |
| `LogContext` | Enriches system logs with request_id, method, URL, IP, user_id, role, duration, status | All web routes |

`SecurityHeaders` auto-injects Vite dev server URL into CSP directives when the Vite
hot file is present (development only).

### Console Commands

| Command | Signature | Responsibility |
|---|---|---|
| Health Check | `system:health` | 15-point system verification (PHP, extensions, DB, storage, queue, cache, app key, etc.) |
| Cleanup | `system:cleanup` | Prune expired resets, stale cache, failed jobs, old activity logs, old log files |
| Cache Warm | `system:cache-warm` | Pre-warm settings, brand values, config cache, view cache, event cache |

Commands follow `{domain}:{action}` naming and are self-documenting via `$description`.

---

## Dependency Graph

```
                  ┌─────────────────────────────────┐
                  │      All Business Domains        │
                  │  (Auth, School, Internship, ...)  │
                  └──────────────┬──────────────────┘
                                 │ depends on
                                 ▼
                  ┌─────────────────────────────────┐
                  │            Core Domain           │
                  │  ┌───────┬──────┬────────┬────┐  │
                  │  │Contract│Base │Infra   │Frame│  │
                  │  │  ts    │Classes│structure│work│  │
                  │  └───────┴──────┴────────┴────┘  │
                  └──────────────┬──────────────────┘
                                 │ depends on
                                 ▼
                  ┌─────────────────────────────────┐
                  │   Laravel + Spatie + PHP 8.4     │
                  └─────────────────────────────────┘
```

Core is the root of the entire dependency graph. Nothing depends on it that isn't in the
Laravel framework, Spatie packages, or PHP standard library.

---

## What Core Does NOT Provide

Core deliberately excludes certain things to maintain its zero-business-logic mandate:

| Excluded | Reason | Belongs In |
|---|---|---|
| Business enums (AccountStatus, InternshipStatus) | Enum values encode domain knowledge | Respective business domains |
| Domain events | Events carry domain-specific payloads | Respective business domains |
| Validation rules for business entities | Rules reference domain knowledge | Entities or Form Objects in business domains |
| Feature-specific middleware | Middleware that checks business state | Respective business domains |
| Seeders or factories | Data generation is domain-specific | `database/seeders/`, `database/factories/` |
| Translations | UI text is domain-specific | `lang/{locale}/` files |
| Route definitions | Routes map to domain-specific controllers | `routes/web/{domain}.php` |
| Migrations that reference business data | Schema design encodes domain relationships | `database/migrations/` |
