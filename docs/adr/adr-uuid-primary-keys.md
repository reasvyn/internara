# ADR-001: UUID Primary Keys

> **Status:** Accepted
> **Last updated:** 2026-06-10

## Context

Every database table requires a primary key. Laravel defaults to auto-incrementing unsigned big integers, which introduce several problems for this application:

- **Enumeration attacks**: Sequential IDs (`/users/1`, `/users/2`) leak system scale and enable automated scraping. Student record confidentiality is paramount in an educational system.
- **Distributed collision risk**: Schools may need offline-capable deployments or multi-region setups. Auto-increment IDs require a central sequence generator, creating a single point of failure.
- **Foreign key inconsistency**: With 50+ tables referencing each other, mixed key types across modules would create join-type mismatches and coordination overhead.
- **Merge conflicts**: Staging and production data merges would cause inevitable ID collisions with integers.

Three alternatives were evaluated:

1. **Auto-increment bigint** — Simple and fast, but vulnerable to enumeration and unscalable across distributed environments.
2. **ULID** — Sortable and URL-safe, but requires custom implementation with weaker ecosystem support.
3. **UUID v7 (time-ordered)** — Time-sortable UUIDs that maintain B-tree insertion efficiency. Natively supported by Laravel's `HasUuids` trait since Laravel 10.

## Decision

All models use **UUID v7** as primary keys. `BaseModel` applies `HasUuids` (which generates ordered UUID v7 for sequential B-tree insertion), sets `$incrementing = false`, and configures `$keyType = 'string'`.

The `User` model is the sole exception — it extends `Authenticatable` directly (required for Laravel's authentication system) but manually applies the `HasUuids` trait and overrides `getIncrementing()` and `getKeyType()` to maintain UUID consistency.

Foreign key columns use `foreignUuid()->constrained()` in every migration. Composite indexes are added on every foreign key column. No mixed key types are permitted — enforced through code review and PHPStan.

## Consequences

- **Positive**: Globally unique IDs are enumeration-safe, merge-friendly, and consistent across all tables.
- **Positive**: No central sequence needed — any replica generates IDs independently. UUID v7 ordering preserves B-tree insertion locality.
- **Positive**: URLs contain no sequential information — `/users/{uuid}` reveals nothing about user count or growth rate.
- **Positive**: Consistent key type eliminates join-type mismatches across the entire database schema.
- **Negative**: UUIDs are bulkier (36 characters as string) than integers (8 bytes), increasing index size. At school-scale data volumes (thousands to low millions of records), this is negligible.
- **Negative**: Debugging is slightly less convenient — copying a UUID from logs is harder than a small integer. Mitigated by SmartLogger shortcuts.
- **Negative**: JOIN comparisons are marginally slower due to string comparison. Mitigated by composite indexes and ordered UUIDs.

## References

- `app/Core/Models/BaseModel.php` — Base class with HasUuids trait
- `app/User/Models/User.php` — Exception pattern (Authenticatable + manual UUID)
- `docs/architecture.md` — Persistence Layer section
- `docs/conventions.md` — Models section
