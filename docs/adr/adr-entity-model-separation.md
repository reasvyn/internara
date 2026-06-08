# ADR-004: Entity-Model Separation

> **Status:** Accepted (Revised 2026-06-01)
> **Last updated:** 2026-06-08

## Context

Eloquent models in Laravel mix persistence (database queries, relationships, scopes) with business logic (validation rules, status checks, permission gating). This coupling has two negative effects:

1. **Business logic cannot be tested without a database** — every test requires factories, migrations, and database setup, making tests slow and brittle.
2. **Schema changes ripple through business logic** — renaming a column breaks inline rules scattered across Models, Actions, and Controllers.

However, strict framework isolation (banning all Eloquent usage from business logic) imposes a development velocity cost that outweighs the benefits for this project's team size and scope. A pragmatic balance is needed.

## Decision

Business rules live in dedicated **Entity** classes that are `final readonly` (immutable after construction — they represent a snapshot of state at a point in time) and **allow framework dependencies** (Eloquent models, Carbon, and other framework classes where practical). The priority is testability, not purity.

Entities are bridged from persistence via `fromModel(Model): static`, which extracts data from an Eloquent model and constructs the entity. Models expose entities via named accessors like `asRegistrationState()`, `asInternshipPeriod()`.

### Relationship to DTOs

| Aspect | Entity (BaseEntity) | DTO (BaseData) |
|---|---|---|
| Purpose | Business rules, state queries | Data transfer, input/output contracts |
| Mutation | Never | Never |
| Framework deps | Pragmatic — allowed | Pragmatic — allowed |
| fromModel | Yes — persistence bridge | Optional |
| Used by | Actions, Policies, Livewire | Actions (input), Livewire (form mapping) |

### Shared Validation Rules

Entities may expose static `rules()` methods returning validation rules shared between Form Objects and Form Requests, eliminating duplicate validation logic across UI layers:

```php
final readonly class InternshipPeriod extends BaseEntity
{
    public static function rules(?string $excludeId = null): array { ... }
}
```

## Consequences

- **Positive**: Entity tests need minimal setup — construct and assert. They run in milliseconds without a database.
- **Positive**: Business rules are isolated from raw database access patterns. Renaming a column only affects the `fromModel()` bridge.
- **Positive**: Framework dependencies are allowed when practical — no artificially enforced purity that slows development.
- **Negative**: Bridge code (`fromModel()`) must be maintained alongside model changes, adding a maintenance surface.

## References

- `app/Core/Entities/BaseEntity.php` — Base entity class
- `app/Core/Data/BaseData.php` — DTO base class (complementary)
- `app/Core/Contracts/StatusEnum.php` — State transition contract
- `docs/architecture.md` — Validation Strategy section
- `docs/conventions.md` — Entities section
