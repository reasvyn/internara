# Base Class Mandate

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Architecture Overview](../architecture.md) § Base Class Mandate and [Core Module](../guides/arch/modular-pattern.md) |

## Context and Problem Statement

Across 18 modules, 12 architectural layers, 155+ Actions and 38 models, consistency cannot rely
on discipline alone. Without enforcement, drift accumulates silently: a model without UUID keys
breaks foreign-key assumptions, a policy without role checks allows unauthorized access, and an
Action without transaction wrapping leaves partial writes on failure. Architecture tests that
once caught these violations were removed due to a `pest-plugin-arch` compatibility bug.

**Decision Drivers:**

* Uniform behavior per layer (UUID keys, transactions, authorization, status handling)
* Predictability across 465+ files for onboarding and review
* Cross-cutting change leverage — one base-class change reaches 150+ consumers
* Enforcement that survives the temporary absence of architecture tests

## Considered Options

* **Mandate one base class per layer (chosen)** — every architectural role extends or implements a
  single Core base. *Pros:* uniform contracts, automatic propagation of fixes; *Cons:* broad
  blast radius when a base changes.
* **Opt-in base classes** — provide bases as helpers, adoption voluntary.
  *Pros:* low friction. *Cons:* inconsistent coverage, drift returns, review burden grows.*
* **Interfaces only, no inheritance** — enforce contracts via interfaces/traits alone.
  *Pros:* more flexible. *Cons:* duplicated wiring across layers, no shared implementation reuse.*

## Decision Outcome

**Chosen option: Mandate one base class per layer** — every architectural layer has exactly one
Core base; there is no alternative.

| Layer | Base Class | Provides | Enforced By |
|-------|------------|----------|-------------|
| Model | BaseModel | UUID v7 (HasUuids), non-incrementing string PK | extends check |
| Action (Command/Process) | BaseAction | `transaction()`, `log()`, HandlesActionErrors | extends check |
| Action (Read) | — (none required) | — | — |
| Entity | BaseEntity | `final readonly`, `fromModel` bridge | extends + final |
| Policy | BasePolicy | AuthorizesRoles + AuthorizesOwnership | extends check |
| Livewire CRUD | BaseRecordManager | Search, filter, sort, pagination, bulk actions | extends check |
| Controller | BaseController | Cross-cutting HTTP concerns | extends check |
| Form Request | BaseFormRequest | Consistent ValidationFailedException | extends check |
| Enum | Implements LabelEnum | `label(): string` | implements |
| Status Enum | Implements StatusEnum + LabelEnum | `canTransitionTo()`, `isTerminal()` | implements |
| Exception | AppException or ModuleException | HasExceptionContext | extends check |
| Cache key | `config/cache-keys.php` | Centralized registry | config array |

**Exception:** `User` must extend `Authenticatable`, not `BaseModel`. It manually applies
`HasUuids` and overrides `getIncrementing()` / `getKeyType()` to preserve UUID consistency —
the sole documented exception, kept in sync with BaseModel explicitly.

**Enforcement gap (temporary):** until `pest-plugin-arch` stabilizes, blocking code review enforces the mandate.

### Positive Consequences

* Every class in a layer behaves identically — predictable across the codebase
* Cross-cutting changes (e.g., new capability in `BaseAction`) reach 150+ actions automatically
* New contributors recognize structure immediately

### Negative Consequences

* User-model exception adds maintenance burden — must track BaseModel evolution
* Changing a base class affects all consumers; requires impact analysis and broad test runs

## Links

* [Architecture Overview](../architecture.md) — 4-layer model and mandate rationale
* [Modular Pattern](../guides/arch/modular-pattern.md) — module colocation and base-class usage
* [Actions Guide](../guides/arch/action-pattern.md) — Command/Read/Process triad built on BaseAction
* [Entity Pattern](../guides/arch/entity-pattern.md) — BaseEntity contract for business rules
