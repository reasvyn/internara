# Database
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Design Philosophy

The database is organized around the concept that every piece of persistent
state belongs to a domain. Tables are not flat or arbitrary; they are grouped
into five conceptual categories that mirror the application's architecture:
core, operational, assessment, security, and supporting. This structure makes
it obvious where data lives and how it relates.

## UUID Primary Keys

Every business model uses a UUID primary key instead of an auto-incrementing
integer. This choice is deliberate: UUIDs are globally unique without a central
sequence, which makes database merging, seeding across environments, and
distributed deployment safe. There is no risk of ID collision when restoring a
backup alongside production data, or when records are created offline and
synced later. UUIDs also prevent information leakage — unlike sequential IDs,
they reveal nothing about the total number of records or the order of creation.

The trade-off is larger index size and slightly slower joins compared to
integers, but at this application's scale the benefits of distribution safety
and information hiding outweigh the costs. All foreign key columns use UUIDs
to match their parent primary keys, and every foreign key is explicitly
indexed to mitigate the performance difference.

Models extending the base `BaseModel` class automatically gain UUID support.
The `User` model applies it manually since it extends Laravel's
`Authenticatable` directly. UUID generation is handled by Laravel's built-in
`HasUuids` trait using ordered UUIDs, which keeps B-tree index insertion
efficient.

## SQLite as Default

The default database driver is SQLite. This was chosen because it requires
zero configuration — no server process, no credentials, no port management.
A single file is all that is needed. For development and single-server
deployments, this eliminates operational friction. Testing also uses SQLite
(in-memory mode), which makes the test suite fast and self-contained.

The trade-off is that SQLite has limited concurrent write capacity and does not
support all SQL features. For production deployments with multiple concurrent
users, MySQL 8+, MariaDB, or PostgreSQL 14+ is recommended. The application
abstracts database access through Eloquent, so switching drivers requires
changing only the environment variable.

## Key Table Categories

Core tables are the foundation: users, profiles, schools, departments, and
academic years. These define who the participants are and what organizational
structure they belong to.

Operational tables track the primary workflows: internships, placements,
registrations, attendances, logbooks, assignments, and submissions. These
tables record what happens during the internship lifecycle.

Assessment tables handle evaluation: rubrics, competencies, indicators,
assessments, evaluations, presentations, and reports. These are separated
because evaluation has its own data lifecycle and access patterns distinct
from operational tracking.

Security tables manage access and auditing: roles, permissions,
activity_log, login_history, sessions, and account status history. Every
mutating action is logged immutably, producing an audit trail that can be
traced back to a specific user and request.

Supporting tables enable the application to function: settings (key-value
configuration), media (file attachments), notifications, and setups
(installation state). These are consumed by the framework and infrastructure
rather than by business workflows directly.

## Schema Organization Principles

All migrations live in a flat `database/migrations/` directory with
chronological timestamp prefixes. This ensures consistent execution order
across environments. Migration filenames indicate which table they create
(e.g., `2026_04_29_092750_create_users_table.php`).

Each domain manages its own schema concerns through distinct migration files,
but all files coexist in the same directory. The domain ownership is evident
from the table name, not from a directory structure.

Foreign key delete behaviors follow a simple rule: cascade when the child
record cannot exist without the parent, nullify when the relationship is
optional, and restrict when deletion should be prevented. Composite indexes
are created for the specific query patterns each domain uses, not
speculatively for every column combination.

Factories and seeders mirror this domain structure. Each model has a
factory, and seeders are idempotent — they can be run multiple times without
duplicating data. The seeding order respects domain dependencies: school
data before user data, permissions before role assignments, internships
before registrations.

## Where to Find It

All migrations live in `database/migrations/` with chronological prefixes.
Factories are in `database/factories/`, seeders in `database/seeders/`.
The base model class is in `app/Domain/Core/Models/BaseModel.php`.
Database configuration is in `config/database.php`, overridable via `.env`.

## Full Table Reference

The database schema covers 75+ tables organized into lifecycle groups:
Identity & Access, Institutional Setup, Partnerships, Internship Program,
Registration, Daily Operations (attendance, logbook), Mentoring, Assignments,
Assessment, Reports, Guidance & Incidents, Evaluations, Admin & Audit, and
Infrastructure (cache, queue, sessions, media, notifications, activity log).

Refer to individual domain documentation for table details and relationships.

---

## Engine Comparison

| Feature | SQLite | MySQL 8+ | MariaDB 10.6+ | PostgreSQL 14+ |
|---|---|---|---|---|
| **Setup** | Zero — file-based | Server install | Server install | Server install |
| **Concurrent writes** | ❌ Locks entire file | ✅ Row-level locking | ✅ Row-level locking | ✅ MVCC |
| **Connection pooling** | Not needed | ProxySQL | ProxySQL | PgBouncer |
| **Read replicas** | Not supported | Supported | Supported | Supported |
| **Default for** | Development, testing | Production | Production | Production |

### Known SQLite vs MySQL/PostgreSQL Differences

| Difference | Impact |
|---|---|
| SQLite does not enforce column length | A `varchar(255)` column accepts longer values in SQLite, but MySQL truncates. Ensure data fits constraints. |
| SQLite has no native `ENUM` type | Enum columns are stored as `text` with check constraints. Migrations abstract this difference. |
| SQLite locks on write | Under concurrent write load, "database is locked" errors occur. Switch to MySQL/PG in production. |
| SQLite `ALTER TABLE` is limited | Some schema changes require table recreation. |

### Connection Pooling

For high-traffic deployments:
- **MySQL**: ProxySQL or configure `max_connections` appropriately
- **PostgreSQL**: PgBondary for transaction-mode pooling

Laravel supports read/write separation for replicas:

```php
// config/database.php
'read' => ['host' => ['192.168.1.1']],
'write' => ['host' => ['192.168.1.2']],
```

### Index Strategy

Internara uses composite indexes for common query patterns:

| Pattern | Index Example |
|---|---|
| FK + status filter | `[registration_id, status]` on `attendances` |
| User + date lookup | `[user_id, date]` on `logbooks` and `attendances` |
| Polymorphic lookup | `[subject_type, subject_id]` on `activity_log` |

### Migration Strategy

After switching the database connection, run migrations to create the schema:

```bash
php artisan migrate
```

When migrating from SQLite to MySQL with existing data, use Laravel's
dump and schema sync tools.

## Infrastructure Context

| Tier | Engine | Connection Pooling | Read Replicas |
|---|---|---|---|
| 1 (Shared) | MySQL / MariaDB (shared) | Not needed | Not supported |
| 2 (VPS) | MySQL 8+ / PostgreSQL 14+ | Optional (ProxySQL / PgBouncer) | Optional |
| 3 (HA) | MySQL 8+ / PostgreSQL 14+ | ✅ Required | ✅ Recommended |

See [Infrastructure → Database Strategy](infrastructure.md#4-database-strategy) for detailed
tier-based database configuration, connection pooling setup, and replication strategy.
