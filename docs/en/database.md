# Database

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

The complete database schema (75 tables, columns, types, foreign keys, indexes,
and business rules) is documented in the ERD docs organized by data lifecycle:

| Lifecycle | File | Tables |
|---|---|---|
| Identity & Access | `docs/en/erd/01-auth.md` | 10 |
| Institutional Setup | `docs/en/erd/02-institution.md` | 5 |
| Companies & Partnerships | `docs/en/erd/03-partnership.md` | 3 |
| Internship Program | `docs/en/erd/04-internship.md` | 6 |
| Student Registration | `docs/en/erd/05-registration.md` | 5 |
| Daily Execution | `docs/en/erd/06-daily.md` | 3 |
| Mentoring & Teams | `docs/en/erd/07-mentoring.md` | 4 |
| Assignments & Submissions | `docs/en/erd/08-assignment.md` | 3 |
| Assessment & Grading | `docs/en/erd/09-assessment.md` | 6 |
| Reports & Certification | `docs/en/erd/10-report.md` | 4 |
| Guidance & Incidents | `docs/en/erd/11-guidance.md` | 3 |
| Evaluations & Notifications | `docs/en/erd/12-evaluation.md` | 3 |
| Admin & Audit | `docs/en/erd/13-admin.md` | 5 |
| Infrastructure | `docs/en/erd/14-infra.md` | 11 |

Start with `docs/en/erd/00-erd-index.md` for the master map and conventions.
