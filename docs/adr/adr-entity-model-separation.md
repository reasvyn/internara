# Entity-Model Separation

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Entity Pattern](../guides/arch/entity-pattern.md) and [Architecture Overview](../architecture.md) § Data Layer |

## Context and Problem Statement

Eloquent models mix persistence (queries, relationships, scopes) with business logic (validation,
status checks, permission gating). This coupling causes two pains: business logic cannot be tested
without a database (factories, migrations, setup — slow and brittle), and schema changes ripple
through inline rules scattered across Models, Actions, and Controllers. Strict isolation (banning
all framework usage from business logic) would trade one cost for another — velocity loss
disproportionate to team size and scope.

**Decision Drivers:**

* Testability of business rules without database setup
* Containment of schema-change blast radius to a single bridge point
* Pragmatic velocity — isolation that helps without enforcing purity that hinders
* Clear ownership: persistence vs domain logic in distinct, discoverable types

## Considered Options

* **Rules inline in Eloquent models** — business logic lives directly in Model methods.
  *Pros:* fewest files. *Cons:* tests require DB, schema renames scatter, no snapshot semantics.*
* **Strict domain isolation** — ban all framework dependencies from business logic, map through
  anti-corruption layers. *Pros:* pure domain, framework-agnostic. *Cons:* mapping ceremony,
  velocity cost unjustified for 18-module single-tenant system.*
* **Dedicated `final readonly` Entity with pragmatic framework use (chosen)** — extract rules into
  immutable Entity classes bridged via `fromModel(Model): static`, allow framework deps where
  practical. *Pros:* millisecond tests, single bridge point for schema change, no artificial purity.*

## Decision Outcome

**Chosen option: Dedicated `final readonly` Entity with pragmatic framework use** — business rules
live in Entity classes that are `final readonly` (snapshot of state at a point in time) and **allow
framework dependencies** (Eloquent, Carbon) where practical. Testability is prioritized over purity.

Bridging via `fromModel(Model): static` extracts data from a model and constructs the entity;
models expose entities through named accessors (`asRegistrationState()`, `asInternshipPeriod()`).

**Relationship to DTOs:**

| Aspect | Entity (BaseEntity) | DTO (BaseData) |
|--------|---------------------|----------------|
| Purpose | Business rules, state queries | Data transfer, input/output contracts |
| Mutation | Never | Never |
| Framework deps | Pragmatic — allowed | Pragmatic — allowed |
| fromModel | Yes — persistence bridge | Optional |
| Used by | Actions, Policies, Livewire | Actions (input), Livewire (form mapping) |

**Shared Validation Rules** — Entities may expose `static rules()` returning validation arrays
shared between Form Objects and Form Requests, eliminating duplication across UI layers:

```php
final readonly class InternshipPeriod extends BaseEntity
{
    public static function rules(?string $excludeId = null): array { ... }
}
```

### Positive Consequences

* Entity tests need minimal setup — construct and assert in milliseconds, no database
* Schema renames affect only the `fromModel()` bridge
* Framework deps allowed where practical — no purity tax on iteration speed

### Negative Consequences

* Bridge code must be maintained alongside model changes — adds a surface that can drift if
  neglected; mitigated by co-locating Entity and Model within the same module

## Links

* [Entity Pattern](../guides/arch/entity-pattern.md) — `final readonly` contract and bridge conventions
* [Data Pattern](../guides/arch/data-pattern.md) — DTO vs Entity responsibilities
* [Architecture Overview](../architecture.md) — layer responsibilities and validation strategy
* [Conventions — Entities](../conventions.md) — team-wide entity authoring rules
