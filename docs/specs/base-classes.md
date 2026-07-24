# Base Classes — Action Triad, Data Layer, UI Layer, Policies & Contracts

> **Last updated:** 2026-07-24 **Changes:** feat — split from core-foundation.md; architectural
> base classes, entity/DTO/model contracts, Livewire components, policies, contracts

## Description

Architectural base classes and contracts that every module in Internara extends. Defines the
Action Triad (Command/Read/Process), Entity/DTO/Model data layer, Livewire UI base classes,
Policy authorization, enum contracts, and audit data structures. Tech stack and infrastructure
configuration are a separate initiative — see [tech-stack.md](tech-stack.md). Cross-cutting
utility classes are a separate initiative — see [shared-utilities.md](shared-utilities.md).

---

## 1. Problem Statements

### PS-1 — Base Class Consistency

18 modules with 150+ features share a common vocabulary: how to write Actions, Models, Entities,
DTOs, Livewire components, and Policies. Without enforced base classes, each module would reinvent
patterns, creating maintenance nightmares and subtle bugs.

### PS-2 — Architectural Invariant Enforcement

Critical invariants (C1: no Model mutations in Livewire, C5: Entity forbidden imports,
C6: DTO forbidden imports, C8: RejectedException not RuntimeException) must be enforceable
at the class level, not just by convention. Abstract base classes with restricted APIs make
violations compile-time errors rather than runtime surprises.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Enforce Action Triad pattern (Command/Read/Process) via abstract base classes |
| G2  | Provide 5 Livewire base classes covering all UI patterns (table CRUD, modal CRUD, list, form, wizard) |
| G3  | Define Entity/DTO/Model contracts with forbidden import lists |
| G4  | Provide Policy base class with super_admin auto-allow and role/ownership traits |
| G5  | Maintain a dual exception hierarchy (AppException + ModuleException) for precise error handling |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Cache/session infrastructure (see [tech-stack.md](tech-stack.md)) |
| NG2  | SmartLogger, PiiMasker, PasswordRules (see [shared-utilities.md](shared-utilities.md)) |
| NG3  | Middleware ordering and execution (see [middleware-pipeline.md](middleware-pipeline.md)) |
| NG4  | Exception rendering and error handling details (see [logging-and-error-handling.md](logging-and-error-handling.md)) |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Creates a New Module

**Actor:** Developer
**Preconditions:** Project cloned, `composer install` completed, PHP 8.4+ available
**Flow:**
1. Developer creates module directory under `app/{Module}/`
2. Creates Model extending `BaseModel` (UUID PKs automatic)
3. Creates Entity extending `BaseEntity` (final readonly, `fromModel()`)
4. Creates Command Action extending `BaseCommandAction` (transaction + logging automatic)
5. Creates Livewire component extending `BaseRecordManager` (search, filter, sort, pagination automatic)
6. Creates Policy extending `BasePolicy` (role + ownership checks available)
**Postconditions:** Module follows all architectural conventions, base classes enforce invariants

### UC-2 — System Handles Business Rule Violation

**Actor:** Student
**Preconditions:** Student is logged in, attempting invalid operation
**Flow:**
1. Student submits form via Livewire
2. Livewire calls Command Action
3. Action delegates to Entity for business rule check
4. Entity returns `false` (rule violated)
5. Action throws `RejectedException` with message
6. Livewire catches `RejectedException`, flashes error message
7. Student sees user-friendly error
**Postconditions:** No stack trace exposed, SmartLogger records the attempt, user sees `__()` localized message

---

## 4. Functional Requirements

### 4.1 Actions — Action Triad

| ID    | Requirement |
| ----- | ----------- |
| FR-A1 | `BaseAction` — abstract root: transaction wrapper, event dispatch, logging, error handling |
| FR-A2 | `BaseCommandAction` — all mutations: `respond()`, `respondDeleted()`, `respondError()`, `validate()`, `authorize()`, `flash()` |
| FR-A3 | `BaseReadAction` — queries only: `remember()`, `cacheKey()`, `mask()` (PII), `paginate()`, `format()` |
| FR-A4 | `BaseProcessAction` — orchestration: `step()` with success/failure tracking, `trackProgress()`, `notify()`, `logProgress()` |
| FR-A5 | All Actions have exactly one public method: `execute()` |
| FR-A6 | Command/Process Actions wrap DB operations in `$this->transaction()` |
| FR-A7 | Command/Process Actions call `$this->log()` after successful mutation |

