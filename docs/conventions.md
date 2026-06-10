# Coding Conventions

> **Last updated:** 2026-06-10
> **Changes:** Synchronized with codebase audit — updated module count, added SmartLogger channel routing modes, updated Enum names, fixed cross-module communication patterns, added pre-commit checklist
> **Context:** ✅ All conventions are enforced through code review.

This document describes conventions for writing code in the Internara codebase. These rules exist to
produce consistent, predictable code that any team member can read without context-switching.

Conventions are organized from foundational (base classes, structure) to specific (commands, tests,
cache keys). Each section includes a rationale and examples.

For a comprehensive catalog of all design patterns, architectural rules, and workflow patterns
used across the codebase, see [Modular Pattern Reference](architecture/modular-pattern.md).

---

## 0. Documentation-First

### Documentation as Single Source of Truth

Documentation is the **authoritative reference** for the system. It defines what the system does,
how it is structured, and why decisions were made. Code implements what documentation describes —
not the other way around. When documentation and implementation disagree, documentation is the SSOT
and implementation must be corrected.

### Document First, Then Implement

Every change — feature, refactor, bug fix — begins with documentation. Before writing a single line
of code, the relevant docs must be updated to describe the intended outcome. This applies at all
scales:

- **New feature** → document the feature in `key-features.md`, update the module's conceptual doc
  (`{module}.md`) and API reference (`{module}-reference.md`)
- **Architecture change** → update `architecture.md` and any affected ADRs
- **Bug fix** → if the fix changes behavior, update the affected docs
- **Refactor** → if the refactor moves code between modules, update both modules' docs and
  `module-index.md`

Implementation follows documentation. The docs describe the target state; code catches up.

### Two Documentation Tiers

Each module has two documents serving different audiences:

| Document                             | Audience                             | Content                                                                                         |
| ------------------------------------ | ------------------------------------ | ----------------------------------------------------------------------------------------------- |
| `docs/modules/{module}.md`           | Architects, developers, stakeholders | Purpose, design principles, module boundary — pure conceptual design, no implementation details |
| `docs/modules/{module}-reference.md` | Developers, reviewers                | Full API reference — file paths, class names, table schemas, dependency graphs                  |

When describing a module's behavior, write the conceptual doc. When listing files or classes, write
the reference doc. Never mix implementation details into conceptual docs.

### Documentation Updates as Part of Definition of Done

A change is not complete until the relevant documentation is updated. This is enforced through code
review — a PR that changes code without corresponding doc updates is incomplete and must not be
merged.

---

## 1. Base Classes

Core provides base classes for every layer. Use them when they add value — skip them when they
don't.

| Layer                      | Base Class                                  | Provides                                                                                                    |
| -------------------------- | ------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Model                      | `BaseModel`                                 | UUID PK (`HasUuids`), non-incrementing, string key type                                                     |
| Action (Command / Process) | `BaseAction`                                | `transaction()`, `log()`, `HandlesActionErrors`                                                             |
| Action (Read)              | None required                               | Read operations don't need transaction or logging                                                           |
| Entity                     | `BaseEntity`                                | `final readonly`, `fromModel(Model): static` contract                                                       |
| State entity               | `BaseEntity`                                | State-machine helpers defined per entity                                                                    |
| Policy                     | `BasePolicy`                                | `AuthorizesRoles`, `AuthorizesOwnership` traits                                                             |
| Livewire CRUD              | `BaseRecordManager`                         | Search, filter, sort, pagination, bulk actions                                                              |
| Controller                 | `BaseController` (optional)                 | Marker for controllers, can extend Laravel's `Controller` directly                                          |
| Form Request               | `BaseFormRequest`                           | Consistent `ValidationFailedException` on failure (located in `app/Core/Http/Requests/BaseFormRequest.php`) |
| Enum                       | Implements `LabelEnum`                      | `label(): string`                                                                                           |
| Status enum                | Implements `StatusEnum` (+ LabelEnum)       | `canTransitionTo()`, `validTransitions()`, `isTerminal()`                                                   |
| Exception                  | Extends `AppException` or `ModuleException` | `HasExceptionContext` trait                                                                                 |
| Cache key                  | `config/cache-keys.php`                                | Centralized key registry, prevents collisions                                                               |
| DTO                        | `BaseData`                                  | `app/Core/Data/BaseData.php`                                                                                |
| Event                      | `BaseEvent`                                 | `Dispatchable`, `eventName()` for log translation, `toPayload()` for logging payload                        |

### Notes

- **User model**: Extends `Illuminate\Foundation\Auth\User` directly. Apply `HasUuids` trait
  manually for UUID consistency.
- **Notifications**: Extend `Illuminate\Notifications\Notification` directly.
- **Base classes are helpers, not mandates.** If a base class adds no value for your use case, use
  the framework class directly.

---

## 2. File Structure

### Path Convention

All code follows a strict two-tier path convention:

| Scope                 | Pattern                                                | Example                                            |
| --------------------- | ------------------------------------------------------ | -------------------------------------------------- |
| Module-specific       | `app/{Module}/{Submodule}/{Component}/{ClassName}.php` | `app/User/Profile/Actions/UpdateProfileAction.php` |
| Shared (cross-module) | `app/{Component}/{ClassName}.php`                      | `app/Data/AuditCheck.php`                          |

