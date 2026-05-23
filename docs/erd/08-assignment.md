# 08 — Assignments & Submissions

> **Lifecycle:** Assignment type definition → Assignment creation → Student submission → Grading
> **Domains:** `Assignment`
> **Tables:** 3 (`assignment_types`, `assignments`, `submissions`)

---

## Purpose

Manages task creation and student work submission. Assignments are defined per internship program and categorized by type. Students submit work which is then graded by teachers. Supports mandatory/optional assignments, due dates, and configurable metadata.

---

## Tables

### assignment_types

Categorization of assignments. Pre-seeded data, rarely modified.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | NOT NULL | Display name |
| slug | varchar(255) | NOT NULL | URL-friendly identifier |
| group | varchar(255) | NULLABLE | Category group for UI grouping |
| description | text | NULLABLE | | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Typical seed data:** 'Internship Report', 'Daily Log', 'Presentation', 'Competency Assessment', 'Company Visit Report'.

### assignments

Task definition within an internship program.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| assignment_type_id | varchar(36) | FK → assignment_types(id), CAS | Category |
| internship_id | varchar(36) | FK → internships(id), CAS | Parent program |
| academic_year | varchar(9) | NULLABLE | Snapshot (e.g., "2025/2026") |
| title | varchar(255) | NOT NULL | Assignment title |
| group | varchar(255) | NULLABLE | Sub-group within type |
| description | text | NULLABLE | Instructions |
| is_mandatory | boolean | DEFAULT false | Required for completion |
| due_date | timestamp | NULLABLE | Submission deadline |
| config | json | NULLABLE | Type-specific configuration |
| status | varchar(20) | DEFAULT 'draft' | 'draft', 'published', 'closed', 'archived' |
| created_by | varchar(36) | FK → users(id), NUL | Creator (teacher/admin) |
| document_id | varchar(36) | FK → documents(id), NUL | Reference document |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** Composite on `[internship_id, status]`.

**Status transitions:**
```
draft ──► published ──► closed ──► archived
  ▲                        │
  └────── draft (re-open) ─┘
```

- `draft` — Being prepared, invisible to students
- `published` — Visible, submissions accepted
- `closed` — Past due date, no new submissions
- `archived` — Historical, read-only

### submissions

Student submissions for assignments, with grading data.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| assignment_id | varchar(36) | FK → assignments(id), CAS | Parent assignment |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student's registration |
| student_id | varchar(36) | FK → users(id), CAS | Student user |
| content | text | NULLABLE | Submission body/text |
| metadata | json | NULLABLE | File attachments, additional data |
| submitted_at | timestamp | NULLABLE | First submission timestamp |
| status | varchar(20) | DEFAULT 'draft' | 'draft', 'submitted', 'graded', 'returned' |
| score | float | NULLABLE | Numerical grade |
| feedback | text | NULLABLE | Teacher's comments |
| graded_by | varchar(36) | FK → users(id), SNU | Who graded |
| graded_at | timestamp | NULLABLE | When graded |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** Composite on `[student_id, status]`, `[assignment_id, status]`, `[registration_id, status]`.

**Status transitions:**
```
draft ──► submitted ──► graded
                │
                └──► returned (resubmit)
                           │
                           └──► submitted (re-submitted for re-grading)
```

- `draft` — Student is working on it, not yet submitted
- `submitted` — Ready for grading
- `graded` — Score and feedback provided
- `returned` — Sent back for revision

**Constraint:** One submission per `[assignment_id, registration_id]` pair (enforced at application layer).

---

## Key Queries

### Pending assignments for a student:

```sql
SELECT a.title, at.name AS type, a.due_date,
       CASE WHEN s.id IS NULL THEN 'not_started'
            WHEN s.status = 'draft' THEN 'in_progress'
            WHEN s.status = 'submitted' THEN 'awaiting_grade'
            ELSE s.status
       END AS submission_status
FROM assignments a
JOIN assignment_types at ON at.id = a.assignment_type_id
LEFT JOIN submissions s ON s.assignment_id = a.id
    AND s.registration_id = ?
WHERE a.internship_id = (SELECT internship_id FROM registrations WHERE id = ?)
  AND a.status = 'published'
ORDER BY a.due_date;
```

### Ungraded submissions for a teacher:

```sql
SELECT s.id, a.title, u.name AS student,
       s.submitted_at
FROM submissions s
JOIN assignments a ON a.id = s.assignment_id
JOIN users u ON u.id = s.student_id
WHERE s.status = 'submitted'
  AND a.internship_id IN (
    SELECT internship_id FROM internships
  )
ORDER BY s.submitted_at ASC;
```

### Assignment completion rates:

```sql
SELECT a.title,
       COUNT(s.id) AS total_submissions,
       AVG(CASE WHEN s.status = 'graded' THEN 1 ELSE 0 END) AS graded_pct,
       AVG(s.score) AS avg_score
FROM assignments a
LEFT JOIN submissions s ON s.assignment_id = a.id
WHERE a.id = ?
GROUP BY a.id;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `assignments.internship_id` | `internships.id` | 04-internship |
| `assignments.assignment_type_id` | `assignment_types.id` | 08-assignment |
| `assignments.created_by` | `users.id` | 01-auth |
| `assignments.document_id` | `documents.id` | 04-internship |
| `submissions.assignment_id` | `assignments.id` | 08-assignment |
| `submissions.registration_id` | `registrations.id` | 05-registration |
| `submissions.student_id` | `users.id` | 01-auth |
| `submissions.graded_by` | `users.id` | 01-auth |