### 4.2 Data Layer

| ID    | Requirement |
| ----- | ----------- |
| FR-M1 | `BaseModel` — abstract, extends Eloquent, uses `HasUuids` + `HasCommonScopes` traits |
| FR-M2 | `BaseAuthenticatable` — abstract, bridges Laravel Authenticatable with UUID support |
| FR-M3 | `BaseEntity` — abstract, `final readonly`, implements `JsonSerializable`, requires `fromModel()` |
| FR-M4 | `BaseData` — abstract, `final readonly`, implements `JsonSerializable`, `fromArray()` with camelCase/snake_case fallback |
| FR-M5 | `ActionResponse` — final readonly DTO: `ok()`, `created()`, `updated()`, `deleted()`, `error()`, `withRedirect()` |
| FR-M6 | `HasCommonScopes` — `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()` |
| FR-M7 | `ActivityLog` — extends Spatie `Activity` model: scopes `forUser()`, `whereSubject()`, `ofAction()`, `inLog()`, `recent()`, `lastDays()`, `forModule()`, helper `getGroupedByDay()` |

### 4.3 UI Layer — Livewire Base Classes

| ID    | Requirement |
| ----- | ----------- |
| FR-L1 | `BaseRecordManager` — table CRUD: search, filter, sort, pagination, bulk actions, selection |
| FR-L2 | `BaseRecordEntry` — modal CRUD: create/edit modal with form, `handleError()` for RejectedException |
| FR-L3 | `BaseRecordList` — read-only list: search, pagination (no create/edit) |
| FR-L4 | `BaseFormView` — full-page form: dirty tracking, `handleSave()` |
| FR-L5 | `BaseWizard` — multi-step wizard: `steps()` (abstract, returns key array), `nextStep()` (validates + advances), `prevStep()`, `goToStep()` (with access check), `isStepAccessible()` (all prior steps completed), `progressPercent()`, `currentStepKey()`, `handleStepError()` (catches `RejectedException`), state persistence hooks |
| FR-L6 | `BaseController` — JSON response helpers: `jsonSuccess()`, `jsonCreated()`, `jsonError()`, `jsonPaginated()`, etc. |
| FR-L7 | `BaseFormRequest` — throws `ValidationFailedException` on failed validation |

### 4.4 Contracts — Enum & Interface

| ID    | Requirement |
| ----- | ----------- |
| FR-C1 | `LabelEnum` — interface requiring `label(): string` on all enums |
| FR-C2 | `StatusEnum` — extends `LabelEnum`, adds `isTerminal()`, `canTransitionTo()`, `validTransitions()` |
| FR-C3 | `ColorableEnum` — interface requiring `color(): string` for badge styling |
| FR-C4 | `SendsNotifications` — interface for notification dispatch: `execute(userId, type, title, ...)` |
| FR-C5 | `SettingsStore` — interface for settings retrieval: `get(key, default)` |
| FR-CH1 | `CustomDatabaseChannel` — notification channel: receives `SendsNotifications`, calls `toCustomDatabase()` on notification, validates `type`/`title` keys, delegates to `SendsNotifications::execute()` |

### 4.5 Exception Hierarchy

> **Canonical source:** [logging-and-error-handling.md](logging-and-error-handling.md) §4.5

| ID    | Requirement |
| ----- | ----------- |
| FR-E1 | `AppException` (abstract) — framework-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E2 | `ModuleException` (abstract) — business-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E3 | `RejectedException` extends `ModuleException` — HTTP 400, business rule violations (C8 invariant) |
| FR-E4 | `ValidationFailedException` extends `ActionException` — HTTP 422, form validation failures |
| FR-E5 | `UnauthorizedException` extends `PresentationException` — HTTP 403, authorization failures |
| FR-E6 | `InfrastructureException` extends `AppException` — HTTP 500, not user-facing |
| FR-E7 | `HasExceptionContext` trait — `hint`, `context`, `toCliOutput()`, `isUserFacing()`, `shouldReport()` |

### 4.6 Policies