Where `{Component}` is the technical layer (Actions, Models, Policies, Livewire, etc.) and
`{ClassName}` is the PascalCase filename matching the class.

### Module Structure — Submodule-Based

Code is organized by module, then by **Submodule** within each module. Each submodule directory is a
self-contained vertical slice with its own technical component layers. Files that span multiple
submodules live at the module root.

> [!NOTE]
> For cross-cutting or system-wide modules/submodules (such as Settings, Enrollment, or Assessment), a **flat structure** directly under the module root is permitted to place component directories (e.g. `Actions/`, `Models/`, `Policies/`, etc.) without a submodule grouping layer, avoiding redundant namespace segments.

```
app/{Module}/
├── {SubModule}/                    → One directory per submodule root
│   ├── Actions/                    → Business operations (Command, Read, Process)
│   ├── Models/                     → Eloquent models belonging to this submodule
│   ├── Policies/                   → Authorization gates
│   ├── Livewire/                   → UI components (optional)
│   │   └── Forms/                  → Form Objects (optional)
│   ├── Entities/                   → Pure business rules (optional)
│   ├── Enums/                      → Enum specific to this submodule (optional)
│   ├── Events/                     → Module events (optional)
│   ├── Listeners/                  → Event subscribers (optional)
│   └── Notifications/              → Multi-channel alerts (optional)
├── Types/                          → Shared value objects, flat enums, rules (optional)
├── Actions/                        → Cross-submodule orchestration (optional)
├── Http/                           → Cross-submodule controllers & middleware (optional)
│   ├── Controllers/
│   └── Middleware/
├── Console/                        → Cross-submodule artisan commands (optional)
├── Livewire/                       → Cross-submodule UI (dashboards, etc.) (optional)
│   └── Forms/                      → Form Objects (optional)
├── Notifications/                  → Cross-submodule notifications (optional)
├── Events/                         → Cross-submodule events (optional)
├── Listeners/                      → Cross-submodule listeners (optional)
├── Support/                        → Shared module utilities (optional)
└── Services/                       → Infrastructure services (optional)
```

### Shared (Cross-Module) Structure

Code that is shared across multiple modules lives directly under `app/`, not inside any module
directory:

```
app/
├── Data/              → DTOs shared across modules (AuditCheck, AuditReport)
├── Enums/             → Cross-module enums (AuditCategory, AuditStatus, CsvRowResult)
├── Exceptions/        → Concrete exception classes (ConflictException, NotFoundException)
├── Livewire/          → Global UI components (LangSwitcher, ThemeSwitcher)
│   └── Concerns/      → Livewire traits (WithRecordSelection, WithSorting)
├── Policies/
│   └── Concerns/      → Policy traits (AuthorizesOwnership, AuthorizesRoles)
└── Support/           → Static utilities (Locale, Theme, PiiMasker, etc.)
```

### Views & Tests Mirror Structure

```
# Module views
resources/views/{module}/{submodule}/{component-name}.blade.php
  → app/{Module}/{Submodule}/Livewire/{ComponentName}.php

# Shared views
resources/views/livewire/{component-name}.blade.php
  → app/Livewire/{ComponentName}.php

# Module tests
tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php
  → app/{Module}/{Submodule}/{Component}/{Name}.php

# Shared tests
tests/{Feature,Unit}/{Component}/{Name}Test.php
  → app/{Component}/{Name}.php
```

### No Redundant Namespace Segments

The class name must never be repeated in the path. This keeps namespaces clean and prevents
unnecessary nesting:

```php
// ✅ Correct — unique segments
app/User/Models/User.php              // namespace App\User\Models
app/Academics/School/Models/School.php // namespace App\Academics\School\Models

// ❌ Wrong — redundant segment duplicates the class name
app/User/User/Models/User.php         // namespace App\User\User\Models
app/Program/Internship/Internship/Models/Internship.php
```

### Submodule Grouping Rules

- **Submodule directory** — named after the submodule root concept (`User/`, `Profile/`,
  `Internship/`, `Placement/`, etc.)
- **`Types/`** — value objects, simple enums, and validation rules too small for their own
  submodule. Examples: `Gender.php`, `BloodType.php`, `SystemUsername.php`.
- **Root `Actions/`** — cross-submodule orchestration (dashboard stats, multi-submodule queries,
  services that span submodules).
- **Root `Http/`** — cross-submodule controllers (dashboards, home page).
- **Root `Console/`** — module-wide artisan commands (not specific to one submodule).
- **Root `Livewire/`** — cross-submodule UI components (dashboards, global widgets).
- **Root `Support/`** — shared utilities not belonging to any single submodule.

### Submodule Encapsulation Rules

1. Files inside an submodule directory MUST NOT import from sibling submodule directories within the
   same module. Cross-submodule access goes through the module root.
2. Root module files (`Actions/`, `Http/`, `Console/`, `Livewire/`) MAY import from any submodule
   within the same module — they are the coordination layer.
3. An submodule MAY import from other modules (respecting cross-module rules in
   [architecture.md](architecture.md)).

### Services vs Support

