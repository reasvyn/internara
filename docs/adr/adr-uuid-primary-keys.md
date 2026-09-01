# UUID Primary Keys

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Architecture Overview](../architecture.md) § Persistence Layer and [Conventions — Models](../conventions.md) |

## Context and Problem Statement

Every table requires a primary key. Laravel defaults to auto-incrementing unsigned big integers,
which create four problems at the scale and sensitivity of this system: enumeration attacks
(`/users/1`, `/users/2` leak scale and enable scraping — student confidentiality is paramount),
distributed collision risk (offline/multi-region deployments need no central sequence), foreign-key
inconsistency across 50+ inter-referencing tables, and inevitable ID collisions when merging
staging and production data.

**Decision Drivers:**

* Prevent enumeration of student and school records
* Support offline/multi-region generation without a central sequence
* Consistent key type across all tables to avoid join mismatches
* Preserve B-tree insertion efficiency under UUIDs

## Considered Options

* **Auto-increment bigint** — simple and fast. *Pros:* smallest index. *Cons:* enumeration-vulnerable,
  requires central sequence, merge collisions.*
* **ULID** — sortable, URL-safe. *Pros:* time-ordered, compact. *Cons:* custom implementation,
  weaker ecosystem support.*
* **UUID v7 (time-ordered, chosen)** — time-sortable UUIDs with B-tree locality. *Pros:*
  enumeration-safe, distributed, natively supported by Laravel's `HasUuids` since Laravel 10;
  ordered generation preserves insertion efficiency. *Cons:* larger than integers (see consequences).*

## Decision Outcome

**Chosen option: UUID v7** — all models use UUID v7 primary keys. `BaseModel` applies `HasUuids`
(ordered UUID v7), sets `$incrementing = false` and `$keyType = 'string'`.

The `User` model is the sole exception — it extends `Authenticatable` (required for auth) but
manually applies `HasUuids` and overrides `getIncrementing()` / `getKeyType()` to preserve UUID
consistency. Foreign keys use `foreignUuid()->constrained()` in every migration with composite
indexes; mixed key types are forbidden and enforced via review.

### Positive Consequences

* Globally unique, enumeration-safe, merge-friendly IDs with consistent type across all tables
* No central sequence — any replica generates independently
* URLs reveal no scale (`/users/{uuid}`)
* Ordered UUIDs preserve B-tree insertion locality

### Negative Consequences

* Larger index footprint (36-char string vs 8-byte integer) — negligible at school-scale
  (thousands to low millions of rows)
* Debugging slightly less convenient (copying UUIDs); mitigated by SmartLogger shortcuts
* Marginally slower string-based JOIN comparisons; mitigated by composite indexes and ordering

## Links

* [Architecture Overview](../architecture.md) — persistence and layer responsibilities
* [Model Pattern](../guides/arch/model-pattern.md) — BaseModel and UUID contract
* [Conventions — Database](../conventions.md) — migration and FK conventions
