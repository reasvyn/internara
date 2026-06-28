# Database — Schema Design & Migration Strategy

> **Last updated:** 2026-06-24 **Changes:** sync — migration consolidation: 57 scattered migrations
> **Changes:** sync — initial metadata sync with new format
## Description

Schema design principles, migration conventions, supported database engines, UUID primary keys, and indexing strategy.

## Design Philosophy

The database is organized around the concept that every piece of persistent state belongs to a
module. Tables are grouped into five conceptual categories: core, operational, assessment, security,
and supporting. This structure makes it obvious where data lives and how it relates.

The schema encompasses 44 domain models plus 9+ framework/package tables (settings, cache, jobs,
notifications, pulse, backups, media, activity logs, permissions).

---

## UUID Primary Keys

Every business model uses a UUID v7 primary key instead of an auto-incrementing integer. UUIDs are
globally unique without a central sequence, making database merging, seeding across environments,
and distributed deployment safe. UUIDs also prevent information leakage — unlike sequential IDs,
they reveal nothing about the total number of records or the order of creation.

All foreign key columns use UUIDs to match their parent primary keys, and every foreign key is
explicitly indexed. Models extending `BaseModel` automatically gain UUID support via Laravel's
`HasUuids` trait. The `User` model applies it manually since it extends `Authenticatable` directly.

---

## SQLite as Default

The default database driver is SQLite. It requires zero configuration — no server process, no
credentials, no port management. For development and testing, this eliminates operational friction.
Testing also uses SQLite (in-memory mode), which makes the test suite fast and self-contained.

SQLite is intended for development and testing only. In shared hosting production, use MySQL or
MariaDB provided by your hosting service. Scale to PostgreSQL when exceeding 500 registered users
per PKL period. The application abstracts database access through Eloquent, so switching drivers
requires changing only the environment variable.

---

## Key Table Categories

**Core tables** are the foundation: `users`, `profiles`, `departments`, and `academic_years`. These
define who the participants are and what organizational structure they belong to.

**Operational tables** track the primary workflows: `internships`, `placements`, `registrations`,
`attendances`, `logbooks`, `supervision_logs`, `assignments`, and `submissions`. These record what
happens during the internship lifecycle.

**Assessment & Certification tables** handle evaluation and credentials: `rubrics`, `assessments`,
`evaluations`, `reports`, and `certificates`. These are separated because evaluation and
certification have their own data lifecycles and access patterns.

**Security and Audit tables** manage access control and auditing: `roles`, `permissions`,
`model_has_roles`, `model_has_permissions`, `role_has_permissions`, `activity_log`, and
`gdpr_deletion_logs`. Every mutating action is logged immutably.

**Supporting tables** enable the application to function: `settings`, `media`, `notifications`,
`absence_requests`, `announcements`, `incident_reports`, and `placement_change_requests`.

---

## Migration Structure

Migrations are organized into **six sequential layers** (2026_01_01 through 2026_01_06) representing
the initialization order. Each layer groups logically related tables:

| Layer               | Date Prefix | Purpose                                                   | Example Tables                                                                                                                                                         |
| ------------------- | ----------- | --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Foundation**      | 2026_01_01  | Framework tables: config, caching, jobs, observability    | settings, cache, jobs, notifications, pulse, backups, announcements                                                                                                    |
| **Authentication**  | 2026_01_02  | Users, tokens, RBAC, audit, profiles                      | users, access_tokens, permissions, activity_log, gdpr_deletion_logs, profiles                                                                                          |
| **Configuration**   | 2026_01_03  | School organization and shared entities                   | academic_years, departments, companies, media, documents, rubrics, partnerships                                                                                        |
| **Internship Core** | 2026_01_04  | PKL workflow: placements, attendance, reports, evaluation | internships, placements, registrations, applications, attendances, logbooks, supervision_logs, assignments, submissions, assessments, reports, certificates, incidents |
| **Grouping**        | 2026_01_05  | Student grouping and document tracking                    | internship_groups, registration_documents, placement_change_requests                                                                                                   |
| **Evaluation**      | 2026_01_06  | Feedback forms and survey infrastructure                  | evaluation_forms, evaluation_sections, evaluation_questions, evaluation_responses, evaluation_answers                                                                  |

This structure ensures clean dependency resolution: foundation tables before auth, auth before
domain models, domain models before business workflows.

---

## Schema Organization

Module ownership is evident from the table name (e.g., `internship_groups`, `supervision_logs`,
`incident_reports`). Each table's migration file is named to match its table (e.g.,
`2026_01_04_000001_create_internships_table.php`).

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

Factories and seeders mirror the module structure. Seeders are idempotent — they can be run multiple
times without duplicating data. The seeding order respects module dependencies: school data before
user data, permissions before role assignments, internships before registrations.

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