| Directory   | Purpose                                                      | Example                            |
| ----------- | ------------------------------------------------------------ | ---------------------------------- |
| `Support/`  | Pure utility classes, no Eloquent, no framework dependencies | `Theme`, `CsvHandler`, `PiiMasker` |
| `Services/` | Framework-aware infrastructure code                          | `EnvironmentAuditor`, `PulseGuard` |

Prefer `Support/` for stateless utilities. Use `Services/` only when the class depends on framework
services (container, config, facades) and does not fit the Action pattern.

---

## 3. General PHP

- `declare(strict_types=1)` in every file except migrations and config.
- Constructor property promotion: `public function __construct(protected readonly X $x) {}`. Do not
  leave empty zero-parameter constructors unless private.
- Explicit return types on every method: `function isAccessible(User $user): bool`.
- Type hints on all parameters: `function find(string $id): ?Model`.
- `===` over `==` unless loose comparison is intentional.
- Trailing commas on multiline arrays, function calls, constructor params.
- `__()` for all user-facing strings. Never hardcode display text.
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code.
- Use `match()` instead of long `switch()` blocks when returning a value from an expression.
- `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`.
- Null-safe operator `?->` and null coalescing `??` over explicit null checks.
- Readonly properties prefer promoted constructor parameters over `#[Readonly]`.

---

## 4. Naming Conventions

| Element                    | Convention                                                    | Example                                                               |
| -------------------------- | ------------------------------------------------------------- | --------------------------------------------------------------------- |
| Submodule directory        | Singular `{Name}` (submodule root concept)                    | `User`, `Profile`, `Internship`, `Placement`                          |
| Types directory            | `Types/` for small value objects                              | `Types/Gender.php`, `Types/BloodType.php`                             |
| Model                      | Singular `{Name}`                                             | `User`, `AcademicYear`, `Internship`                                  |
| Command Action             | `{Verb}{Entity}Action`                                        | `CreateUserAction`, `ApproveRegistrationAction`                       |
| Read Action                | `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`      | `InternshipDashboardReader`, `GetStudentStatsData`                    |
| Process Action             | `{Verb}{Entity}Process`                                       | `RegisterStudentProcess`, `CloseInternshipProcess`                    |
| Entity                     | `{Name}`                                                      | `Apprentice`, `InternshipPeriod`, `RegistrationState`, `SchoolEntity` |
| Data / DTO                 | `{Verb}{Entity}Data` or `{Entity}Data` (extending `BaseData`) | `SetupTokenData`, `AuditCheck`                                        |
| Livewire                   | `{Name}` — suffixed with Manager, Editor, Center              | `UserManager`, `ProfileEditor`, `RegistrationCenter`                  |
| Livewire alias (submodule) | `{kebab-module}.{kebab-submodule}.{kebab-name}`               | `admin.user.user-manager`                                             |
| Livewire alias (root)      | `{kebab-module}.{kebab-name}`                                 | `user.profile-editor`                                                 |
| Livewire Form              | `{Entity}Form` (extending `Livewire\Form`)                    | `AcademicYearForm`, `SchoolForm`                                      |
| Policy                     | `{Name}Policy`                                                | `UserPolicy`, `InternshipPolicy`                                      |
| Exception                  | `{Name}Exception`                                             | `ConflictException`, `ValidationFailedException`                      |
| Controller                 | `{Name}Controller`                                            | `DashboardController`, `ReportController`                             |
| Middleware                 | `{Name}Middleware`                                            | `CheckRoleMiddleware`, `SetLocaleMiddleware`                          |
| Event                      | `{Entity}{Actioned}` — past tense                             | `InternshipCreated`, `ReportApproved`, `StudentRegistered`            |
| Listener                   | `{Verb}{Entity}` or react to event name                       | `NotifyAdminsInternshipCreated`, `LogSetupFinalized`                  |
| Notification               | `{Entity}{NotificationType}Notification`                      | `InternshipCreatedNotification`, `WelcomeNotification`                |
| Console command            | `{module}:{action}`                                           | `system:health`, `admin:recover`, `notifications:prune`               |
| Route name                 | `{prefix}.{resource}.{action}`                                | `admin.users.index`, `internship.reports.show`                        |
| Config key                 | `snake_case` with `{file}.{key}`                              | `app.name`, `database.default`                                        |
| Column / table             | `snake_case`                                                  | `user_id`, `academic_year_id`, `academic_years`                       |
| Boolean method             | `is`/`has`/`can`/`should` prefix                              | `isActive()`, `allowsLogin()`, `canTransitionTo()`                    |
| Test method                | Pest `it()` with descriptive string                           | `it('creates a user with valid data')`                                |
| Test file                  | `{Name}Test.php`                                              | `CreateUserActionTest.php`, `UserManagerTest.php`                     |
| Factory                    | `{Name}Factory`                                               | `UserFactory`, `InternshipFactory`                                    |
| Migration                  | `YYYY_MM_DD_HHMMSS_create_{table}_table.php`                  | `2026_04_29_092750_create_users_table.php`                            |

---

## 5. Models

- Extend `BaseModel` (UUID PK, `HasUuids`, non-incrementing, string key type). Exception: `User`
  extends `Authenticatable` with manual `HasUuids`.
