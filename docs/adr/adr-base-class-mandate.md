# Base Class Mandate
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Status
Accepted

## Context

In a 23-module codebase with 10+ architectural layers and 160+ Actions across 50+ models,
consistency is not optional. Every developer writing a new model, action, or policy must make
the same structural decisions — or the codebase drifts into an inconsistent state where some
models extend `BaseModel`, others extend `Model` directly, and some actions use `SmartLogger`
while others use `Log::` facade.

Without enforcement, inconsistency accumulates silently:

- A model without a UUID primary key breaks foreign key assumptions across 75+ tables
- A policy without role checks allows unauthorized access
- An action without transaction wrapping leaves partial database writes on failure
- A cache key defined as a string literal instead of a `CacheKeys` constant makes systematic
  cache invalidation impossible

Architecture tests previously caught these violations but were removed due to a
`pest-plugin-arch` compatibility bug. Until they are restored, enforcement relies on code
review and PHPStan.

## Decision

Every architectural layer has exactly one base class from Core. There is no alternative.

| Layer | Base Class | Provides | Enforced By |
|---|---|---|---|
| Model | `BaseModel` | UUID PK (`HasUuids`), non-incrementing, string key type | extends check |
| Action (Command) | `BaseAction` | `transaction()`, `log()`, `HandlesActionErrors` | extends check |
| Action (Read) | None required | Not needed — reads don't need transaction/log | — |
| Action (Process) | `BaseAction` | Same as Command — orchestration with tx + log | extends check |
| Entity | `BaseEntity` | `final readonly`, `fromModel(Model): static` | `final readonly` + extends |
| State | `BaseEntity` | State-machine helpers defined per entity | — |
| Policy | `BasePolicy` | `AuthorizesRoles` + `AuthorizesOwnership` traits | extends check |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions | extends check |
| Livewire simple | `Component` (Livewire's) | Standard Livewire component | — |
| Controller | `BaseController` | Cross-cutting HTTP concerns | extends check |
| Form Request | `FormRequest` (Core's, not Laravel's) | Consistent `ValidationFailedException` | extends check |
| Enum | Implements `LabelEnum` | `label(): string` method | implements check |
| Status Enum | Implements `StatusEnum` (+ LabelEnum) | `canTransitionTo()`, `validTransitions()`, `isTerminal()` | implements check |
| Exception | Extends `AppException` or `DomainException` | `HasExceptionContext` trait | extends check |
| Cache key | `CacheKeys` constant | Centralized key registry, collision prevention | constant reference |

### Exceptions

The `User` model cannot extend `BaseModel` because it must extend Laravel's `Authenticatable`
for authentication features (password hashing, remember tokens, email verification). It
manually applies `HasUuids` and overrides `getIncrementing()` and `getKeyType()` to
maintain UUID consistency.

### Enforcement Gap

Architecture tests that previously enforced these rules were removed due to a
`pest-plugin-arch` compatibility bug. The following layers had dedicated tests:

- `ModelLayerArchTest` — models extend BaseModel (or Authenticatable for User)
- `ActionLayerArchTest` — Command/Process actions extend BaseAction
- `PolicyLayerArchTest` — policies extend BasePolicy
- `EntityLayerArchTest` — entities extend BaseEntity
- `EnumLayerArchTest` — enums implement LabelEnum
- `ExceptionLayerArchTest` — exceptions extend AppException or DomainException

Until these tests are restored, PHPStan custom rules and code review serve as the enforcement
mechanism. Violations are considered blocking in code review.

## Consequences

- **Positive**: Every class in a given layer behaves identically — UUID keys, transactional
  actions, authorized policies. Predictability across 465+ files.
- **Positive**: Cross-cutting changes (e.g., adding a new feature to `BaseAction`) apply to
  all 150+ actions automatically.
- **Positive**: New developers can look at any existing module file and know the structure —
  every model, action, and policy follows the same pattern.
- **Positive**: Cache keys are declared in one place — discovering what cache keys exist and
  what invalidates them requires reading one file.
- **Negative**: The User model cannot extend `BaseModel` due to Laravel's authentication
  requirements. This exception is documented and explicitly tested.
- **Negative**: Simple or one-off classes must still extend the base, adding minimal overhead
  (e.g., a simple policy with one method still extends `BasePolicy`).
- **Negative**: Changing a base class affects all consuming classes — requires careful testing
  and impact analysis.
- **Negative**: Without architecture tests, enforcement is manual and inconsistent. PHPStan
  custom rules can partially bridge this gap.

## References

- `app/Core/Models/BaseModel.php` — base model with UUID
- `app/Core/Actions/BaseAction.php` — base action with tx + log
- `app/Core/Entities/BaseEntity.php` — base entity (final readonly)
- `app/Core/Policies/BasePolicy.php` — base policy with role/ownership traits
- `app/Core/Livewire/BaseRecordManager.php` — base CRUD component
- `app/Core/Http/Controllers/BaseController.php` — base controller
- `app/Core/Http/Requests/FormRequest.php` — base form request
- `app/Core/Contracts/LabelEnum.php` — enum contract
- `app/Core/Contracts/StatusEnum.php` — status enum contract
- `app/Core/Support/CacheKeys.php` — cache key registry
- `docs/architecture.md` — Base Class Mandate section
- `docs/conventions.md` — Section 0 (Mandatory Base Classes)
