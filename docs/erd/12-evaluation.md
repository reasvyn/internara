# 12 — Evaluations & Notifications

> **Lifecycle:** Mentor evaluation → Feedback collection → Notification delivery → Announcements
> **Domains:** `Evaluation`, `Admin`
> **Tables:** 3 (`evaluations`, `notifications`, `announcements`)

---

## Purpose

Two feedback mechanisms: evaluations (structured mentor/quality assessments with scores) and notifications (targeted in-app messages). Announcements are broadcast messages to role-based audiences.

---

## Tables

### evaluations

Polymorphic evaluations — primarily used for mentor quality assessment.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| evaluator_id | varchar(36) | FK → users(id) | Who submitted the evaluation |
| evaluation_type | varchar(255) | DEFAULT 'mentor' | 'mentor', 'program', 'company' |
| mentor_id | varchar(36) | FK → users(id), NULLABLE | Who/what is being evaluated |
| registration_id | varchar(36) | FK → registrations(id), NULLABLE | Associated registration |
| target_type | varchar(255) | NULLABLE | Polymorphic target model type |
| target_id | varchar(255) | NULLABLE | Polymorphic target model ID |
| overall_score | float | NOT NULL | Aggregate score (0-100) |
| feedback | text | NULLABLE | Written feedback |
| criteria_scores | text | NULLABLE | JSON: detailed scores per criterion |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Polymorphic design:** The `target_type`/`target_id` pair allows evaluations to reference any model without adding foreign keys. Currently used for mentor evaluation; extensible to program quality, company satisfaction, etc.

**Evaluation types:**
- `mentor` — Student evaluates their mentor
- `program` — Student evaluates the internship program
- `company` — Student evaluates the host company

### notifications

In-app notification records. Populated via `CustomDatabaseChannel`.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), idx | Recipient |
| type | varchar(255) | NOT NULL | Notification class/type identifier |
| title | varchar(255) | NOT NULL | Notification header |
| message | text | NULLABLE | Body text |
| data | text | NULLABLE | JSON payload with action data |
| link | varchar(255) | NULLABLE | Deep link URL |
| is_read | boolean | DEFAULT 0 | Read/unread status |
| read_at | timestamp | NULLABLE | When the user opened it |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** `user_id`, `is_read`. Composite `[user_id, is_read]` for unread count queries (application index).

**Notification types:** 'assignment_created', 'submission_graded', 'absence_approved', 'certificate_issued', 'report_feedback', etc.

### announcements

Broadcast messages to targeted roles.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| title | varchar(255) | NOT NULL | Announcement headline |
| message | text | NOT NULL | Full message body |
| type | varchar(255) | DEFAULT 'info' | 'info', 'warning', 'alert', 'success' |
| link | varchar(255) | NULLABLE | Optional reference URL |
| target_roles | text | NULLABLE | JSON array of target role names (null = all users) |
| created_by | varchar(36) | FK → users(id) | Admin author |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Targeting:** `target_roles` stores a JSON array like `["student", "teacher"]`. If null, the announcement is visible to all authenticated users.

---

## Key Queries

### Mentor evaluation average:

```sql
SELECT AVG(overall_score) AS avg_score,
       COUNT(*) AS total_evaluations
FROM evaluations
WHERE mentor_id = ?
  AND evaluation_type = 'mentor';
```

### Unread notification count:

```sql
SELECT COUNT(*) AS unread_count
FROM notifications
WHERE user_id = ?
  AND is_read = 0;
```

### Active announcements for a role:

```sql
SELECT title, message, type, created_at
FROM announcements
WHERE (target_roles IS NULL
    OR target_roles LIKE '%"student"%')
ORDER BY created_at DESC
LIMIT 10;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `evaluations.evaluator_id` | `users.id` | 01-auth |
| `evaluations.mentor_id` | `users.id` | 01-auth |
| `evaluations.registration_id` | `registrations.id` | 05-registration |
| `notifications.user_id` | `users.id` | 01-auth |
| `announcements.created_by` | `users.id` | 01-auth |