- Use `#[Fillable([...])]` attribute for mass assignment, never `$fillable` property.
- Use `HasFactory` trait on every model.
- Use `#[Appends([...])]` for computed accessors.
- Use `#[Cast([...])]` or `protected $casts` for attribute casting.

### Relationships

| Type                        | Method Name | Example                      |
| --------------------------- | ----------- | ---------------------------- |
| `BelongsTo` / `HasOne`      | Singular    | `user()`, `academicYear()`   |
| `HasMany` / `BelongsToMany` | Plural      | `users()`, `registrations()` |
| `MorphTo`                   | Singular    | `verifiable()`               |
| `MorphMany`                 | Plural      | `comments()`                 |

Always define the inverse relationship. Use `->foreignUuid()->constrained()` in migrations.

### Entity Accessors

Expose entities via specific named accessors, never a generic `entity()`:

```php
// ✅ Correct
public function asInternshipPeriod(): InternshipPeriod
public function asInternshipState(): InternshipState

// ❌ Wrong
public function entity(): InternshipPeriod
```

### Factory Method

```php
protected static function newFactory(): InternshipFactory
{
    return InternshipFactory::new();
}
```

---

## 6. Actions: Command, Read, Process

Actions are the single entry point for business operations. There are three types, each with a
distinct contract.

### 5a. Command Actions (Mutations)

**Purpose:** Every write to the system — create, update, delete, state transitions.

**Base class:** `BaseAction` (provides `transaction()`, `log()`, `HandlesActionErrors`).

**Contract:**

- Single public `execute()` method. Never add a second public method.
- MUST wrap all database operations in `$this->transaction()`.
- MUST call `$this->log()` after successful mutation.
- SHOULD dispatch a module event for significant state changes.
- MUST be preceded by a policy check in the calling layer.
- Constructor dependencies use `protected readonly` promotion.

**Naming:** `{Verb}{Entity}Action` — `CreateUserAction`, `ApproveRegistrationAction`.

**Example:**

```php
class SubmitLogbookAction extends BaseAction
{
    public function __construct(protected readonly NotifyMentorAction $notifyMentor) {}

    public function execute(Logbook $entry, array $data): Logbook
    {
        return $this->transaction(function () use ($entry, $data) {
            $entry->update([
                'content' => $data['content'],
                'status' => LogbookStatus::SUBMITTED->value,
            ]);

            $this->log('logbook_submitted', $entry);
            event(new LogbookSubmitted($entry));

            return $entry;
        });
    }
}
```

### 5b. Read Actions (Queries)

**Purpose:** Complex read operations — aggregation, filtering, cross-module data assembly. Not for
simple `Model::find()` or `Model::where()` — those stay in Livewire.

**Base class:** None required. A plain class with constructor injection. May use
`HandlesActionErrors` from `BaseAction` but MUST NOT call `transaction()` or `log()`.

**Contract:**

- MUST NOT mutate any database state.
- MUST NOT call `transaction()` or `log()`.
- SHOULD return typed objects or collections, never raw arrays.
- MUST pass through authorization unless the calling layer already authorized.

**Naming:** `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`.

**Example:**

```php
class InternshipProgressReader
{
    public function __construct(protected readonly Internship $model) {}

    public function completionStats(Internship $program): array
    {
        $total = $this->model->registrations()->count();
        $completed = $this->model->registrations()->whereHas('certificates')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
```

### 5c. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions.

**Base class:** `BaseAction` (same as Command — transaction + logging at the process level).

**Contract:**

- MUST compose other Actions via constructor injection.
- MUST handle partial failure — what happens to steps 1–2 if step 3 fails?
- SHOULD emit a single module event representing the completed process.
- MUST NOT duplicate business logic that already exists in Command Actions.

**Naming:** `{Verb}{Entity}Process` — `RegisterStudentProcess`, `CloseInternshipProcess`.

**Example:**

```php
class RegisterStudentProcess extends BaseAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyMentor->execute($registration);

            $this->log('student_registered', $registration);
            event(new StudentRegistered($registration));

            return $registration;
        });
    }
}
```

### Action Decision Reference

| Scenario                 | Pattern            | Base Class   | Transaction | Logging     | Event          |
| ------------------------ | ------------------ | ------------ | ----------- | ----------- | -------------- |
| Create/update/delete     | Command            | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| State transition         | Command            | `BaseAction` | ✅ Required | ✅ Required | ✅ Required    |
| Send notification        | Command            | `BaseAction` | ✅ Required | ✅ Required | ❌             |
| Simple list query        | Inline in Livewire | None         | ❌          | ❌          | ❌             |
| Complex aggregated query | Read Action        | None         | ❌          | ❌          | ❌             |
| Dashboard statistics     | Read Action        | None         | ❌          | ❌          | ❌             |
| Multi-step orchestration | Process            | `BaseAction` | ✅ Required | ✅ Required | ✅ Required    |

---

## 7. Entities

- `final readonly` class extending `BaseEntity` (from `app/Core/Entities/BaseEntity.php`).
- All state via constructor with `private` properties (never `public`). Expose via getter methods.
- Bridge from persistence via `fromModel(Model): static`.
- Named accessors on models: `asRegistrationState()`, `asInternshipPeriod()`.
- Business logic methods only — no persistence, no HTTP, no I/O.
- May use static factory methods (e.g., `get()`) for entities backed by settings instead of a model.
- Framework dependencies (Carbon, Eloquent) allowed when practical.

