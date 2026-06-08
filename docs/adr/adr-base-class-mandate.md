# ADR-006: Base Class Mandate

> **Status:** Accepted
> **Last updated:** 2026-06-08

## Context

In a 20-module codebase with 12 architectural layers and 160+ Actions across 50+ models, consistency is not optional. Without enforcement, drift accumulates silently: a model without UUID keys breaks foreign key assumptions, a policy without role checks allows unauthorized access, and an action without transaction wrapping leaves partial database writes on failure.

Architecture tests previously caught these violations but were removed due to a `pest-plugin-arch` compatibility bug. Until restored, enforcement relies on PHPStan custom rules and code review.

## Decision

Every architectural layer has exactly one base class from Core. There is no alternative. The following table defines the mapping:

| Layer | Base Class | Provides | Enforced By |
|---|---|---|---|
| Model | BaseModel | UUID v7 (HasUuids), non-incrementing, string key type | extends check |
| Action (Command/Process) | BaseAction | transaction(), log(), HandlesActionErrors | extends check |
| Action (Read) | None required | — | — |
| Entity | BaseEntity | final readonly, fromModel bridge | extends + final check |
| Policy | BasePolicy | AuthorizesRoles + AuthorizesOwnership traits | extends check |
| Livewire CRUD | BaseRecordManager | Search, filter, sort, pagination, bulk actions | extends check |
| Controller | BaseController | Cross-cutting HTTP concerns | extends check |
| Form Request | BaseFormRequest (Core's) | Consistent ValidationFailedException | extends check |
| Enum | Implements LabelEnum | label(): string method | implements check |
| Status Enum | Implements StatusEnum + LabelEnum | canTransitionTo(), isTerminal() | implements check |
| Exception | AppException or ModuleException | HasExceptionContext trait | extends check |
| Cache key | CacheKeys constant | Centralized key registry | constant reference |

### Exception

The `User` model cannot extend `BaseModel` — it must extend `Authenticatable` for authentication features. It manually applies `HasUuids` and overrides `getIncrementing()` and `getKeyType()` to maintain UUID consistency. This is the sole exception.

### Enforcement Gap

Until architecture tests are restored (planned when `pest-plugin-arch` stabilizes), PHPStan custom rules and code review serve as enforcement. Violations are considered blocking in code review.

## Consequences

- **Positive**: Every class in a given layer behaves identically — UUID keys, transactional actions, authorized policies. Predictable across 465+ files.
- **Positive**: Cross-cutting changes (e.g., adding a new feature to `BaseAction`) apply to all 150+ actions automatically.
- **Positive**: New developers can look at any module file and know the structure — every model, action, and policy follows the same pattern.
- **Negative**: The User model exception is documented but adds a maintenance burden — it must be kept in sync with BaseModel features.
- **Negative**: Changing a base class affects all consuming classes — requires careful testing and impact analysis.

## References

- `app/Core/Models/BaseModel.php` — Base model with UUID
- `app/Core/Actions/BaseAction.php` — Base action with transaction + log
- `app/Core/Entities/BaseEntity.php` — Base entity (final readonly)
- `app/Core/Policies/BasePolicy.php` — Base policy with role/ownership traits
- `app/Core/Livewire/BaseRecordManager.php` — Base CRUD Livewire component
- `app/Core/Http/Controllers/BaseController.php` — Base controller
- `app/Core/Http/Requests/BaseFormRequest.php` — Base form request
- `app/Core/Contracts/LabelEnum.php` — Enum contract
- `app/Core/Contracts/StatusEnum.php` — Status enum contract
- `app/Core/Support/CacheKeys.php` — Cache key registry
- `docs/architecture.md` — Base Class Mandate section
