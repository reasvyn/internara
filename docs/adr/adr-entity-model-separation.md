# ADR-004: Entity-Model Separation

## Status
Accepted

## Context

Eloquent models in Laravel mix persistence (database queries, relationships, scopes) with
business logic (validation rules, status checks, permission gating). This coupling makes
business logic untestable without a database connection and tightly binds domain rules to the
ORM.

For example, checking whether a registration can be approved requires:

```php
// Bad: business logic inside an Eloquent model
$registration->status === 'pending' && $registration->placement_id !== null
```

This logic can only be tested with a database, a model factory, and all the associated setup.
It is also tightly coupled to the database schema — renaming the `placement_id` column breaks
business logic.

Three alternatives were considered:

1. **Put logic in the model**: Simplest, Laravel convention — but couples business rules to
   the ORM, making tests slow and refactoring risky. Every test needs a database.
2. **Put logic in Actions**: Actions would contain all business rules as private methods —
   but this leads to code duplication when multiple actions need the same check, and prevents
   reuse outside action contexts.
3. **Entities with BaseState**: A dedicated class hierarchy for business rules, with a
   separate `BaseState` subclass for state-machine logic (status transitions, lifecycle
   guards). This is the selected approach.

## Decision

Business rules live in dedicated **Entity** classes that are:

- `final readonly` — immutable after construction. An entity represents a snapshot of state
  at a point in time; it never mutates.
- **Zero framework dependencies** — no Eloquent, no Facades, no Container. This guarantees
  entities can be instantiated and tested in pure PHP.
- **Testable by simple construction** — `new RegistrationState(status: 'pending', ...)`. No
  database, no mocking, no setup.
- **Bridged from persistence** via `fromModel(Model): static` — the single connection to the
  ORM. This method extracts data from an Eloquent model and constructs the entity.

Models expose entities via named accessors like `asRegistrationState()`, `asInternshipPeriod()`.
This makes the boundary explicit: models handle persistence, entities handle business rules.

### State Machine Entities (BaseState)

For business processes with explicit lifecycles (registration status, internship status,
account status), entities extend `BaseState` instead of `BaseEntity`:

```php
abstract readonly class BaseState extends BaseEntity
{
    public function isState(string $state): bool
    {
        return property_exists($this, 'status') && $this->status === $state;
    }

    public function isStateIn(array $states): bool
    {
        return property_exists($this, 'status') && in_array($this->status, $states, true);
    }
}
```

State transitions are validated by the enum's `StatusEnum` contract (`canTransitionTo()`,
`validTransitions()`, `isTerminal()`), not by the entity. The entity provides the current
state snapshot; the enum defines allowed transitions. This separation keeps entity logic
focused on business rules rather than lifecycle mechanics.

### Shared Validation Rules (Gradual Adoption)

Entities can also expose validation rules that are shared between Form Objects and Form
Requests:

```php
final readonly class InternshipPeriod extends BaseEntity
{
    public static function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
```

Both Form Objects and Form Requests reference the same `Entity::rules()` method, eliminating
duplicate validation logic across UI layers.

### Relationship to Data DTOs

Entities are not the only readonly data class in the system. The distinction:

| Aspect | Entity (`BaseEntity`) | DTO (`Data`) |
|---|---|---|
| Purpose | Business rules, state queries | Data transfer, input/output contracts |
| Framework deps | Zero (except `fromModel` bridge) | Zero |
| Mutation | Never | Never |
| Used by | Actions, Policies, Livewire | Actions (input), Livewire (form mapping) |
| fromModel | Yes — bridge from persistence | Optional — can be constructed from array |

Both are `final readonly`. Entities carry business logic methods; DTOs are pure data carriers.

## Consequences

- **Positive**: Entity tests need zero setup — construct and assert. They run in milliseconds,
  not seconds. The entire entity test suite completes faster than a single database-dependent test.
- **Positive**: Business rules are decoupled from database schema. Renaming a column affects
  only the `fromModel()` bridge, not the entity's logic.
- **Positive**: Entities can be used anywhere — Actions, Livewire components, controllers,
  tests — without database access. This enables policy checks and validation without
  querying the database.
- **Positive**: `BaseState` provides a lightweight state-machine helper without requiring a
  separate pattern. Combined with `StatusEnum`, it covers all state management needs.
- **Positive**: Entities can serve as the source of truth for validation rules, eliminating
  duplication between Form Objects and Form Requests.
- **Negative**: Bridge code (`fromModel()`) must be maintained alongside model changes.
  Adding a column to a model requires adding it to the entity and its `fromModel()` method.
- **Negative**: Some developers may find the indirection unfamiliar — "why is this logic not
  on the model?" The answer is testability and framework independence.
- **Negative**: Not every domain needs entities — only domains with meaningful business rules
  should create them (approximately 25 entities across 24 domains is appropriate).

## References

- `app/Domain/Core/Entities/BaseEntity.php` — base entity class
- `app/Domain/Core/States/BaseState.php` — state machine entity base
- `app/Domain/Core/Contracts/StatusEnum.php` — state transition contract
- `app/Domain/Internship/Entities/InternshipPeriod.php` — example entity
- `app/Domain/Registration/Entities/RegistrationState.php` — example state entity
- `app/Domain/Core/Data/Data.php` — DTO base class (complementary, not competing)
- `docs/architecture.md` — Validation Strategy section
- `docs/conventions.md` — Section 6 (Entities)