```php
final readonly class SchoolEntity extends BaseEntity
{
    public function __construct(
        private string $name,
        private string $institutionalCode,
        private string $email,
        private string $address = '',
        private string $phone = '',
        private string $website = '',
        private string $principalName = '',
    ) {}

    public static function fromModel(Model $model): static
    {
        return self::get();
    }

    public static function get(): self
    {
        $values = Settings::get([
            'school.name',
            'school.institutional_code',
            'school.email',
            'school.address',
            'school.phone',
            'school.website',
            'school.principal_name',
        ]);

        return new self(
            name: (string) ($values['school.name'] ?? ''),
            institutionalCode: (string) ($values['school.institutional_code'] ?? ''),
            email: (string) ($values['school.email'] ?? ''),
            address: (string) ($values['school.address'] ?? ''),
            phone: (string) ($values['school.phone'] ?? ''),
            website: (string) ($values['school.website'] ?? ''),
            principalName: (string) ($values['school.principal_name'] ?? ''),
        );
    }

    public function name(): string
    {
        return $this->name;
    }
    public function email(): string
    {
        return $this->email;
    }
    public function phone(): string
    {
        return $this->phone;
    }
    // ...
}
```

**Rationale:** Entities keep business logic testable and isolated from raw Eloquent access patterns.
Framework dependencies (Eloquent, Carbon) are allowed when practical.

---

## 8. Enums

- All enums are `string`-backed.
- All implement `LabelEnum` (provides `label(): string`).
- State machine enums additionally implement `StatusEnum` (provides `canTransitionTo()`,
  `isTerminal()`, `validTransitions()`).
- Optionally implement `ColorableEnum` (provides `color(): string` for UI badges).
- Business logic methods live directly on the enum, not in a separate class.

### Case Convention

Enum cases use `UPPER_SNAKE`. The backing string value stays lowercase:

```php
enum InternshipStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### Model $attributes Default

Use the enum's `->value` property, never a hardcoded string:

```php
// ✅ Correct
protected $attributes = [
    'status' => InternshipStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];
