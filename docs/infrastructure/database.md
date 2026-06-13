# Database

> **Last updated:** 2026-06-13

## Design Philosophy

The database is organized around the concept that every piece of persistent state belongs to a module. Tables are grouped into five conceptual categories: core, operational, assessment, security, and supporting. This structure makes it obvious where data lives and how it relates.

The schema encompasses over 40 tables (from 41 migration files, including both domain-specific tables and framework/package tables such as cache, sessions, jobs, media, notifications, and activity logs).

---

## UUID Primary Keys

Every business model uses a UUID v7 primary key instead of an auto-incrementing integer. UUIDs are globally unique without a central sequence, making database merging, seeding across environments, and distributed deployment safe. UUIDs also prevent information leakage — unlike sequential IDs, they reveal nothing about the total number of records or the order of creation.

All foreign key columns use UUIDs to match their parent primary keys, and every foreign key is explicitly indexed. Models extending `BaseModel` automatically gain UUID support via Laravel's `HasUuids` trait. The `User` model applies it manually since it extends `Authenticatable` directly.

---

## SQLite as Default

The default database driver is SQLite. It requires zero configuration — no server process, no credentials, no port management. For development and testing, this eliminates operational friction. Testing also uses SQLite (in-memory mode), which makes the test suite fast and self-contained.

SQLite is intended for development and testing only. In shared hosting production, use MySQL or MariaDB provided by your hosting service. Scale to PostgreSQL when exceeding 500 registered users per PKL period. The application abstracts database access through Eloquent, so switching drivers requires changing only the environment variable.

---

## Key Table Categories

**Core tables** are the foundation: `users`, `profiles`, `departments`, and `academic_years`. These define who the participants are and what organizational structure they belong to.

**Operational tables** track the primary workflows: `internships`, `placements`, `registrations`, `attendances`, `logbooks`, `supervision_logs`, `assignments`, and `submissions`. These record what happens during the internship lifecycle.

**Assessment & Certification tables** handle evaluation and credentials: `rubrics`, `assessments`, `evaluations`, `reports`, and `certificates`. These are separated because evaluation and certification have their own data lifecycles and access patterns.

**Security and Audit tables** manage access control and auditing: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `activity_log`, and `gdpr_deletion_logs`. Every mutating action is logged immutably.

**Supporting tables** enable the application to function: `settings`, `media`, `notifications`, `absence_requests`, `announcements`, `incident_reports`, and `placement_change_requests`.

---

## Schema Organization

All migrations live in a flat `database/migrations/` directory with chronological timestamp prefixes. Migration filenames indicate which table they create (e.g., `2026_04_29_092750_create_users_table.php`). Module ownership is evident from the table name.

Foreign key delete behaviors:

- **CASCADE** when the child record cannot exist without the parent
- **SET NULL** when the relationship is optional
- **RESTRICT** when deletion should be prevented

Composite indexes are created for known query patterns, not speculatively:

| Pattern            | Index Example                                     |
| ------------------ | ------------------------------------------------- |
| FK + status filter | `[registration_id, status]` on `attendances`      |
| User + date lookup | `[user_id, date]` on `logbooks` and `attendances` |
| Polymorphic lookup | `[subject_type, subject_id]` on `activity_log`    |

Factories and seeders mirror the module structure. Seeders are idempotent — they can be run multiple times without duplicating data. The seeding order respects module dependencies: school data before user data, permissions before role assignments, internships before registrations.

---

## Engine Comparison

| Feature                | SQLite               | MySQL 8+             | MariaDB 10.6+        | PostgreSQL 14+ |
| ---------------------- | -------------------- | -------------------- | -------------------- | -------------- |
| **Setup**              | Zero — file-based    | Server install       | Server install       | Server install |
| **Concurrent writes**  | ❌ Locks entire file | ✅ Row-level locking | ✅ Row-level locking | ✅ MVCC        |
| **Connection pooling** | Not needed           | ProxySQL             | ProxySQL             | PgBouncer      |
| **Read replicas**      | Not supported        | Supported            | Supported            | Supported      |
| **Default for**        | Development, testing | Production           | Production           | Production     |

### Known SQLite vs MySQL/PostgreSQL Differences

| Difference                            | Impact                                                                                                      |
| ------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| SQLite does not enforce column length | A `varchar(255)` column accepts longer values in SQLite, but MySQL truncates. Ensure data fits constraints. |
| SQLite has no native `ENUM` type      | Enum columns are stored as `text` with check constraints. Migrations abstract this difference.              |
| SQLite locks on write                 | Under concurrent write load, "database is locked" errors occur. Switch to MySQL/PostgreSQL in production.   |
| SQLite `ALTER TABLE` is limited       | Some schema changes require table recreation.                                                               |

### Connection Pooling

For high-traffic deployments:

- **MySQL**: ProxySQL or configure `max_connections` appropriately
- **PostgreSQL**: PgBouncer for transaction-mode pooling

Laravel supports read/write separation for replicas:

```php
// config/database.php
'read' => ['host' => ['192.168.1.1']],
'write' => ['host' => ['192.168.1.2']],
```

### Migration Strategy

```bash
# Run migrations on current connection
php artisan migrate

# When switching from SQLite to MySQL/PostgreSQL:
# 1. Configure new connection in .env
# 2. Run migrations
# 3. Import existing data using database dump tools
```

---

## Infrastructure Context

| Tier           | Engine                    | Connection Pooling              | Read Replicas  |
| -------------- | ------------------------- | ------------------------------- | -------------- |
| 1 (Shared)     | MySQL / MariaDB (shared)  | Not needed                      | Not supported  |
| ≤500 users     |                           |                                 |                |
| 2 (VPS)        | MySQL 8+ / PostgreSQL 14+ | Optional (ProxySQL / PgBouncer) | Optional       |
| 500–2000 users |                           |                                 |                |
| 3 (HA)         | MySQL 8+ / PostgreSQL 14+ | ✅ Required                     | ✅ Recommended |
| 2000+ users    |                           |                                 |                |

---

## Where to Find It

- All migrations: `database/migrations/`
- Factories: `database/factories/`
- Seeders: `database/seeders/`
- Base model: `app/Core/Models/BaseModel.php`
- Database configuration: `config/database.php` (overridable via `.env`)
- Module reference: [Module Index](../modules/module-index.md)
- Infrastructure design: [Infrastructure](infrastructure.md#4-database-strategy)
