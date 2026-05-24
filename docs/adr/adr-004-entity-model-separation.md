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

Two alternatives were considered:
1. **Put logic in the model**: Simplest, Laravel convention — but couples business rules to
   the ORM, making tests slow and refactoring risky.
2. **Put logic in Actions**: Actions would contain all business rules as private methods —
   but this leads to code duplication when multiple actions need the same check, and prevents
   reuse outside action contexts.

## Decision
Business rules live in dedicated **Entity** classes that are:
- `final readonly` — immutable after construction
- Zero framework dependencies — no Eloquent, no Facades, no Container
- Testable by simple construction — `new RegistrationState(status: 'pending', ...)`
- Bridged from persistence via `fromModel(Model): static` — the single connection to the ORM

Models expose entities via named accessors: `asRegistrationState()`, `asInternshipPeriod()`.
This makes the boundary explicit: models handle persistence, entities handle business rules.

## Consequences
- **Positive**: Entity tests need zero setup — construct and assert. They run in milliseconds,
  not seconds.
- **Positive**: Business rules are decoupled from database schema. Renaming a column affects
  only the `fromModel()` bridge, not the entity's logic.
- **Positive**: Entities can be used anywhere — Actions, Livewire components, controllers,
  tests — without database access.
- **Negative**: Bridge code (`fromModel()`) must be maintained alongside model changes.
- **Negative**: Some developers may find the indirection unfamiliar — "why is this logic not
  on the model?"
- **Negative**: Not every domain needs entities — only domains with meaningful business rules
  should create them (25 entities across 24 domains is appropriate).

## References
- `app/Domain/Core/Entities/BaseEntity.php`
- `docs/conventions.md` — Section 6 (Entities)
- ~~`tests/Arch/EntityLayerArchTest.php`~~ (removed)
- Example: `app/Domain/Internship/Entities/InternshipPeriod.php`
- Example: `app/Domain/Registration/Entities/RegistrationState.php`