```

---

## 9. Policies

- Extend `BasePolicy` (provides `AuthorizesRoles` and `AuthorizesOwnership` traits).
- Auto-discovered from `app/*/Policies/` by `AppServiceProvider`. Convention: `{Model}Policy` in the
  same module as `{Model}`.
- Cross-module policies (where a policy gates a model from another module) must be registered
  manually in `AppServiceProvider::boot()`.
- `super_admin` bypasses all gates via `Gate::before()`.

```php
class AcademicYearPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

---

## 10. Livewire Components

- CRUD table components extend `BaseRecordManager` (provides search, filter, sort, pagination,
  selection, bulk actions).
- Simple page components extend `Component`.
- Components delegate all writes to Command Actions.
- Components delegate complex queries to Read Actions.
- Computed properties use the `#[Computed]` attribute.
- Submodule-specific components: `app/{Module}/{SubModule}/Livewire/{Name}.php`
- Cross-submodule components: `app/{Module}/Livewire/{Name}.php`
- Shared cross-module components: `app/Livewire/{Name}.php`
- Views mirror the app structure:
    - Submodule view: `resources/views/{module}/{submodule}/{component-name}.blade.php`
    - Cross-submodule view: `resources/views/{module}/{component-name}.blade.php`
    - Shared view: `resources/views/livewire/{component-name}.blade.php`
- Component alias (submodule): `{kebab-module}.{kebab-submodule}.{kebab-name}` — e.g.,
  `admin.user.user-manager`
- Component alias (cross-submodule): `{kebab-module}.{kebab-name}` — e.g., `user.profile-editor`
- Component alias (shared): `{kebab-component-name}` — e.g., `livewire.lang-switcher`

### Form Objects

Complex forms MUST be extracted into `app/{Module}/Livewire/Forms/{Name}Form.php`:

```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
```

**Rules:**

- Form Objects extend `Livewire\Form`, not `BaseAction`.
- Naming: `{Entity}Form` — `UserForm`, `InternshipForm`.
- All form state, validation rules, and `toArray()` logic live inside the Form Object.
- Form Objects validate via explicit `$form->validate()` in the parent component.
- Form Objects must NOT call Actions directly — they prepare data for the component to dispatch.

---

## 11. Data / DTOs

DTOs are optional but recommended for Action inputs that have stabilized (3+ parameters or multiple
callers). They live in `app/{Module}/Data/` or `app/Data/` for shared DTOs.

- Extend `App\Core\Data\BaseData` (from `app/Core/Data/BaseData.php`).
- `final readonly` class with typed `public` constructor parameters (contrast with Entities which
  use `private` properties).
- Supports `toArray()`, `fromArray()`, and `from()` for polymorphic construction.
- Use `BaseData::fromArray()` during migration for backward compatibility.

```php
final readonly class SetupTokenData extends BaseData
{
    public function __construct(public string $plaintext, public Carbon $expiresAt) {}
}
```

**Migration path:**

```
Phase 1 — execute(array $data)            → rapid development
Phase 2 — execute(Data|array $data)       → accepts both (union type)
Phase 3 — execute(Data $data)             → DTO only (final)
```

---

## 12. Events & Listeners

Events decouple side effects from core business logic. They are optional but encouraged when a
Command Action triggers multiple downstream reactions.

### Event Conventions

- All new events MUST extend `BaseEvent` (`app/Core/Events/BaseEvent.php`).
- `BaseEvent` provides the `Dispatchable` trait and the `eventName(): string` contract — used by
  SmartLogger for log description translation.
- Events are lightweight classes with public typed properties.
- Events belong to the module that emits them.
- Event naming: `{Entity}{PastTenseAction}` — `InternshipCreated`, `ReportApproved`.

```php
final readonly class InternshipCreated extends BaseEvent
{
    public function __construct(
        public readonly Internship $internship,
        public readonly ?User $createdBy = null,
    ) {}

    public function eventName(): string
    {
        return 'internship_created';
    }
}
```

### Listener Conventions

- Listeners implement `ShouldQueue` for non-critical side effects.
- Listeners can live in any module.
- Listener naming: describe what the listener does — `NotifyAdminsInternshipCreated`,
  `InvalidateDashboardCache`, `LogSetupFinalized`.

```php
class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        $admins = User::role(['super_admin', 'admin'])->get();
        Notification::send(
            $admins,
            new InternshipCreatedNotification(internshipName: $event->internship->name),
        );
    }
}
```

### Registration

Listeners are registered in `AppServiceProvider::boot()`:

```php
Event::listen(SetupFinalized::class, [LogSetupFinalized::class, 'handle']);
```

### SmartLogger Integration

Events extending `BaseEvent` integrate automatically with SmartLogger. Pass the event object to
`event()` instead of a string key:

```php
SmartLogger::success('User registered')->event(new UserRegistered($user))->for($admin)->save();
```

When a `BaseEvent` is passed:

1. **Dispatch**: `event($baseEvent)` is called automatically inside `save()`.
2. **Event name**: `$baseEvent->eventName()` provides the log translation key (replaces the string
   argument).
3. **Payload merging**: `$baseEvent->toPayload()` (public properties) is merged first, then explicit
   `withPayload()` overrides.

This pattern ensures every event is both dispatched and logged with a single fluent call, keeping
the audit trail consistent with the event structure.

---

## 13. Notifications

- Extend `Illuminate\Notifications\Notification`.
- Implement `ShouldQueue` for channel delivery via queue worker.
- Define channels via the `via()` method.
- Use `CustomDatabaseChannel::class` for in-app database notifications.
- Naming: `{Entity}{NotificationType}Notification` — `InternshipCreatedNotification`.

```php
class InternshipCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $internshipName) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('notifications.internship_created.mail_subject'))
            ->line(
                __('notifications.internship_created.mail_line1', [
                    'name' => $this->internshipName,
                ]),
            );
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'internship_created',
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.database', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
        ];
    }
}
```

---

## 14. Controllers & Routes

### Controllers

- Controller suffix required: `DashboardController`, `ReportController`.
- Controllers delegate to Actions — no business logic in controller methods.
- Controllers are optional — prefer Livewire components for interactive pages.

### Routes

- Routes are split by module in `routes/web/{module}.php`.
- Master `routes/web.php` requires them in dependency order.
- All routes use `->name()`: `Route::get(...)->name('admin.users.index')`.
- Route naming: `{prefix}.{resource}.{action}`.
- Route model binding: use explicit binding with `Route::bind()` or `@` notation.

### Middleware Groups

```php
Route::middleware(['auth', 'auth.throttle'])->group(function () { ... });
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () { ... });
Route::middleware(['guest', 'auth.throttle'])->group(function () { ... });
```

- `auth` — requires authentication.
- `guest` — redirects authenticated users away.
- `role:{roles}` — checks user role via `CheckRoleMiddleware`.
- `auth.throttle` — rate limiting for auth endpoints.
- `verified` — requires email verification (optional, configurable).

---

## 15. Console Commands

- Command signature follows `{module}:{action}` naming.
- Use verb-noun pairs: `system:health`, `admin:recover`, `notifications:prune`.
- Arguments use curly braces: `{email?}`, `{--force}`.
- Use Laravel's `Command` base class (not a Core base class).
- Commands live in the owning module's `Console/Commands/` directory.

```php
class HealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';

    protected $description = 'Perform a comprehensive system health check';

    public function handle(): int
    {
        // ...
        return Command::SUCCESS;
    }
}
```

---

## 16. Blade Views

Views mirror the submodule-based source structure:

```
resources/views/{module}/
├── {submodule}/                    → Views for a specific submodule
│   ├── {component-name}.blade.php  → Livewire component view
│   └── components/                 → Sub-views (optional)
├── layouts/                        → Module-specific layouts (cross-cutting)
├── components/                     → Shared sub-views (cross-cutting)
└── partials/                       → Reusable partials (cross-cutting)
```

- Submodule-specific views: `resources/views/{module}/{submodule}/{component-name}.blade.php` —
  mirrors the Livewire component path `app/{Module}/{SubModule}/Livewire/`.
- Cross-submodule views: `resources/views/{module}/{component-name}.blade.php` — for dashboards and
  components that span multiple submodules.
- Anonymous components: `x-core::layouts.*`, `x-core::ui.*`, `x-core::widgets.*`.
- `@props()` declaration at the top of every component template.
- maryUI components prefixed with `x-mary-`.
- Layouts: `x-core::layouts.app` (authenticated), `x-core::layouts.guest` (public).
- Module-specific layouts in `resources/views/{module}/layouts/`.

---

## 17. Migrations, Factories & Seeders

### Migrations

- Naming: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`.
- All foreign keys use `foreignUuid()->constrained('{table}')`.
- Follow UUID FK with explicit `onDelete()` / `onUpdate()` behavior:
    - `cascadeOnDelete()` — child cannot exist without parent
    - `onDelete('set null')` — relationship is optional
    - `onDelete('restrict')` — deletion should be prevented
- Composite indexes for common query patterns: `->index(['user_id', 'date'])`.
- Each migration file handles one table or one logical change.
- Indices are created explicitly, not relying on FK auto-indexing.

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
    $table->date('date');
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    $table->string('status');
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('verified_at')->nullable();
    $table->index(['user_id', 'date']);
    $table->timestamps();
});
```

### Factories

- One factory per model, extending `Illuminate\Database\Eloquent\Factories\Factory`.
- Define model-specific states via `state()` methods.
- Factory naming: `{Name}Factory` — `InternshipFactory`, `UserFactory`.

```php
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(6),
            'status' => InternshipStatus::DRAFT->value,
        ];
    }

    public function published(): static
    {
        return $this->state(
            fn(array $attrs) => [
                'status' => InternshipStatus::PUBLISHED->value,
            ],
        );
    }
}
```

### Seeders

- Seeders are idempotent — running them multiple times does not duplicate data.
- Seeding order respects module dependencies: school → user → permissions → internships.
- Use `firstOrCreate()` for reference data, `create()` for test data.

---

## 18. Cache Keys

- Every cache key MUST be declared in `config/cache-keys.php`.
- Naming: `{module}.{purpose}[.{qualifier}]`.
- TTL and invalidation trigger are documented in comments.
- Access via `config('cache-keys.{key_name}')`.

```php
// config/cache-keys.php
return [
    /** Invalidation: SetupFinalized event */
    'setup_installed' => 'setup.is_installed',

    /** TTL: 5 minutes. Invalidation: manual flush */
    'admin_dashboard_stats' => 'admin.dashboard.stats',

    /** TTL: forever. Invalidation: Settings update */
    'theme_css_variables' => 'theme.css_variables',

    /** Key pattern: notification.unread:{userId} */
    'notification_unread' => 'notification.unread:',
];
```

Usage in code:

```php
Cache::forget(config('cache-keys.admin_dashboard_stats'));
Cache::remember(config('cache-keys.setup_installed'), ...);
```

---

## 19. Cross-Module Communication

Cross-module imports are **allowed** — import Models, Actions, Policies, or other classes from
sibling modules directly when needed.

```php
// ✅ Direct import — perfectly fine
use App\Academics\Models\AcademicYear;

