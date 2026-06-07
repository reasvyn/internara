# ADR-001: UUID Primary Keys

> Last updated: 2026-05-27 Changes: docs: comprehensive infrastructure, architecture, and
> conventions overhaul

## Status

Accepted

## Context

Every database table in the system needs a primary key. The default choice in Laravel is
auto-incrementing integers (unsigned bigint). However, integer IDs have several drawbacks for this
application:

- **Enumeration attacks**: Sequential IDs (`/users/1`, `/users/2`) leak information about system
  scale and make automated scraping trivial. This is especially relevant for an educational system
  where student records must be protected.
- **Distributed collisions**: If the system ever needs sharding, multi-region deployment, or
  offline-capable clients, integer auto-increment creates collision risk without a central sequence
  generator. Schools operating on unreliable networks may need offline-capable deployments.
- **Foreign key consistency**: With 75+ tables referencing each other, mixing integer PKs across
  modules would require coordination. UUIDs make every foreign key universally unique without a
  central authority.
- **Merge conflicts**: If two environments (staging, production) need data merged, integer IDs
  almost certainly collide. Schools running pilot programs in staging alongside production risk ID
  conflicts on merge.

Alternatives considered:

1. **Auto-increment bigint**: Simple, fast, Laravel default — but vulnerable to enumeration and
   unscalable across distributed environments. Laravel's convention encourages this but it does not
   fit the deployment model of self-hosted instances that may need offline sync.
2. **ULID**: Sortable, URL-safe, more compact than UUID — but requires a custom implementation and
   community packages. Less ecosystem support and fewer tool integrations.
3. **UUID v7 (time-ordered)**: Time-sortable UUIDs that keep B-tree indexes efficient — natively
   supported by Laravel's `HasUuids` trait in recent versions. This is the selected approach.

## Decision

All models extend `BaseModel` which applies the `HasUuids` trait (generating ordered UUIDs — UUID v7
— for sequential B-tree insertion efficiency), sets `$incrementing = false`, and configures
`$keyType = 'string'`.

The single exception is the `User` model, which extends `Authenticatable` directly (required for
Laravel's authentication system: password hashing, remember tokens, email verification) but still
applies the `HasUuids` trait manually and overrides `getIncrementing()` and `getKeyType()` to
maintain UUID consistency with all other models.

Foreign keys referencing UUID primary keys use `foreignUuid()->constrained()` in migrations. This is
enforced by code review — no mixed key types are permitted.

## Consequences

- **Positive**: IDs are globally unique, safe from enumeration, merge-friendly, and consistent
  across all 75+ tables.
- **Positive**: No central sequence needed — any replica can generate IDs independently. Ordered
  UUIDs (v7) ensure B-tree insertion is efficient, mitigating the primary performance concern of
  random UUIDs.
- **Positive**: UUIDs in URLs (`/users/{uuid}`) don't leak system information — an attacker cannot
  infer user count or growth rate from ID patterns.
- **Positive**: Consistent key type across the entire database eliminates join-type mismatches that
  plague systems with mixed integer and UUID keys.
- **Negative**: UUIDs are bulkier (36 characters as string, 16 bytes as binary) than integers (8
  bytes), increasing index size. For a school-sized dataset (thousands to low millions of records),
  this is negligible.
- **Negative**: Debugging is less convenient — copying a UUID from logs is harder than a small
  integer. Mitigated by logging shortcuts in `SmartLogger`.
- **Negative**: JOINs are marginally slower than integer keys due to string comparison overhead.
  Mitigated by:
    - Explicit composite indexes on every foreign key column
    - Ordered UUIDs preserving temporal locality
    - School-scale data volumes where index size difference is irrelevant

## References

- `app/Core/Models/BaseModel.php` — base class with HasUuids trait
- `app/User/Models/User.php` — exception pattern (Authenticatable + manual UUID)
- `docs/conventions.md` — Section 4 (Models)
- `docs/infrastructure/database.md` — UUID Primary Keys section
