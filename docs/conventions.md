# Coding Conventions

This document describes conventions for writing code in the Internara codebase. These rules exist to produce consistent, predictable code that any team member can read without context-switching.

## 0. Mandatory Base Classes

Every layer has a designated base class from the Core domain. You MUST use them — never roll your own patterns:

| Layer | Base Class | File |
|---|---|---|
| Model | `BaseModel` (or `Authenticatable` for User) | `Core/Models/BaseModel.php` |
| Action | `BaseAction` | `Core/Actions/BaseAction.php` |
| Entity | `BaseEntity` | `Core/Entities/BaseEntity.php` |
| Policy | `BasePolicy` | `Core/Policies/BasePolicy.php` |
| Livewire CRUD | `BaseRecordManager` | `Core/Livewire/BaseRecordManager.php` |
| Controller | `BaseController` | `Core/Http/Controllers/BaseController.php` |
| Form Request | `FormRequest` (Core's, not Laravel's) | `Core/Http/Requests/FormRequest.php` |
| Enum | `LabelEnum` (+ optionally `StatusEnum`, `ColorableEnum`) | `Core/Contracts/` |
| DTO | `Data` | `Core/Data/Data.php` |
| Exception | `AppException` or `DomainException` | `Core/Exceptions/` |

**Why:** These base classes provide UUID primary keys, transaction wrapping, automatic audit logging, role/ownership authorization, search/sort/filter/pagination, and consistent error handling. Skipping them means reimplementing the same infrastructure — and creating inconsistency.

**Consequence of not using them:** Violations will be caught by code review and PHPStan analysis.

## 1. File Structure

```
app/Domain/{Domain}/
├── Actions/        → Business logic entry points
├── Models/         → Eloquent data access
├── Livewire/       → Reactive UI
├── Policies/       → Authorization
├── Enums/          → Constants with behavior
├── Entities/       → Business rules (optional)
├── Http/           → Controllers & middleware (optional)
├── Notifications/  → Alerts (optional)
├── Events/         → Domain events (optional)
├── Listeners/      → Event subscribers (optional)
├── Console/        → Artisan commands (optional)
├── Support/        → Domain utilities (optional)
├── Services/       → Domain-specific services (optional)
└── Data/           → DTOs (optional)
```

**Rationale:** Colocation by domain means a feature touches one directory tree instead of eight. This reduces cognitive load and makes boundaries explicit.

## 2. General PHP

- `declare(strict_types=1)` in every file except migrations.
- Constructor property promotion (`public function __construct(protected readonly X $x) {}`).
- Explicit return types on every method.
- `===` over `==` unless loose comparison is intentional.
- Trailing commas on multiline arrays, function calls, and constructor params.
- `__()` for all user-facing strings.
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code.

## 3. Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Model | `{Name}` | `User`, `AcademicYear` |
| Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Entity | `{Name}` | `Apprentice`, `MenteeState` |
| Livewire | `{Name}` | `UserManager`, `StudentClockIn` |
| Policy | `{Name}Policy` | `UserPolicy` |
| Enum | `{Name}` | `AccountStatus`, `Role` |
| Enum case | `UPPER_SNAKE` | `SUPER_ADMIN`, `DRAFT` |
| Controller | `{Name}Controller` | `DashboardController` |
| Middleware | `{Name}Middleware` | `CheckRoleMiddleware` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index` |
| Config key | `snake_case` | `app.name` |
| Column/table | `snake_case` | `user_id` |
| Boolean method | `is`/`has`/`can`/`should` prefix | `isActive()`, `allowsLogin()` |

**Rationale:** Consistent naming means file search, grep, and autocomplete work reliably. `CreateUserAction` is instantly recognizable as an Action; `UserController` is clearly a Controller.

## 4. Models

- Extend `BaseModel` (provides UUID PK, `HasUuids`, non-incrementing, string key type). Exception: `User` extends `Authenticatable` directly.
- Use `#[Fillable([...])]` attribute for mass assignment, not `$fillable` property.
- Use `HasFactory` trait on every model.
- Relationship methods: singular for `BelongsTo`/`HasOne`, plural for `HasMany`/`BelongsToMany`.
- Expose entities via `as{EntityName}()` methods, never a generic `entity()`.

## 5. Actions

- Single `execute()` method. Never add a second public method.
- Constructor dependencies use `protected readonly` promotion.
- Wrap mutations in `DB::transaction()`.
- Use `HandlesActionErrors` trait (from BaseAction) for consistent try-catch-log-rethrow.
- Name: `{Verb}{Entity}Action` — `CreateUserAction`, not `UserCreator` or `CreateUser`.

## 6. Entities

- `final readonly` class extending `BaseEntity`.
- Zero framework dependencies — no Eloquent, no Facades, no Container.
- Bridge from persistence via `fromModel(Model): static`.
- Models expose them via `as{EntityName}()` accessor.