$activeYear = AcademicYear::where('is_active', true)->first();
```

### Guidelines

1. **Direct import** (simplest, preferred for straightforward access)
2. **Module Events** (preferred when the same event triggers 2+ independent reactions)
3. **Action delegation** (fine for cross-module Action calls)
4. **Core contracts** (useful for abstractions used broadly across modules)

Use events when you want to add new reactions without modifying the caller. Use direct imports for
everything else.

---

## 20. Exception Hierarchy & Handling

Two separate exception hierarchies exist. Both use the `HasExceptionContext` trait for consistent
CLI-friendly output and error hinting:

1. **`AppException` Hierarchy** (abstract base: `app/Core/Exceptions/AppException.php`):
    - Derives from standard PHP `RuntimeException`.
    - Used for application-level, HTTP-level, or infrastructure-level failures.
    - Concrete exceptions include:
        - `ConflictException` (conflict state / duplicate records)
        - `NotFoundException` (HTTP 404 / resource missing)
        - `UnauthorizedException` (HTTP 403 / permission denied)
        - `ValidationFailedException` (HTTP 422 / request validation error)
        - `RateLimitException` (HTTP 429 / request rate limit exceeded)

2. **`ModuleException` Hierarchy** (abstract base: `app/Core/Exceptions/ModuleException.php`):
    - Derives from standard PHP `RuntimeException` (does _not_ extend `AppException` to isolate
      domain invariant checks from framework catch blocks).
    - Used for violations of business rules or invalid model transitions.
    - Concrete exceptions include:
        - `RejectedException` (thrown when a domain rule or invariant is violated)

### Rules & Conventions

- All abstract base exceptions live in `app/Core/Exceptions/`.
- All concrete exceptions live alongside their abstract bases in `app/Core/Exceptions/`.
- Actions must throw specific concrete exceptions with a clear error message, optional hint, and
  relevant metadata context.

---

## 21. Dual Mentor Fallback Protocol

Internara implements a **Dual Mentor Fallback & Optionality Protocol** to ensure that academic
progress and student workflows are never blocked by industry supervisor inactivity.

### Coding & Architectural Conventions

1. **Actions & Parameters Optionality**:
    - Any Action that performs verification or sign-off (e.g., `VerifyAttendanceAction`,
      `FinalizeLogbookAction`, `SubmitEvaluationAction`) must accept nullable parameters or have
      fallback paths if the industry supervisor is unavailable.
2. **Bypass Window (Journals/Attendance)**:
    - Reflective logbooks and attendances support a Teacher override. If a logbook remains in the
      `SUBMITTED` state for more than the bypass window (default: 48 hours), the assigned `Teacher`
      can bypass the supervisor.
    - The corresponding Command Action (e.g. `BypassSupervisorVerificationAction` or equivalent)
      must:
        - Transition the logbook/attendance to `FINALIZED` / `VERIFIED`.
        - Record `verified_by_fallback = true` or set the fallback verifier fields.
        - Append an audit trail log using `SmartLogger` detailing the teacher who authorized the
          override.
        - Clear the supervisor's pending verification queue.
3. **Grading & Rubric Fallback**:
    - End-of-placement competency evaluations support:
        - **Proxy Entry**: Enabling the `Teacher` to enter scores on behalf of the supervisor
          (controlled via a proxy toggle).
        - **Weight Redistribution**: Dynamically redistributing the supervisor's weight (40%) to the
          Teacher (new weight: 40%) and Report/Exam (new weight: 60%) if no supervisor scores are
          submitted.
4. **Transparent Compliance Stamping**:
    - Any document or certificate compiled using fallback weights or proxy scores must be stamped
      with a metadata tag (`fallback_weights` or `proxy_scores`) to maintain transparent compliance
      audits.

---

## 22. Testing

### File Structure

Tests mirror the source structure exactly:

```
tests/Feature/{Module}/{SubModule}/{Name}Test.php  → Module integration tests (Actions, Livewire)
tests/Unit/{Module}/{SubModule}/{Name}Test.php     → Module pure unit tests (Entities, Enums)
tests/Unit/{Module}/Types/{Name}Test.php           → Value objects, flat enums, rules
tests/{Feature,Unit}/{Component}/{Name}Test.php    → Shared component tests (Data, Enums, Exceptions, Livewire)
```

**Examples:**

- `tests/Feature/User/Profile/UpdateProfileActionTest.php` → mirrors
  `app/User/Profile/Actions/UpdateProfileAction.php`
- `tests/Unit/Enums/AuditCategoryTest.php` → mirrors `app/Enums/AuditCategory.php`
- `tests/Feature/Livewire/LangSwitcherTest.php` → mirrors `app/Livewire/LangSwitcher.php`
- `tests/Feature/Livewire/ThemeSwitcherTest.php` → mirrors `app/Livewire/ThemeSwitcher.php`

### Scope Isolation (CRITICAL)

- **Do NOT combine multiple distinct testing scopes into a single test file** (e.g., do not group
  multiple separate console commands or actions into a single `ConsoleCommandsTest` or `SetupTest`).
- Each command, action, and component must have its own **dedicated test file** to ensure
  single-responsibility and comprehensive test coverage from all angles.
- If a test file becomes too fat or too thin, it should be split or deepened appropriately.
- Ensure you test each component thoroughly from multiple angles (happy path, validation failures,
  edge cases, error handling, mock assertions) including its entire chain of dependencies.

### Naming

```php
// Feature test — describe the Action/Component being tested
describe('CreateInternshipAction', function () {
    it('creates an internship with active academic year', function () { ... });
    it('creates an internship without academic year', function () { ... });
});

