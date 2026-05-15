# Database

## Connection
The default database is SQLite. MySQL, MariaDB, and PostgreSQL are also supported. Configure via `DB_CONNECTION` in your `.env` file.
Testing uses SQLite `:memory:` with `LazilyRefreshDatabase`.

## UUID Primary Keys
All business models use UUIDs instead of auto-incrementing integers. Most models extend `BaseModel` which provides `HasUuids`, non-incrementing keys, and string key type. The `User` model extends `Authenticatable` and applies `HasUuids` directly.

## Mass Assignment
Models use PHP 8 `#[Fillable]` and `#[Hidden]` attributes. Older models may still use the traditional `$fillable` property.

## Foreign Keys
Always use constrained UUID foreign keys: `$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();`

## Package Integrations
| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Role-based access control |
| `spatie/laravel-medialibrary` | File attachments (User avatar, School logo, Document files, RegistrationDocument uploads, Submission files) |
| `spatie/laravel-activitylog` | Model change tracking and audit trail |
| `spatie/laravel-model-status` | Polymorphic status tracking (used on Registration model) |
| `spatie/laravel-honeypot` | Spam protection for forms |

## Core Tables

| Table | Purpose |
|---|---|
| `users` | User accounts (all roles) |
| `profiles` | Extended user profile data |
| `schools` | Institution profile (single record) |
| `departments` | Academic departments |
| `academic_years` | Academic calendar periods |
| `internships` | Internship programs |
| `internship_companies` | Partner companies |
| `internship_placements` | Placement slots with quotas |
| `internship_registrations` | Student internship enrollment |
| `internship_document_requirements` | Required documents per internship |
| `registration_documents` | Uploaded documents per registration |
| `registration_mentor` | Mentor assignments (pivot) |
| `mentees` | Student role extension |
| `mentors` | Mentor role extension (teacher/supervisor) |
| `roles` / `permissions` | RBAC (spatie/laravel-permission) |
| `model_has_roles` / `model_has_permissions` | Role/permission assignments |

## Operational Tables

| Table | Purpose |
|---|---|
| `logbooks` | Daily student journals |
| `attendances` | Daily attendance records |
| `assignments` | Teacher-created tasks |
| `assignment_types` | Assignment categorization |
| `submissions` | Student assignment submissions |
| `supervision_logs` | Mentoring/guidance session records |
| `schedules` | Event scheduling |
| `handbooks` | Internship handbook versions |
| `handbook_acknowledgements` | Student handbook sign-off |
| `announcements` | System-wide announcements |

## Assessment Tables

| Table | Purpose |
|---|---|
| `rubrics` | Evaluation rubrics |
| `competencies` | Rubric competencies (weighted) |
| `indicators` | Competency scoring indicators |
| `assessments` | Student assessment records |
| `evaluations` | Mentor evaluation records |

## Account & Security Tables

| Table | Purpose |
|---|---|
| `account_applications` | New student account requests |
| `account_status_history` | Status change audit trail |
| `account_recovery_codes` | Account recovery tokens |
| `account_restrictions` | Functional restrictions per user |
| `activation_tokens` | Account activation/setup tokens |
| `super_admin_approvals` | Multi-approval workflow for sensitive changes |
| `login_history` | Login attempt records |
| `suspicious_login_attempts` | Flagged login anomalies |
| `password_reset_tokens` | Password reset flow |
| `sessions` | Active user sessions |

## Supporting Tables

| Table | Purpose |
|---|---|
| `settings` | Key-value configuration store |
| `setups` | Installation state tracking |
| `statuses` | Polymorphic status history (spatie/laravel-model-status) |
| `media` | File attachments (spatie/laravel-medialibrary) |
| `activity_log` | Audit trail (spatie/laravel-activitylog) |
| `notifications` | In-app notification records |
| `absence_requests` | Student absence submissions |
| `documents` | Reference document library |
| `teams` / `team_user` | Team management |
| `gdpr_deletion_logs` | GDPR compliance deletion records |
| `schedules` | Calendar events |
| `cache` / `cache_locks` | Cache driver tables |
