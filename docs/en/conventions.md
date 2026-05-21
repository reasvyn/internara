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
| State | `BaseState` | `Core/States/BaseState.php` |
| DTO | `Data` | `Core/Data/Data.php` |
| Exception | `AppException` or `DomainException` | `Core/Exceptions/` |
| Enum contract | `LabelEnum` (and optionally `StatusEnum`, `ColorableEnum`) | `Core/Contracts/` |

**Why:** These base classes provide UUID primary keys, transaction wrapping, automatic audit logging, role/ownership authorization, search/sort/filter/pagination, and consistent error handling. Skipping them means reimplementing the same infrastructure — and creating inconsistency.

**Consequence of not using them:** Architecture tests will fail. `ModelLayerArchTest` checks that models extend `BaseModel`. `PolicyLayerArchTest` checks that policies extend `BasePolicy`. `EntityLayerArchTest` checks that entities are `final readonly` and extend `BaseEntity`.

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
- Register via `Gate::policy()` in `AppServiceProvider`.
- `super_admin` bypasses all gates via `Gate::before()`.

## 9. Livewire Components

- CRUD tables extend `BaseRecordManager` (provides search, filter, sort, pagination, selection).
- Simple pages extend `Component`.
- Components delegate all writes to Actions.
- Computed properties use `#[Computed]`.
- Views at `resources/views/{domain}/{component}.blade.php`.

## 10. Controllers & Routes

- Controller suffix required: `DashboardController`.
- Controllers delegate to Actions — no business logic.
- Routes split by domain in `routes/web/{domain}.php`.
- All routes named with `->name()`.

## 11. Views

- Anonymous components: `x-layouts::*`, `x-ui::*`, `x-widget::*`.
- Livewire views: `resources/views/{domain}/{name}.blade.php`.
- `@props()` declaration at top of every component.
- maryUI components prefixed `x-mary-`.

## 12. Exceptions

Use the right exception type from `App\Domain\Core\Exceptions`:
- `ActionException` for business operation failures.
- `DomainException` for domain invariant violations.
- `PresentationException` for HTTP-level failures (404, 403).
- `InfrastructureException` for external system failures.

## 13. Architecture Rules (Enforced by Tests)

| Rule | Enforced By |
|------|------------|
| `declare(strict_types=1)` in all app files | `StrictTypesArchTest` |
| No `dd()`, `dump()`, etc. | `StrictTypesArchTest` |
| Controllers don't import Actions or Models | `LayerSeparationArchTest` |
| Notifications don't import Livewire | `LayerSeparationArchTest` |
| Events don't import Actions | `LayerSeparationArchTest` |
| Services don't import Livewire | `LayerSeparationArchTest` |
| Entities extend `BaseEntity`, are `final readonly` | `EntityLayerArchTest` |
| Enums are string-backed, implement `LabelEnum` | `EnumLayerArchTest` |
| Models extend `BaseModel`, use `HasUuids` | `ModelLayerArchTest` |
| Actions have `execute()`, end with `Action` | `ActionLayerArchTest` |
| Policies extend `BasePolicy` | `PolicyLayerArchTest` |
| Exceptions extend `AppException` | `ExceptionLayerArchTest` |
| Core contracts are interfaces | `ContractsArchTest` |