// Unit test — describe the Entity or Enum
describe('AccountStatus', function () {
    it('prevents login for suspended accounts', function () { ... });
    it('allows transition from activated to verified', function () { ... });
});
```

### Feature Tests

- Test Command Actions in isolation: factory → execute → assert database/state.
- Test Process Actions: complete workflow + partial failure scenarios.
- Test Livewire components: render → interact → assert state/redirect.
- Use `LazilyRefreshDatabase` for test isolation.
- Do NOT test Eloquent relationships or model scopes directly — test through Actions.

### Unit Tests

- Entities: `new Entity(...)` → assert business rule methods.
- Enums: assert `label()`, transition rules, terminal states.
- Data DTOs: construct via constructor or `fromArray()` → assert `toArray()`.
- Policies: mock user/model → assert boolean gate methods.

### What NOT to Test

- Eloquent model relationships (framework behavior, test through feature tests).
- Simple getters/setters on models.
- Configuration loading.
- Framework-provided functionality (UUID generation, pagination, etc.).

---

## 23. Code Quality Enforcement

| Tool             | What It Enforces                                              | How                                         |
| ---------------- | ------------------------------------------------------------- | ------------------------------------------- |
| **Laravel Pint** | PHP code style (PSR-12 + Laravel conventions)                 | `vendor/bin/pint` before finalizing changes |
| **PHPStan**      | Static analysis (type safety, dead code, boundary violations) | `vendor/bin/phpstan analyse`                |
| **Prettier**     | Markdown, JSON, YAML, Blade formatting                        | `npm run format`                            |
| **Code Review**  | Architecture conventions, pattern compliance                  | Manual review of every PR                   |

### Pre-commit Checklist

- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `var_dump()`, `ray()` left in code
- [ ] All user-facing strings use `__()` helper
- [ ] Action follows the correct triad pattern (Command/Read/Process)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`
