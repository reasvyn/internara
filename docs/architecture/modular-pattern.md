# Modular Pattern Reference

> **Last updated:** 2026-06-10
>
> Comprehensive catalog of design patterns, conventions, and architectural rules used across the
> Internara codebase. This document distils patterns from `docs/architecture.md`,
> `docs/conventions.md`, all ADRs, and the implementation code itself into a single reference.
>
> Every pattern here exists because it solves a concrete problem. If a pattern does not add value
> for your use case, use the simpler alternative. These are guidelines, not mandates.

---

## Table of Contents

1. [Architectural Patterns](#1-architectural-patterns)
2. [Base Class Patterns (Layer 4)](#2-base-class-patterns-layer-4)
3. [Contract Patterns (Layer 3)](#3-contract-patterns-layer-3)
4. [Action Patterns](#4-action-patterns)
5. [Entity-Model Separation Patterns](#5-entity-model-separation-patterns)
6. [Enum Patterns](#6-enum-patterns)
7. [Policy & Authorization Patterns](#7-policy--authorization-patterns)
8. [Livewire Component Patterns](#8-livewire-component-patterns)
9. [Model Patterns](#9-model-patterns)
10. [Logging & Error Handling Patterns](#10-logging--error-handling-patterns)
11. [Testing Patterns](#11-testing-patterns)
12. [Cache Patterns](#12-cache-patterns)
13. [Route & Controller Patterns](#13-route--controller-patterns)
14. [Notification Patterns](#14-notification-patterns)
15. [Migration & Database Patterns](#15-migration--database-patterns)
16. [Naming Conventions](#16-naming-conventions)
17. [PHP Language Conventions](#17-php-language-conventions)
18. [Quality Enforcement](#18-quality-enforcement)
19. [Cross-Cutting Patterns](#19-cross-cutting-patterns)
20. [Dual Mentor Fallback Protocol](#20-dual-mentor-fallback-protocol)
21. [Workflow Patterns](#21-workflow-patterns)

---

## 1. Architectural Patterns

### 1.1 Module-Colocated Vertical Slicing (Action-Based MVC)

**Intent:** Organize code by business domain, not by technical layer.

**Rationale:** Flat layering scatters a single feature across eight directories. Module colocation
ensures everything related to Enrollment lives under `app/Enrollment/`. High cohesion, low coupling.

**Structure:**
```
app/{Module}/
├── {SubModule}/          → Submodule-rooted components
│   ├── Actions/          → Business operations
│   ├── Models/           → Eloquent models
│   ├── Policies/         → Authorization gates
│   ├── Livewire/         → UI components
│   ├── Entities/         → Business rules
│   ├── Enums/            → Domain enums
│   ├── Events/           → Module events
│   ├── Listeners/        → Event subscribers
│   └── Notifications/    → Multi-channel alerts
├── Types/                → Shared value objects, enums, rules
├── Actions/              → Cross-submodule orchestration
├── Http/                 → Cross-submodule controllers & middleware
├── Console/              → Cross-submodule artisan commands
├── Livewire/             → Cross-submodule UI (dashboards)
├── Support/              → Module utilities
└── Services/             → Infrastructure services
```

**Total: 19 modules** (Core, Auth, User, SysAdmin, Setup, Settings, Academics, Program, Enrollment,
Assessment, Evaluation, Assignment, Journals, Guidance, Incident, Partners, Certification, Reports,
Document).

See: `docs/architecture.md` §Layered Architecture, `docs/conventions.md` §2.

### 1.2 12-Layer Architecture

**Intent:** Strict downward-only dependency graph. Each layer depends only on layers below it.

```
Layer 12 — Business Modules     (vertical slices through layers 1-11)
Layer 11 — UI / Presentation    (Livewire 4, Blade, maryUI, Alpine.js)
Layer 10 — HTTP Layer           (Controllers, Middleware, 18 route files)
Layer  9 — Communication        (Events, Listeners, Notifications, Console Commands)
Layer  8 — Authorization        (Policies, RBAC — 5 roles, 2 functional)
Layer  7 — Business Operations  (Command/Read/Process Actions)
Layer  6 — Domain Rules         (Entities: final readonly, DTOs, Enums)
Layer  5 — Module Models        (Eloquent, UUID PKs, HasFactory)
Layer  4 — Core Base Classes    (BaseModel, BaseAction, BaseEntity, BasePolicy, etc.)
Layer  3 — Core Contracts       (LabelEnum, StatusEnum, ColorableEnum, etc.)
Layer  2 — Persistence          (Database, Config, Cache, Queue, Files)
Layer  1 — Infrastructure       (PHP 8.4, Laravel 13, Spatie packages)
```

**Rules:**
- Core (layers 3-4) depends on nothing except Laravel and Spatie.
- No business module may be imported by Core.
- Sibling modules may import each other directly; prefer events when side effects are involved.

See: `docs/architecture.md` §Layered Architecture.

### 1.3 Action Triad (Command / Read / Process)

**Intent:** Separate mutations, queries, and orchestration into three distinct contracts,
eliminating the ambiguity of multi-method Service classes.

| Type | Base Class | Transaction | Logging | Use Case |
|------|-----------|-------------|---------|----------|
| **Command** | `BaseAction` | Required | Required | Create, update, delete, state transitions |
| **Read** | None | Prohibited | Prohibited | Complex queries, aggregation, dashboard stats |
| **Process** | `BaseAction` | Required | Required | Multi-step orchestration of Command Actions |

**Command Action pattern:**
```php
class CreateInternshipAction extends BaseAction
{
    public function __construct(
        protected readonly NotifyStakeholdersAction $notify,
    ) {}

    public function execute(CreateInternshipData $data): Internship
    {
        return $this->transaction(function () use ($data) {
            $internship = Internship::create($data->toArray());
            $this->log('internship_created', $internship);
            $this->dispatchEvent(new InternshipCreated($internship));
            return $internship;
        });
    }
}
```

**Read Action pattern:**
```php
class InternshipDashboardReader
{
    public function __construct(protected Internship $model) {}

    public function activeCount(): int { /* query only */ }
    public function recentRegistrations(int $days = 7): Collection { /* query only */ }
}
```

**Process Action pattern:**
```php
class RegisterStudentProcess extends BaseAction
{
    public function __construct(
        protected CreateRegistrationAction $createRegistration,
        protected AssignPlacementAction $assignPlacement,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->log('student_registered', $registration);
            $this->dispatchEvent(new StudentRegistered($registration));
            return $registration;
        });
    }
}
```

See: `docs/architecture.md` §Action Triad, `docs/conventions.md` §6.

### 1.4 Cross-Module Communication (Four Patterns)

| Pattern | When to Use | Example |
|---------|-------------|---------|
| **Direct import** | Straightforward access, no side effects | `use App\Academics\Models\AcademicYear;` |
| **Action delegation** | Cross-module business operation | `$this->createUser->execute($data);` |
| **Module event** | Fire-and-forget side effects | `event(new InternshipCreated($internship));` |
| **Core contract** | Abstraction used broadly | `LabelEnum`, `SendsNotifications` |

Use events when you want to add new reactions without modifying the caller. Use direct imports for
everything else.

See: `docs/architecture.md` §Cross-Module Communication, `docs/adr/adr-cross-module-communication.md`.

### 1.5 Gradual Migration Path

**Intent:** Ship features first, migrate to typed DTOs and events later.

Three phases per pattern:
```
Phase 1 — execute(array $data)            → rapid development
Phase 2 — execute(Data|array $data)       → accepts both (union type)
Phase 3 — execute(Data $data)             → DTO only (final)
```

See: `docs/adr/adr-gradual-migration.md`.

---

## 2. Base Class Patterns (Layer 4)

Every architectural layer has exactly one base class from Core. These are the foundation every
module builds on.

| Need | Base Class | Location |
|------|-----------|----------|
| Database table | `BaseModel` or `BaseAuthenticatable` | `app/Core/Models/` |
| Business mutation | `BaseAction` | `app/Core/Actions/BaseAction.php` |
| Pure business rules | `BaseEntity` (final readonly) | `app/Core/Entities/BaseEntity.php` |
| Authorization gate | `BasePolicy` | `app/Core/Policies/BasePolicy.php` |
| CRUD table UI | `BaseRecordManager` | `app/Core/Livewire/BaseRecordManager.php` |
| HTTP controller | `BaseController` | `app/Core/Http/Controllers/BaseController.php` |
| Form request | `BaseFormRequest` | `app/Core/Http/Requests/BaseFormRequest.php` |
| Value object / DTO | `BaseData` (final readonly) | `app/Core/Data/BaseData.php` |
| Event | `BaseEvent` | `app/Core/Events/BaseEvent.php` |
| Enum | Implements `LabelEnum` | `app/Core/Contracts/LabelEnum.php` |
| State machine enum | Implements `StatusEnum` + `LabelEnum` | `app/Core/Contracts/StatusEnum.php` |
| Exception | Extends `AppException` or `ModuleException` | `app/Core/Exceptions/` |

See: `docs/conventions.md` §1, `docs/adr/adr-base-class-mandate.md`.

---

## 3. Contract Patterns (Layer 3)

### 3.1 LabelEnum
Every enum across the codebase implements `LabelEnum`, providing a human-readable label for UI
display. No plain PHP enums exist without this interface.

### 3.2 StatusEnum (State Machine)
State machine enums implement `StatusEnum` which extends `LabelEnum`. Three critical methods:
- `isTerminal(): bool` — whether this state is final
- `canTransitionTo(StatusEnum $target): bool` — transition validation (also checks wrong enum type)
- `validTransitions(): array` — list of valid target states

All state machines must define every valid and invalid transition explicitly. Methods like
`isActive()`, `isFinalized()`, `requiresAction()` are added case-by-case on the enum directly.

### 3.3 ColorableEnum
Enums that support UI color/badge variants implement `ColorableEnum` with a `color(): string`
method returning DaisyUI-compatible color names (`primary`, `success`, `warning`, `error`).

### 3.4 SendsNotifications & SettingsStore
Infrastructure contracts that bind notification dispatch and setting storage to their
implementations via the service container.

See: `app/Core/Contracts/`, `docs/conventions.md` §8.

---

## 4. Action Patterns

### 4.1 Action Injection via Method Parameter
Livewire components inject Actions as method parameters, not resolved manually from the container.
Laravel's dependency injection resolves the Action and all its dependencies.

```php
public function save(CreateReportAction $action): void {
    $action->execute($this->form->toArray());
}
```

### 4.2 ActionResponse — Standardized Return Envelope
Every Action returns an `ActionResponse` for consistency, or the model/DTO directly.
`ActionResponse` provides factory methods: `ok()`, `created()`, `updated()`, `deleted()`,
`error()`, `withRedirect()`. Its `jsonSerialize()` automatically converts Models to arrays.

### 4.3 Transaction Safety
- `BaseAction::transaction()` auto-detects nested transactions — if already inside one, it
  executes the callback directly without wrapping.
- Events dispatched during a transaction are queued and dispatched after the transaction commits.
- Retry on deadlock: 3 attempts by default.

### 4.4 Single execute() Contract
Every Action has exactly one public `execute()` method. There is no second public method. Complex
workflows use Process Actions that compose multiple Command Actions.

---

## 5. Entity-Model Separation Patterns

### 5.1 Bridge Pattern (fromModel + as{Entity})
Models own persistence. Entities own business rules. The bridge connects them cleanly:

```php
// Entity — pure business rules
final readonly class InternshipPeriod extends BaseEntity
{
    public static function fromModel(Model $model): static { ... }
    public function isAcceptingRegistrations(?Carbon $now = null): bool { ... }
}

// Model — persistence + bridge
class Internship extends BaseModel
{
    public function asInternshipPeriod(): InternshipPeriod
    {
        return InternshipPeriod::fromModel($this);
    }
}
```

**33 bridge accessors** across all modules: `asApprentice()`, `asInternshipPeriod()`,
`asRegistrationState()`, `asPlacementState()`, `asLogbookState()`, etc.

### 5.2 Entity Purity (Pragmatic)
- `final readonly` class — immutable by construction
- Zero I/O, zero HTTP, zero persistence
- Framework dependencies (Carbon, Eloquent `Model` in `fromModel()`) ARE allowed pragmatically
- Business rule methods only — no getters/setters that just return properties

### 5.3 Shared Validation Rules
Entities may expose static `rules()` methods returning validation rule arrays, shared across
Form Objects and Form Requests to eliminate duplication.

See: `docs/conventions.md` §7, `docs/adr/adr-entity-model-separation.md`,
`.agents/skills/entity-refactoring/SKILL.md`.

---

## 6. Enum Patterns

### 6.1 String-Backed, UPPER_SNAKE Cases
```php
enum InternshipStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    // ...
}
```

### 6.2 Business Logic on Enum
Methods like `isActive()`, `isFinalized()`, `requiresAction()`, `allowsLogin()` live directly on
the enum, not in a separate class.

### 6.3 Model Defaults Use ->value
```php
protected $attributes = [
    'status' => InternshipStatus::DRAFT->value,  // ✅ never hardcode 'draft'
];
```

See: `docs/conventions.md` §8.

---

## 7. Policy & Authorization Patterns

### 7.1 Flat RBAC with Functional Roles
Five roles, no inheritance: `super_admin`, `admin`, `teacher`, `student`, `supervisor`.
Two functional roles derived at runtime: `mentor` (teacher + supervisor), `mentee` (student).

### 7.2 Three-Layer Authorization
1. **Route level:** `CheckRoleMiddleware` with `role:super_admin|admin` syntax
2. **Livewire level:** `$this->authorize('create', Model::class)`
3. **Policy level:** `BasePolicy` methods — `isAdmin()`, `isOwner()`, `allowIfAdmin()`

### 7.3 Super Admin Gate::before Bypass
Super admin bypasses all permission checks via a single `Gate::before()` callback. No need to
enumerate "all permissions" for this role — it just always returns `allow()`.

### 7.4 AuthorizesRoles Trait
Methods: `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `isAdminOrTeacher()`,
`hasAnyOfRoles()`.

### 7.5 AuthorizesOwnership Trait
Methods: `isOwner(user, model)`, `isRelatedThrough(user, model, relation)`,
`isOwnerOrAdmin(user, model)` — all return `Response::allow()` or `Response::deny()`.

See: `docs/conventions.md` §9, `docs/foundation/rbac.md`, `docs/adr/adr-flat-rbac-with-functional-roles.md`.

---

## 8. Livewire Component Patterns

### 8.1 Thin Component Rule
Livewire components handle ONLY:
- UI state (public properties)
- Form validation (UX-level; Action validates again)
- Delegation to Actions

MUST NOT contain:
- `Model::create()`, `Model::update()`, `Model::delete()`
- `DB::` queries
- `Mail::`, `Notification::send()` (unless trivial)
- Business rule conditionals

### 8.2 Action Injection
```php
public function save(CreateReportAction $action): void
{
    $this->validate();
    $action->execute($this->form->toArray());
    flash()->success(__('report.created'));
}
```

### 8.3 Confirmation Dialog Pattern
```php
public ?string $actionTarget = null;
public bool $confirmingAction = false;

public function askAction(string $id): void { $this->actionTarget = $id; $this->confirmingAction = true; }
public function confirmAction(DeleteAction $action): void { $action->execute($this->actionTarget); ... }
```

### 8.4 Form Object Pattern
Complex forms extracted into `Livewire\Form` subclasses:
```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $start_date = '';
    // rules(), toArray(), validationAttributes()
}
```

### 8.5 Component Alias Convention
| Scope | Pattern | Example |
|-------|---------|---------|
| Submodule | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Cross-submodule | `{kebab-module}.{kebab-name}` | `user.profile-editor` |
| Shared | `{kebab-component-name}` | `livewire.lang-switcher` |

See: `docs/conventions.md` §10, `.agents/skills/livewire-development/SKILL.md`,
`.agents/skills/livewire-refactoring/SKILL.md`.

---

## 9. Model Patterns

### 9.1 UUID Primary Key via HasUuids
Every model extends `BaseModel` (UUID PK via `HasUuids` trait). Exception: `User` model extends
`Authenticatable` directly but applies `HasUuids` manually.

**Migration pattern:**
```php
$table->uuid('id')->primary();
$table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
$table->index(['user_id', 'date']);
```

### 9.2 #[Fillable] Attribute
Use PHP 8.4 attribute syntax for mass assignment protection:
```php
#[Fillable(['name', 'email', 'password'])]
class User extends Authenticatable { ... }
```

### 9.3 Entity Bridge Accessors
Named accessors expose entities — never a generic `entity()`:
```php
public function asInternshipPeriod(): InternshipPeriod { ... }
```

### 9.4 Relationship Naming
- `BelongsTo` / `HasOne` — singular: `user()`, `academicYear()`
- `HasMany` / `BelongsToMany` — plural: `users()`, `registrations()`
- Always define the inverse relationship.

### 9.5 Common Scopes (on BaseModel)
`scopeActive()`, `scopeInactive()`, `scopeRecent(50)`, `scopeCreatedAfter(date)`,
`scopeCreatedBefore(date)`, `scopeOrdered(column, direction)`.

See: `docs/conventions.md` §5.

---

## 10. Logging & Error Handling Patterns

### 10.1 SmartLogger — Fluent Dual-Channel Logging
Every significant event is logged to both system log (debug) and activity log (audit) with PII
masking, bilingual descriptions, and graceful degradation on failure.

**Fluent API:**
```php
SmartLogger::info('Profile updated')
    ->for($user)          → causer
    ->about($profile)     → subject
    ->withPayload([...])  → extra data (PII-masked automatically)
    ->module('User')      → activity log name
    ->event('updated')    → event name for translation
    ->channel('slack')    → optional routing
    ->systemOnly()        → system log only
    ->activityOnly()      → activity log only
    ->both()              → both channels (default)
    ->save();
```

**BaseAction shorthand:** `$this->log('user_created', $user, ['source' => 'api'])` converts to
`SmartLogger::info()` -> `both()` -> `save()`.

**PII masking:** Automatic for 39+ key patterns (`password` → `***`, `email` → `jo***@example.com`,
`name` → `J. Doe`, IP → `192.168.***.***`, UA → truncated to 50 chars).

### 10.2 Dual Exception Hierarchy
```
RuntimeException
├── AppException (abstract)
│   ├── ActionException > ValidationFailedException, ConflictException
│   ├── InfrastructureException > RateLimitException
│   └── PresentationException > NotFoundException, UnauthorizedException
└── ModuleException (abstract) > RejectedException
```

Two sibling trees so catch blocks target module failures independently from infrastructure
failures:
- `catch (ModuleException $e)` → user-facing error messages
- `catch (InfrastructureException $e)` → operations/technical errors

### 10.3 HandlesActionErrors Trait
Applied automatically via `BaseAction`. Catches unexpected `Throwable`, logs via SmartLogger
(system-only with PII masking), rethrows as `RuntimeException`.

Known exception types pass through unaltered: `RuntimeException`, `AppException`, `ModuleException`,
`ValidationException`, `AuthorizationException`, `ModelNotFoundException`, `NotFoundHttpException`.

### 10.4 HasExceptionContext Trait
All exceptions provide: `withHint(string)`, `withContext(array)`, `toCliOutput()` (CLI-friendly
message with hint and PII-masked context), `getSanitizedContext()` (PII-masked).

See: `docs/conventions.md` §20, `app/Core/Support/SmartLogger.php`, `app/Core/Support/PiiMasker.php`,
`app/Core/Exceptions/`.

---

## 11. Testing Patterns

### 11.1 Module-First Structure
Tests mirror source structure: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`.

### 11.2 Scope Isolation
Every Action, command, and Livewire component has its own dedicated test file. Never combine
multiple distinct scopes into a single test file.

### 11.3 Layer Testing Strategy
| Layer | Test Type | DB | Approach |
|-------|-----------|-----|----------|
| Enum | Unit | No | Assert `label()`, transitions, terminals, colors |
| Entity | Unit | No | `new Entity(...)`, assert business rule methods |
| DTO | Unit | No | Constructor -> `toArray()`, `fromArray()`, `only()`, `except()` |
| Policy | Unit | No | Mock user/model -> assert boolean gates |
| Command Action | Feature | Yes | Factory -> `execute()` -> assert database state |
| Process Action | Feature | Yes | Full workflow + partial failure scenarios |
| Livewire | Feature | Yes | `Livewire::test()` -> interact -> assert state/redirect |

### 11.4 Performance Preferences
- `LazilyRefreshDatabase` over `RefreshDatabase`
- `assertModelExists()` over `assertDatabaseHas()`
- Factory states/sequences over manual model creation
- Fakes AFTER factory setup (UUID generation events must not be silenced)

### 11.5 TDD Workflow Order
`Enum -> Entity -> Command Action -> Read Action -> Process Action -> Livewire -> Policy -> Console Command`

See: `docs/conventions.md` §22, `docs/infrastructure/testing.md`,
`.agents/skills/pest-testing/SKILL.md`.

---

## 12. Cache Patterns

### 12.1 Centralized Key Registry
Every cache key in `config/cache-keys.php`. Access via `config('cache-keys.{key_name}')`.
Naming: `{module}.{purpose}[.{qualifier}]` — `setup.is_installed`, `theme.css_variables`.

### 12.2 Event-Driven Invalidation
Command Action dispatches event -> Listener -> `Cache::forget(key)`. Three migration phases:
inline -> event+listener -> config registry + listener.

### 12.3 TTL Categorization
| TTL | Duration | Examples |
|-----|----------|---------|
| Short | <5 min | Dashboard stats, notification counts |
| Medium | 5min-1h | Aggregated reports |
| Long | 1h-24h | Slowly changing reference data |
| Forever | Until invalidation | Settings, branding, permissions |

See: `docs/architecture.md` §Caching Strategy, `docs/infrastructure/cache.md`,
`config/cache-keys.php`.

---

## 13. Route & Controller Patterns

### 13.1 Module-Split Route Files
Routes split by module: `routes/web/{module}.php`. Master `routes/web.php` requires them in
dependency order. 18 route files for 19 modules.

### 13.2 Route Naming
`{prefix}.{resource}.{action}` — `admin.users.index`, `student.logbook.show`.

### 13.3 Middleware Groups
| Group | Middleware | Purpose |
|-------|-----------|---------|
| `auth` | Laravel auth | Requires authenticated session |
| `guest` | Laravel guest | Blocks authenticated users |
| `role:{roles}` | `CheckRoleMiddleware` | Route-level role gating |
| `auth.throttle` | `AuthThrottleMiddleware` | Rate limiting on auth endpoints |

### 13.4 Controller Convention
Controller suffix required (`DashboardController`). Delegate to Actions — no business logic.
Prefer Livewire components over controllers for interactive pages.

See: `docs/conventions.md` §14, `docs/infrastructure/routes.md`.

---

## 14. Notification Patterns

### 14.1 Multi-Channel with CustomDatabaseChannel
```php
class InternshipCreatedNotification extends Notification implements ShouldQueue
{
    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }
}
```

### 14.2 Naming & Translation
- Naming: `{Entity}{NotificationType}Notification` — `InternshipCreatedNotification`
- All user-facing strings via `__()` helper
- `CustomDatabaseChannel` stores in-app notifications with `type`, `title`, `message`, `link`

See: `docs/conventions.md` §13.

---

## 15. Migration & Database Patterns

### 15.1 Foreign Key Convention
```php
$table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
```
Explicit `onDelete()` / `onUpdate()` behavior required:
- `cascadeOnDelete()` — child cannot exist without parent
- `onDelete('set null')` — optional relationship
- `onDelete('restrict')` — prevent deletion

### 15.2 Composite Indexes
```php
$table->index(['user_id', 'date']);
```
Indices created explicitly, not relying on FK auto-indexing.

### 15.3 Seeder Idempotency
Seeders use `firstOrCreate()` for reference data, `create()` for test data. Seeding order
respects module dependencies: school -> user -> permissions -> internships.

### 15.4 Single Table Per Migration
Each migration file handles one table or one logical change.

See: `docs/conventions.md` §17.

---

## 16. Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Read Action | `{Context}Reader`, `{Entity}Query` | `InternshipDashboardReader` |
| Process Action | `{Verb}{Entity}Process` | `RegisterStudentProcess` |
| Entity | `{Name}` | `Apprentice`, `InternshipPeriod` |
| DTO | `{Verb}{Entity}Data` | `LoginData`, `SetupTokenData` |
| Event | `{Entity}{PastTenseAction}` | `InternshipCreated` |
| Listener | `{Verb}{Entity}` | `NotifyAdminsInternshipCreated` |
| Notification | `{Entity}{Type}Notification` | `InternshipCreatedNotification` |
| Livewire | `{Name}Manager`/`{Name}Editor` | `UserManager`, `ProfileEditor` |
| Policy | `{Model}Policy` | `UserPolicy` |
| Controller | `{Name}Controller` | `DashboardController` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index` |
| Cache key | `{module}.{purpose}` | `setup.is_installed` |
| Column / table | `snake_case` | `user_id`, `academic_years` |
| Boolean method | `is`/`has`/`can`/`should` | `isActive()`, `canTransitionTo()` |
| Submodule dir | Singular | `User`, `Profile`, `Internship` |
| Test file | `{Name}Test.php` | `CreateUserActionTest.php` |
| Factory | `{Name}Factory` | `UserFactory` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | |

See: `docs/conventions.md` §4.

---

## 17. PHP Language Conventions

| Rule | Detail |
|------|--------|
| strict_types | `declare(strict_types=1)` in every file except migrations and config |
| Property promotion | `public function __construct(protected readonly X $x) {}` |
| Return types | Explicit on every method: `function isActive(): bool` |
| Parameter types | Type hints on all parameters: `function find(string $id): ?Model` |
| Comparison | `===` over `==` unless loose comparison is intentional |
| match() | `match()` over long `switch()` blocks when returning a value |
| String functions | `str_contains()`, `str_starts_with()`, `str_ends_with()` over `strpos()` |
| Null safety | `?->` and `??` over explicit null checks |
| Trailing commas | On multiline arrays, function calls, constructor params |
| Translations | `__()` for all user-facing strings |
| No debug calls | No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code |

See: `docs/conventions.md` §3.

---

## 18. Quality Enforcement

| Tool | Enforcement | Command |
|------|-------------|---------|
| Laravel Pint | PHP code style (PSR-12 + Laravel) | `vendor/bin/pint --format agent` |
| PHPStan | Type safety, dead code, boundary violations | `vendor/bin/phpstan analyse --no-progress` |
| Prettier | Markdown, JSON, YAML, Blade formatting | `npm run format` |
| Code Review | Architecture conventions, pattern compliance | Manual review of every PR |

**Pre-commit checklist:**
- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `var_dump()`, `ray()` left in code
- [ ] All user-facing strings use `__()` helper
- [ ] Action follows correct triad pattern (Command/Read/Process)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --format agent`

See: `docs/conventions.md` §23.

---

## 19. Cross-Cutting Patterns

### 19.1 Static Utility Classes
Pure utility classes with no Eloquent and no framework dependencies live in `Support/`:
- `Color` — hex manipulation, luminance, contrast, shade computation
- `PiiMasker` — PII redaction for 39+ key patterns
- `CsvHandler` — CSV import/export with header validation
- `PasswordRules` — password strength presets
- `Environment` — environment detection (production, staging, testing, maintenance)
- `AppInfo` — static metadata from composer.json
- `AppIntegrity` — application integrity checks
- `LangChecker` — warns on missing translation keys (development only)

### 19.2 Livewire Concerns (Reusable Traits)
- `WithRecordSelection` — checkbox row selection for bulk actions
- `WithSorting` — column sorting state management

### 19.3 Custom Notification Channel
`CustomDatabaseChannel` provides in-app database notifications via the `notifications` table,
compatible with the standard `Illuminate\Notifications\Notification` workflow.

### 19.4 Security Middleware
- `SecurityHeaders` — CSP, X-Frame-Options, Permissions-Policy, Referrer-Policy
- `LogContext` — request tracing: request_id, method, URL, IP, user_id

---

## 20. Dual Mentor Fallback Protocol

### 20.1 Fallback Verification Pattern
Actions that perform verification must accept nullable parameters for industry supervisor fields.
If the supervisor is unavailable:
- **Bypass Window:** Teacher can bypass after 48h inactivity
- **Proxy Entry:** Teacher enters scores on behalf of supervisor
- **Weight Redistribution:** Supervisor's weight (40%) redistributed when absent
- **Compliance Stamping:** Documents compiled with fallback tagged `fallback_weights` or `proxy_scores`

### 20.2 Implementation Rules
- Command Action must transition to `FINALIZED`/`VERIFIED`
- Record `verified_by_fallback = true` or set fallback verifier fields
- Append audit trail via SmartLogger detailing the teacher who authorized the override
- Clear supervisor's pending verification queue

See: `docs/conventions.md` §21.

---

## 21. Workflow Patterns

### 21.1 Feature Building Workflow
`Module docs -> Migration/Model -> Entity -> Enum -> Action -> Policy -> Livewire -> Blade -> Routes -> Translations -> Tests -> Quality`

Data flow: `Component -> Action -> Model/Entity -> Database`

### 21.2 Refactoring Workflows
**Action Extraction:** Identify inline `Model::create/update/delete` -> Create Action -> Move
validation -> Wrap in transaction -> Add log -> Dispatch event -> Inject into component -> Catch
`RejectedException`.

**Entity Extraction:** Identify business rule conditionals -> Create Entity (final readonly,
BaseEntity) -> Extract state into typed constructor -> Implement `fromModel()` -> Move business
rule methods -> Add named accessor on Model -> Update callers -> Write unit tests (no DB).

**Livewire Refactoring:** Four extraction categories:
1. Business logic -> Action
2. Business rules -> Entity
3. Repeated UI patterns -> Shared component/trait
4. Static utilities -> Support class

### 21.3 Data Flow
**Mutation:** `Input -> Livewire/Controller -> Command Action -> Model/Entity -> Database`
(always includes: Policy check -> Transaction wrap -> Log -> Event dispatch)

**Simple query:** `Livewire -> Model::query() -> Database` (with Policy check)

**Complex query:** `Livewire -> Read Action -> Model::query() -> Database`

---

## Pattern Index

| Category | Count | Key Reference |
|----------|-------|--------------|
| Architectural | 9 | `docs/architecture.md`, `docs/adr/` |
| Base Classes | 10 | `app/Core/`, `docs/conventions.md` §1 |
| Contracts | 5 | `app/Core/Contracts/` |
| Action | 4 | `docs/architecture.md` §Action Triad |
| Entity-Model | 3 | `docs/conventions.md` §5-7 |
| Enum | 3 | `docs/conventions.md` §8 |
| Policy/Authorization | 5 | `docs/conventions.md` §9 |
| Livewire | 5 | `docs/conventions.md` §10 |
| Model | 5 | `docs/conventions.md` §5 |
| Logging/Error | 4 | `docs/conventions.md` §20 |
| Testing | 5 | `docs/conventions.md` §22 |
| Cache | 3 | `docs/infrastructure/cache.md` |
| Route/Controller | 4 | `docs/conventions.md` §14 |
| Notification | 2 | `docs/conventions.md` §13 |
| Migration/DB | 4 | `docs/conventions.md` §17 |
| Language | 10 | `docs/conventions.md` §3 |
| Quality | 4 | `docs/conventions.md` §23 |
| Dual Mentor | 2 | `docs/conventions.md` §21 |

---

## Related Pattern References

| Document | Focus |
|----------|-------|
| [Action](action-pattern.md) | Action Triad deep dive, transaction safety, ActionResponse |
| [Entity](entity-pattern.md) | Entity bridge, immutability, entity extraction workflow |
| [Model (Active Record)](model-pattern.md) | Eloquent models, UUID PKs, scopes, relationships, casts |
| [Data (DTO)](data-pattern.md) | BaseData DTOs, fromArray/toArray, ActionResponse, migration path |
| [Event & Notification](event-pattern.md) | BaseEvent, dispatch patterns, listeners, multi-channel notifications |
| [Enum](enum-pattern.md) | State machine enums, LabelEnum/StatusEnum contracts, 35-enum inventory |
| [Livewire](livewire-pattern.md) | Thin components, Form Objects, BaseRecordManager, confirmation dialogs |
| [Exception](exception-pattern.md) | Dual hierarchy, HasExceptionContext, HandlesActionErrors |
| [Policy](policy-pattern.md) | Flat RBAC, three-layer auth, Gate::before, 29-policy inventory |
| [Logging](logging-pattern.md) | SmartLogger fluent API, PII masking (39+ keys) |
| [Cache](cache-pattern.md) | Key registry, invalidation strategies, driver tiers |
| [Service](service-pattern.md) | When to use Services vs Actions, existing inventory, migration path |
| [Repository](repository-pattern.md) | Why no Repository layer, Eloquent as Repository, query tiers |
| [Testing](testing-pattern.md) | Testing conventions, scope isolation, layer strategies, performance |

---

*This document is auto-synchronized with the codebase. When the architecture evolves, update the
relevant sections in `docs/architecture.md`, `docs/conventions.md`, or the ADRs, then reflect
changes here. See `docs/doc-index.md` for the complete documentation catalog.*
