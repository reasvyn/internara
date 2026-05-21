# 13 — Admin & Audit

> **Lifecycle:** User action → Activity log → Status change → GDPR compliance → Super admin approval
> **Domains:** `Core`, `Admin`
> **Tables:** 5 (`activity_log`, `statuses`, `gdpr_deletion_logs`, `super_admin_approvals`, `password_reset_tokens`)
> **Polymorphic:** `activity_log`, `statuses`, `model_has_roles`, `model_has_permissions`

---

## Purpose

Cross-cutting administrative infrastructure. Audit logging (Spatie Activity Log), state machine history (Spatie Model States), GDPR compliance, and multi-admin approval workflows. These tables are written-to by actions across all domains.

---

## Tables

### activity_log (Spatie Activity Log)

Polymorphic audit trail for model changes and user actions.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | integer | PK, AUTO_INCREMENT | |
| log_name | varchar(255) | NULLABLE | Module/domain namespace (set by `->useLog()`) |
| description | text | NOT NULL | Human-readable action description |
| subject_type | varchar(255) | NULLABLE | Polymorphic: model class being acted upon |
| subject_id | integer | NULLABLE | Polymorphic: model ID |
| event | varchar(255) | NULLABLE | Action verb: 'created', 'updated', 'deleted', 'login', etc. |
| causer_type | varchar(255) | NULLABLE | Polymorphic: actor model class |
| causer_id | integer | NULLABLE | Polymorphic: actor model ID |
| properties | text | NULLABLE | JSON: before/after snapshot of changed data |
| batch_uuid | varchar(255) | NULLABLE | Groups related activities in a single operation |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**How it's written (via `SmartLogger`):**
```php
SmartLogger::info('User registered')
    ->event('created')
    ->module('Auth')
    ->about($user)
    ->withPayload(['email' => $user->email])
    ->activityOnly()
    ->save();
```

**Key scopes (defined in `App\Domain\Core\Models\ActivityLog`):**
- `forUser($userId)` — Filter by causer
- `ofAction($event)` — Filter by event type
- `forModule($module)` — Filter by domain module
- `lastDays($days)` — Time range filter
- `groupedByDay($days)` — Aggregation for charts

**Data growth:** This is the fastest-growing table. Every action log call creates a row. Estimate ~50 rows per active user per day. Pruned by `system:cleanup` (retention configurable).

### statuses (Spatie Model States)

Polymorphic state machine history. Records every state transition for models using Spatie ModelStates.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | integer | PK, AUTO_INCREMENT | |
| name | varchar(255) | NOT NULL | State name (e.g., 'pending', 'active', 'completed') |
| reason | text | NULLABLE | Why the transition occurred |
| model_type | varchar(255) | NOT NULL | Polymorphic: model class |
| model_id | integer | NOT NULL | Polymorphic: model ID |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Used by models with Spatie ModelStates:** `Internship` (status), `InternshipRegistration` (status), `Report` (status), etc.

**Note:** Not all status fields use Spatie ModelStates. Simple two-state statuses (like `account_applications.status: pending/approved/rejected`) use plain string columns with `StatusEnum` contracts.

### gdpr_deletion_logs

Audit trail for GDPR data deletion/anonymization requests.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | integer | PK, AUTO_INCREMENT | |
| user_id | integer | NULLABLE | Deleted user's ID (may be null after anonymization) |
| user_email | varchar(255) | NOT NULL | Preserved for legal reference |
| deletion_type | varchar(255) | DEFAULT 'anonymization' | 'anonymization', 'full_deletion', 'export' |
| reason | varchar(255) | NOT NULL | Legal basis for deletion |
| deleted_by | integer | FK → users(id) | Admin who processed the request |
| metadata | text | NULLABLE | JSON with deletion details |
| deleted_at | datetime | NOT NULL | When deletion occurred |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Deletion types:**
- `anonymization` — PII replaced with placeholders, record retained
- `full_deletion` — Irreversible row deletion
- `export` — Data export request (GDPR Article 20)

### super_admin_approvals

Multi-admin approval workflow for sensitive operations.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| target_user_id | varchar(36) | FK → users(id) | Subject of the change |
| requested_by_user_id | varchar(36) | FK → users(id) | Admin requesting |
| change_type | varchar(255) | NOT NULL | What is being changed |
| change_data | text | NULLABLE | JSON with proposed changes |
| status | varchar(255) | DEFAULT 'pending' | 'pending', 'approved', 'rejected' |
| approvals_count | integer | DEFAULT 0 | Number of admins who approved |
| approved_at | datetime | NULLABLE | |
| rejected_at | datetime | NULLABLE | |
| rejection_reason | varchar(255) | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Change types:** 'role_change', 'account_unlock', 'email_change', 'sensitive_data_edit', 'certificate_revocation'.

**Approval threshold:** Configurable in settings. Default: 2 super admins must approve.

### password_reset_tokens

Documented in [01-auth.md](01-auth.md). Included here as a system-level audit mechanism.

---

## Polymorphic Tables (Spatie Permission)

### model_has_roles

| Column | Type | Constraints |
|---|---|---|
| role_id | integer | FK → roles(id), CAS, PK |
| model_type | varchar(255) | PK |
| model_id | varchar(36) | PK |

### model_has_permissions

| Column | Type | Constraints |
|---|---|---|
| permission_id | integer | FK → permissions(id), CAS, PK |
| model_type | varchar(255) | PK |
| model_id | varchar(36) | PK |

### roles

| Column | Type | Constraints |
|---|---|---|
| id | integer | PK, AUTO_INCREMENT |
| name | varchar(255) | NOT NULL |
| guard_name | varchar(255) | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

**Seeded roles:** `super_admin`, `admin`, `teacher`, `student`, `supervisor`.

### permissions

| Column | Type | Constraints |
|---|---|---|
| id | integer | PK, AUTO_INCREMENT |
| name | varchar(255) | NOT NULL |
| guard_name | varchar(255) | NOT NULL |
| created_at | timestamp | |
| updated_at | timestamp | |

### role_has_permissions

| Column | Type | Constraints |
|---|---|---|
| permission_id | integer | FK → permissions(id), CAS, PK |
| role_id | integer | FK → roles(id), CAS, PK |

---

## Key Queries

### Recent activity feed for a registration:

```sql
SELECT al.description, al.event, al.created_at,
       al.causer_type, al.causer_id
FROM activity_log al
WHERE al.subject_type = 'App\\Domain\\Registration\\Models\\Registration'
  AND al.subject_id = ?
ORDER BY al.created_at DESC
LIMIT 50;
```

### Pending super admin approvals:

```sql
SELECT sa.id, sa.change_type, sa.change_data,
       tu.name AS target_user, ru.name AS requested_by,
       sa.created_at
FROM super_admin_approvals sa
JOIN users tu ON tu.id = sa.target_user_id
JOIN users ru ON ru.id = sa.requested_by_user_id
WHERE sa.status = 'pending'
ORDER BY sa.created_at;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `activity_log.causer_id` | `users.id` (polymorphic) | All |
| `activity_log.subject_id` | Polymorphic | All |
| `gdpr_deletion_logs.deleted_by` | `users.id` | 01-auth |
| `super_admin_approvals.target_user_id` | `users.id` | 01-auth |
| `super_admin_approvals.requested_by_user_id` | `users.id` | 01-auth |
| `model_has_roles.model_id` | `users.id` (polymorphic) | 01-auth |
| `model_has_permissions.model_id` | `users.id` (polymorphic) | 01-auth |