| ID    | Requirement |
| ----- | ----------- |
| FR-P1 | `BasePolicy` — abstract, auto-allows `super_admin` via `before()` method |
| FR-P2 | `AuthorizesRoles` trait — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` |
| FR-P3 | `AuthorizesOwnership` trait — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` |

### 4.7 Enums & Audit Data

| ID    | Requirement |
| ----- | ----------- |
| FR-D1 | `AuditCheck` — `final readonly` DTO extending `BaseData`: `category` (AuditCategory), `nameKey`, `status` (AuditStatus), `messageKey`, `nameParams`, `messageParams` |
| FR-D2 | `AuditReport` — `final readonly` DTO extending `BaseData`: aggregates `AuditCheck[]`, provides `passed()` (no FAIL checks), `forCategory()` filter |
| FR-D3 | `AuditCategory` — backed enum implementing `LabelEnum`: `REQUIREMENTS`, `PERMISSIONS`, `DATABASE`, `TERMINAL`, `RECOMMENDATIONS`; `isCritical()` method |
| FR-D4 | `AuditStatus` — backed enum implementing `LabelEnum`: `PASS`, `FAIL`, `WARN`; `symbol()` method (✓/✗/⚠) |
| FR-D5 | `CsvRowResult` — backed enum implementing `LabelEnum`: `CREATED`, `SKIPPED`; used by `CsvHandler` import to track per-row outcomes |

### 4.8 Livewire Concerns

| ID     | Requirement |
| ------ | ----------- |
| FR-TR1 | `WithSorting` — Livewire trait: `$sortBy` property (column + direction), `$sortableColumns` whitelist, `applySorting(Builder)` method with column/direction validation |
| FR-TR2 | `WithRecordSelection` — Livewire trait: `$selectedIds` array, `selectAll(ids)`, `clearSelection()`, computed `selected_count` |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-R1 | Transaction wrapper retries up to 3 attempts on deadlock (BaseAction) |
| NFR-M1 | All base classes are abstract — cannot be instantiated directly |
| NFR-M2 | Entities are `final readonly` — no inheritance, no mutation |
| NFR-M3 | DTOs carry only scalars/Enums/Carbon — never Models or Actions (C6 invariant) |
| NFR-M5 | Module discovery at runtime — no manual registration of Livewire/Policies/Views |
| NFR-L1 | All user-facing error messages in base classes must use `__()` translation helper |
| NFR-A1 | Error pages rendered by exception handlers must meet WCAG 2.1 Level AA |

---

## 6. API / Data Contracts

### Action Triad Signatures

```php
abstract class BaseAction {
    protected function transaction(callable $callback, int $attempts = 3): mixed;
    protected function dispatchEvent(BaseEvent $event): void;
    protected function fail(string $message, array $context = []): never;
    protected function log(string $action, ?Model $subject = null, array $payload = []): void;
}

abstract class BaseCommandAction extends BaseAction {
    protected function respond(mixed $data, ?string $message = null, bool $created = false): ActionResponse;
    protected function respondDeleted(?string $message = null): ActionResponse;
    protected function respondError(string $message, array $errors = []): ActionResponse;
    protected function validate(array $data, array $rules): array;
    protected function authorize(string $ability, mixed $arguments = []): void;
    protected function flash(string $message, string $type = 'success'): void;
}

abstract class BaseReadAction {
    protected function remember(string $key, callable $callback, int $ttl = 300): mixed;
    protected function cacheKey(string $purpose, string ...$qualifiers): string;
    protected function mask(array $data, array $fields = []): array;
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator;
}

abstract class BaseProcessAction extends BaseAction {
    protected function step(string $name, callable $callback): mixed;
    protected function trackProgress(float $percent, ?string $message = null): void;
    protected function notify(mixed $notifiables, Notification $notification): void;
}
```

### Data Layer Signatures

