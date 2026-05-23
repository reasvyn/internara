# ADR-008: Base Class Mandate

## Status
Accepted

## Context
In a 24-domain, 465-file codebase with 10+ architectural layers, consistency is not optional.
Every developer writing a new model, action, or policy must make the same structural decisions
— or the codebase drifts into an inconsistent state where some models extend `BaseModel`,
others extend `Model`, and some use custom logging while others use `Log::` facade.

Without enforcement, inconsistency accumulates silently:
- A model without UUID breaks foreign key assumptions in 18 other domains
- A policy without role checks allows unauthorized access
- An action without transaction wrapping leaves partial database writes on failure

Architecture tests catch these violations, but they should not need to — the conventions
should be impossible to bypass.

## Decision
Every architectural layer has exactly one base class from Core:

| Layer | Base Class | Why |
|---|---|---|
| Model | `BaseModel` | UUID PK, `HasUuids`, non-incrementing, string key |
| Action | `BaseAction` | Transaction wrapping, dual-channel logging, error handling |
| Entity | `BaseEntity` | `final readonly`, `fromModel()` bridge contract |
| Policy | `BasePolicy` | Role authorization (`AuthorizesRoles`) + ownership (`AuthorizesOwnership`) |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions |
| Controller | `BaseController` | Cross-cutting HTTP concerns |
| Form Request | `FormRequest` | Consistent validation error handling |
| State | `BaseState` | State machine lifecycle with Spatie integration |
| Exception | `AppException` / `DomainException` | Structured exception hierarchy with context |
| Enum | `LabelEnum` / `StatusEnum` | Consistent label and transition interfaces |

Architecture tests (`tests/Arch/`) enforce each rule — violations fail CI.

## Consequences
- **Positive**: Every class in a given layer behaves identically — UUID keys, transactional
  actions, authorized policies. Predictability across 465 files.
- **Positive**: Architecture tests make drift impossible. Adding a new model that extends
  `Model` instead of `BaseModel` produces a test failure.
- **Positive**: Cross-cutting changes (e.g., adding a new feature to `BaseAction`) apply to
  all 150 actions automatically.
- **Positive**: New developers can look at any existing domain file and know the structure —
  every model, action, and policy follows the same pattern.
- **Negative**: The User model cannot extend `BaseModel` — it must extend `Authenticatable`
  for authentication features. This exception is documented and explicitly tested.
- **Negative**: Simple/one-off classes must still extend the base, adding minimal overhead
  (e.g., a simple policy with one method still extends `BasePolicy`).
- **Negative**: Changing a base class affects all consuming classes — requires careful testing.

## References
- `docs/architecture.md` — Base Class Mandate section
- `docs/conventions.md` — Section 0 (Mandatory Base Classes)
- `tests/Arch/ModelLayerArchTest.php`
- `tests/Arch/ActionLayerArchTest.php`
- `tests/Arch/PolicyLayerArchTest.php`
- `tests/Arch/EntityLayerArchTest.php`
- `tests/Arch/EnumLayerArchTest.php`
- `tests/Arch/ExceptionLayerArchTest.php`
