---
name: laravel-best-practices
description: Apply this skill whenever writing, reviewing, or refactoring any Laravel PHP code including controllers, models, migrations, form requests, policies, jobs, queries, routes, and Blade views. Context-aware of the Module-first Action-based MVC architecture.
---

# Laravel Best Practices Skill

## When to Activate

Apply this skill whenever writing, reviewing, or refactoring any Laravel PHP code — controllers, models, migrations, form requests, policies, jobs, queries, routes, Blade views. Overrides default Laravel conventions where they conflict with the Action-based MVC architecture.

## Key References

- **Architecture**: `docs/architecture.md` — complete architectural foundation
- **Conventions**: `docs/conventions.md` — coding conventions with examples
- **Base classes**: `app/Core/` — Actions, Entities, Policies, Livewire, Models, Http, Data

## Module-First Organization

All code lives under `app/{Module}/` instead of the default flat structure:

| Laravel Default | Internara Convention |
|-----------------|---------------------|
| `app/Models/` | `app/{Module}/{SubModule}/Models/` |
| `app/Http/Controllers/` | `app/{Module}/Http/Controllers/` or submodule |
| `app/Policies/` | `app/{Module}/{SubModule}/Policies/` |
| Route files | `routes/web/{module}.php` |
| Views | `resources/views/{module}/` |

## Mandatory Base Classes

| Layer | Base Class | Provides |
|-------|-----------|----------|
| Model | `BaseModel` | UUID PK via `HasUuids`, non-incrementing |
| Auth Model | `Authenticatable` + `HasUuids` | User model only |
| Command/Process Action | `BaseAction` | `transaction()`, `log()`, `HandlesActionErrors` |
| Read Action | None | Plain class with constructor injection |
| Entity | `BaseEntity` (abstract readonly) | `fromModel(Model): static` contract |
| Policy | `BasePolicy` | `AuthorizesRoles`, `AuthorizesOwnership` traits |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions |
| Form Request | `BaseFormRequest` | Consistent `ValidationFailedException` |
| DTO | `BaseData` (final readonly) | `toArray()`, `fromArray()`, `from()` |
| Event | `BaseEvent` | `Dispatchable`, `eventName()`, `toPayload()` |
| Enum | Implements `LabelEnum` | `label(): string` |
| State enum | Implements `StatusEnum` | `canTransitionTo()`, `isTerminal()` |
| Exception | `AppException` or `ModuleException` | `HasExceptionContext` trait |

## Action-Based MVC

- Controllers and Livewire are thin — handle UI state, delegate to Actions
- One Action = one business operation = one `execute()` method
- Actions validate input, delegate rule checks to Entities, persist in transactions, emit side effects
- Actions must NOT contain inline `canX()` checks — those belong in Entities

## UUID Primary Keys

- Every Model extends `BaseModel` which applies `HasUuids`
- Foreign keys use `foreignUuid()->constrained()` in migrations
- `User` model is the sole exception — extends `Authenticatable` directly, applies `HasUuids` manually

## Enum Conventions

- All enums are `string`-backed
- All implement `LabelEnum` (`label(): string`)
- State machine enums additionally implement `StatusEnum`
- Cases: `UPPER_SNAKE` with lowercase backing value
- Model defaults use `Enum::CASE->value` — never hardcoded strings

## Code Standards

- `declare(strict_types=1)` on every PHP file (except migrations and config)
- Constructor property promotion with `protected readonly`
- Explicit return types on every method
- `__()` for all user-facing strings — never hardcoded
- Array validation rules (not pipe syntax)
- `#[Fillable]` attribute on Models (not `$fillable` property)

## Verification

- Follows module-first structure (check sibling files)?
- Business logic in Actions, not Models or Livewire?
- Business rule checks delegated to Entities?
- UUID primary keys via BaseModel?
- `declare(strict_types=1)` present?
- Translations for user-facing strings?