```php
abstract class BaseModel extends Eloquent\Model {
    // Traits: HasUuids, HasCommonScopes
    // UUID v7 primary keys, $incrementing = false, $keyType = 'string'
}

abstract class BaseEntity implements JsonSerializable {
    abstract public static fromModel(Model $model): static;
    public static fromArray(array $data): static;
    public function toArray(): array;
    public function with(string $property, mixed $value): static;
}

abstract class BaseData implements JsonSerializable {
    public static fromArray(array $data): static;
    public static from(mixed $source): static;
    public function toArray(): array;
    public function only(string ...$keys): array;
    public function merge(array $overrides): static;
}

final readonly class ActionResponse implements JsonSerializable {
    public bool $success;
    public mixed $data;
    public ?string $message;
    public ?string $redirect;
    public array $errors;
    public static ok(mixed $data = null, ?string $message = null): self;
    public static created(mixed $data = null, ?string $message = null): self;
    public static error(string $message, array $errors = []): self;
    public function withRedirect(string $url): self;
}
```

### Contracts

```php
interface LabelEnum {
    public function label(): string;
}

interface StatusEnum extends LabelEnum {
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}

interface ColorableEnum {
    public function color(): string;
}

interface SendsNotifications {
    public function execute(string $userId, string $type, string $title, ?string $message = null, ?array $data = null, ?string $link = null): mixed;
}

interface SettingsStore {
    public function get(string $key, mixed $default = null): mixed;
}
```

### Enum & Data Classes

```php
enum AuditCategory: string implements LabelEnum {
    case REQUIREMENTS = 'requirements';
    case PERMISSIONS  = 'permissions';
    case DATABASE     = 'database';
    case TERMINAL     = 'terminal';
    case RECOMMENDATIONS = 'recommendations';
    public function isCritical(): bool;
}

enum AuditStatus: string implements LabelEnum {
    case PASS = 'pass';
    case FAIL = 'fail';
    case WARN = 'warn';
    public function symbol(): string; // ✓ / ✗ / ⚠
}

enum CsvRowResult: string implements LabelEnum {
    case CREATED = 'created';
    case SKIPPED = 'skipped';
}

final readonly class AuditCheck extends BaseData {
    public function __construct(
        public AuditCategory $category,
        public string $nameKey,
        public AuditStatus $status,
        public string $messageKey,
        public array $nameParams = [],
        public array $messageParams = [],
    ) {}
}

final readonly class AuditReport extends BaseData {
    public function __construct(public array $checks = []) {}
    public function passed(): bool;
    /** @return AuditCheck[] */
    public function forCategory(AuditCategory $category): array;
}
```

### Livewire Concerns

```php
trait WithSorting {
    public array $sortBy;
    protected array $sortableColumns;
    protected function applySorting(Builder $query): Builder;
}

trait WithRecordSelection {
    public array $selectedIds = [];
    public function selectAll(array $ids): void;
    public function clearSelection(): void;
    #[Computed] public function selected_count(): int;
}
```

### Notification Channel

```php
class CustomDatabaseChannel {
    public function __construct(protected readonly SendsNotifications $sendNotification);
    public function send(mixed $notifiable, Notification $notification): void;
}
```

### Exception Hierarchy

```
RuntimeException
├── AppException (abstract)
│   ├── ActionException (abstract, 400)
│   │   └── ValidationFailedException (422)
│   ├── InfrastructureException (abstract, 500)
│   └── PresentationException (abstract, 400)
│       └── UnauthorizedException (403)
└── ModuleException (abstract)
    └── RejectedException (400)
```

---

## 7. Design Decisions

### DD-1 — Dual Exception Hierarchy

**Decision:** Two separate exception trees: `AppException` (framework) and `ModuleException` (business).
**Rationale:** Allows precise catch-block targeting. Framework errors (infrastructure, presentation)
are caught differently from business rule violations. `RejectedException` (the most common business
exception) extends `ModuleException` and always returns HTTP 400.
**Trade-off:** Slightly more complex exception hierarchy, but prevents the "catch everything as
RuntimeException" anti-pattern.
**Full specification:** [logging-and-error-handling.md](logging-and-error-handling.md) §4.5, §7.1

### DD-2 — Module Discovery at Runtime