**Rationale:** Entities are the testable core of business logic. No database, no mocking, no setup — just construct and assert.

## 7. Enums

- All enums are `string`-backed.
- All implement `LabelEnum` (provides `label(): string`).
- State machine enums additionally implement `StatusEnum` (provides `canTransitionTo()`, `isTerminal()`, `validTransitions()`).
- Business logic methods live directly on the enum, not in a separate class.

### Enum Case Convention

Enum **cases** use `UPPER_SNAKE` (uppercase with underscores):

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

The `string` value (the database representation) stays lowercase. The case name
(the PHP code representation) is `UPPER_SNAKE`. This makes enum cases visually
distinct from other PHP identifiers (variables, methods, properties) and aligns
with PHP's own built-in enums (`UPLOAD_ERR_OK`, `JSON_HEX_TAG`, etc.).

```php
// ✅ Correct — uppercase case, lowercase value
case SUPER_ADMIN = 'super_admin';

// ❌ Wrong — case should be uppercase
case SuperAdmin = 'super_admin';
```

### Model $attributes Default

When setting default values in Eloquent model `$attributes`, use the enum's
`->value` property to stay in sync with the backing value:

```php
// ✅ Correct — references the enum
protected $attributes = [
    'status' => InternshipStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];
```

## 8. Policies

- Extend `BasePolicy` (provides `AuthorizesRoles` and `AuthorizesOwnership` traits).
- Register via `Gate::policy()` in `DomainServiceProvider` (auto-discovered from `app/Domain/*/Policies/`).
- `super_admin` bypasses all gates via `Gate::before()`.

## 9. Livewire Components

- CRUD tables extend `BaseRecordManager` (provides search, filter, sort, pagination, selection).
- Simple pages extend `Component`.
- Components delegate all writes to Actions.
- Computed properties use `#[Computed]`.
- Views at `resources/views/{domain}/{component}.blade.php`.

### 9a. Form Objects

Complex forms MUST be extracted into Livewire Form Objects under
`app/Domain/{Domain}/Livewire/Forms/{Name}Form.php`:

```
app/Domain/{Domain}/Livewire/
├── {Component}.php
└── Forms/
    ├── UserForm.php
    ├── SchoolForm.php
    └── InternshipForm.php
```

Rules:

- Form Objects extend `Livewire\Form`.
- Naming: `{Entity}Form` — `UserForm`, `InternshipForm`.
- All form state (public properties), validation rules, and form-specific
  logic live inside the Form Object.
- The parent component delegates to the Form Object via explicit `$form->validate()` calls.
- Form Objects must NOT call Actions directly — they return validated data
  to the parent component, which dispatches the Action.

Example structure:

```php
// app/Domain/Setup/Livewire/Forms/SchoolForm.php
class SchoolForm extends Form
{
    public string $name = '';
    public string $institutional_code = '';
    public string $email = '';
    public ?string $address = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'institutional_code' => 'required|string|max:50',
            'email' => 'required|email|max:255',
        ];
    }
}
```

```php
// Usage in SetupWizard:
class SetupWizard extends Component
{
    public SchoolForm $schoolForm;

    public function nextStep(): void
    {
        $this->schoolForm->validate();
        // ...
    }

    public function finish(): void
    {
        $data = $this->schoolForm->all();
        // dispatch Action with $data
    }
}
```

**Rationale:** Without Form Objects, components accumulate dozens of flat
`public` properties with scattering validation rules. Form Objects group
related fields, centralize validation, enable reuse across components, and
can be unit-tested independently of the Livewire lifecycle.

## 10. Controllers & Routes

- Controller suffix required: `DashboardController`.
- Controllers delegate to Actions — no business logic.
- Routes split by domain in `routes/web/{domain}.php`.
- All routes named with `->name()`.

## 11. Views

- Anonymous components: `x-shared::layouts.*`, `x-shared::ui.*`, `x-shared::widgets.*`.
- Livewire views: `resources/views/{domain}/{name}.blade.php`.
- `@props()` declaration at top of every component.
- maryUI components prefixed `x-mary-`.

## 12. Exceptions

Use the right exception type from `App\Domain\Core\Exceptions`:

**AppException hierarchy** (extends `RuntimeException`):
- `ActionException` — business operation failures, with `ConflictException` and `ValidationFailedException` subtypes.
- `InfrastructureException` — external system failures, with `RateLimitException` subtype.
- `PresentationException` — HTTP-level failures (404, 403), with `NotFoundException` and `UnauthorizedException` subtypes.

**DomainException hierarchy** (separate tree, extends `RuntimeException`):
- `DomainException` — domain invariant violations, with `RejectedException` subtype.

## 13. Code Quality Enforcement

Conventions in this document are enforced through code review and static analysis (PHPStan).
