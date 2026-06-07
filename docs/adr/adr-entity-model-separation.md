# ADR-004: Entity-Model Separation

> Last updated: 2026-06-01 Changes: feat: relaxed framework dependency constraints for development
> speed

## Status

Accepted (Revised 2026-06-01)

## Context

Eloquent models in Laravel mix persistence (database queries, relationships, scopes) with business
logic (validation rules, status checks, permission gating). This coupling makes business logic
harder to test in isolation and tightly binds module rules to the ORM.

For example, checking whether a registration can be approved requires:

```php
// Bad: business logic inside an Eloquent model
$registration->status === 'pending' && $registration->placement_id !== null;
```

This logic can only be tested with a database, a model factory, and all the associated setup. It is
also tightly coupled to the database schema — renaming the `placement_id` column breaks business
logic.

However, in practice, the development velocity cost of strict framework isolation outweighs the
long-term testability benefits for this project's team size and scope. A pragmatic balance is
preferred.

## Decision

Business rules live in dedicated **Entity** classes that are:

- `final readonly` — immutable after construction. An entity represents a snapshot of state at a
  point in time; it never mutates.
- **Framework dependencies allowed** — entities may use Eloquent models, Carbon, and other framework
  classes where practical. The priority is testability, not purity.
- **Testable by simple construction** — `new RegistrationState(status: 'pending', ...)`. No
  database, no mocking, no setup.
- **Bridged from persistence** via `fromModel(Model): static` — extracts data from an Eloquent model
  and constructs the entity.

Models expose entities via named accessors like `asRegistrationState()`, `asInternshipPeriod()`.
This makes the boundary explicit: models handle persistence, entities handle business rules.

### Shared Validation Rules (Gradual Adoption)

Entities can also expose validation rules that are shared between Form Objects and Form Requests:

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

| Aspect         | Entity (`BaseEntity`)         | DTO (`Data`)                             |
| -------------- | ----------------------------- | ---------------------------------------- |
| Purpose        | Business rules, state queries | Data transfer, input/output contracts    |
| Framework deps | Pragmatic — allowed           | Pragmatic — allowed                      |
| Mutation       | Never                         | Never                                    |
| Used by        | Actions, Policies, Livewire   | Actions (input), Livewire (form mapping) |
| fromModel      | Yes — bridge from persistence | Optional — can be constructed from array |

## Consequences

- **Positive**: Entity tests need minimal setup — construct and assert. They run in milliseconds.
- **Positive**: Business rules are isolated from raw database access patterns. Renaming a column
  affects only the `fromModel()` bridge.
- **Positive**: Framework dependencies are allowed when practical — no artificially enforced purity
  that slows development.
- **Negative**: Bridge code (`fromModel()`) must be maintained alongside model changes.

## References

- `app/Core/Entities/BaseEntity.php` — base entity class
- `app/Core/Contracts/StatusEnum.php` — state transition contract
- `app/Academics/School/Entities/SchoolEntity.php` — example entity backed by settings
- `app/Program/Internship/Entities/InternshipPeriod.php` — example model-backed entity
- `app/Program/Internship/Entities/InternshipState.php` — example state entity
- `app/Core/Data/BaseData.php` — DTO base class (complementary, not competing)
- `app/Setup/Installation/Data/SetupTokenData.php` — example DTO
- `docs/architecture.md` — Validation Strategy section
- `docs/conventions.md` — Section 7 (Entities) and Section 11 (Data/DTOs)
