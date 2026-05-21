# ADR-001: UUID Primary Keys

## Status
Accepted

## Context
Every database table in the system needs a primary key. The default choice in Laravel is
auto-incrementing integers (unsigned bigint). However, integer IDs have several drawbacks for
this application:

- **Enumeration attacks**: Sequential IDs (`/users/1`, `/users/2`) leak information about
  system scale and make automated scraping trivial.
- **Distributed collisions**: If the system ever needs sharding, multi-region deployment, or
  offline-capable clients, integer auto-increment creates collision risk without a central
  sequence generator.
- **Foreign key consistency**: With 50+ tables referencing each other, mixing integer PKs
  across domains would require coordination. UUIDs make every foreign key universally unique.
- **Merge conflicts**: If two environments (staging, production) need data merged, integer IDs
  almost certainly collide.

Alternatives considered:
1. **Auto-increment bigint**: Simple, fast, Laravel default — but vulnerable to enumeration
   and unscalable across distributed environments.
2. **ULID**: Sortable, URL-safe, more compact than UUID — but requires a custom implementation
   and community packages; less ecosystem support.
3. **Snowflake ID**: Twitter-style timestamp-based IDs — requires a worker ID assignment
   mechanism and custom implementation.

## Decision
All models extend `BaseModel` which applies `HasUuids` trait, sets `$incrementing = false`,
and configures `$keyType = 'string'`. The single exception is the `User` model, which extends
`Authenticatable` directly (required for password hashing, remember tokens, and email
verification) but still uses the `HasUuids` trait manually.

## Consequences
- **Positive**: IDs are globally unique, safe from enumeration, merge-friendly, and consistent
  across all 50+ tables.
- **Positive**: No central sequence needed — any replica can generate IDs independently.
- **Positive**: UUIDs in URLs (`/users/{uuid}`) don't leak system information.
- **Negative**: UUIDs are bulkier (36 characters) than integers (8 bytes), increasing index
  size and slightly slowing B-tree lookups.
- **Negative**: Debugging is less convenient — copying a UUID from logs is harder than a
  small integer.
- **Negative**: JOINs are marginally slower than integer keys due to string comparison
  overhead. Mitigated by proper indexing.

## References
- `app/Domain/Core/Models/BaseModel.php`
- `docs/en/conventions.md` — Section 4 (Models)
- `docs/en/database.md` — UUID Primary Keys section