**Decision:** Livewire components, policies, and Blade namespaces are discovered dynamically via
`ModuleDiscoverService`, not manually registered.
**Rationale:** With 22 modules, manual registration in service providers would be error-prone and
a maintenance burden. Runtime scanning adds negligible startup cost (~50ms) and automatically
picks up new modules.
**Trade-off:** Slightly slower boot time. Mitigated by caching discovery results in Redis/file cache.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Strict types | 100% of PHP files (except migrations/config) | `python3 scripts/scan_conventions.py` |
| No debug calls | Zero in committed code | `python3 scripts/scan_conventions.py` |
| Base class usage | 100% of Actions extend BaseAction variants | `python3 scripts/scan_class_contracts.py` |
| Entity contracts | 100% of Entities are `final readonly` with `fromModel()` | `python3 scripts/scan_class_contracts.py` |
| DTO contracts | 100% of DTOs extend `BaseData` | `python3 scripts/scan_class_contracts.py` |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [tech-stack.md](tech-stack.md) | PHP 8.4, Laravel 13, Eloquent Model base, queue/mail configuration |

### Build Guide
This spec defines the architectural vocabulary: Action Triad for business logic, Entity/DTO for
data, Livewire base classes for UI, Policies for authorization, and the exception hierarchy for
error handling. Every module extends these classes. The next step is to build the cross-cutting
utility classes and then the event/RBAC infrastructure that these base classes depend on.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [shared-utilities.md](shared-utilities.md) | Uses `BaseData`, `BaseEntity` contracts for utility DTOs |
| 2 | [event-system.md](event-system.md) | Uses `BaseEvent` and `dispatchEvent()` from `BaseAction` |
| 3 | [rbac-and-authorization.md](rbac-and-authorization.md) | Uses `BasePolicy`, `AuthorizesRoles`, `AuthorizesOwnership` |
| 4 | [middleware-pipeline.md](middleware-pipeline.md) | Defines middleware that `bootstrap/app.php` registers |
| 5 | [installation.md](installation.md) | Uses `BaseWizard`, `BaseCommandAction`, exception hierarchy |

---

## Quick References

- `docs/architecture.md` — 4-layer architecture, Action Triad, dependency rules
- `docs/conventions.md` — Invariants C1-C8, D1-D6, naming, security, testing
- `docs/modules/core.md` — Core module overview
- `docs/modules/core-reference.md` — Core module technical reference
- `docs/architecture/action-pattern.md` — Action Triad contracts and patterns
- `docs/architecture/entity-pattern.md` — Entity contracts and bridge pattern
- `docs/architecture/model-pattern.md` — Model conventions
- `docs/architecture/data-pattern.md` — DTO and ActionResponse contracts
- `docs/architecture/exception-pattern.md` — Dual exception hierarchy
- `app/Core/Actions/` — BaseAction, BaseCommandAction, BaseReadAction, BaseProcessAction
- `app/Core/Models/` — BaseModel, BaseAuthenticatable, ActivityLog
- `app/Core/Entities/` — BaseEntity and all module entities
- `app/Core/Data/` — BaseData, ActionResponse, AuditCheck, AuditReport
- `app/Core/Enums/` — AuditCategory, AuditStatus, CsvRowResult
- `app/Core/Livewire/` — BaseRecordManager, BaseRecordEntry, BaseRecordList, BaseFormView, BaseWizard
- `app/Core/Livewire/Concerns/` — WithSorting, WithRecordSelection
- `app/Core/Policies/` — BasePolicy, AuthorizesRoles, AuthorizesOwnership
- `app/Core/Contracts/` — LabelEnum, StatusEnum, ColorableEnum, SendsNotifications, SettingsStore
- `app/Core/Channels/CustomDatabaseChannel.php` — Queued database notification channel
- `app/Core/Exceptions/` — AppException, ModuleException, RejectedException, and hierarchy
- `bootstrap/app.php` — Middleware registration
- **Related specs:** [tech-stack.md](tech-stack.md) — PHP/Laravel versions, cache, session, queue, mail
- **Related specs:** [shared-utilities.md](shared-utilities.md) — Cross-cutting utility classes
- **Related specs:** [logging-and-error-handling.md](logging-and-error-handling.md) — Exception hierarchy, SmartLogger, error handling
- **Related specs:** [middleware-pipeline.md](middleware-pipeline.md) — Middleware execution order and registration
- **Related specs:** [rbac-and-authorization.md](rbac-and-authorization.md) — Policies, roles, authorization
- **Related specs:** [event-system.md](event-system.md) — Event dispatch and listener infrastructure
