# 04 — Internship Program

> **Lifecycle:** Program creation → Placement slots → Briefing sessions → Scheduling → Document requirements
> **Domains:** `Internship`, `Schedule`
> **Tables:** 6 (`internships`, `briefings`, `briefing_attendances`, `internship_document_requirements`, `placement_change_requests`, `schedules`)

---

## Purpose

Defines the internship program container — the overarching structure that holds all other lifecycles. An `internships` row represents one internship period (semester) for a specific academic year. It configures dates, weights, and program-level settings. Supporting entities handle pre-internship briefings, document requirements, scheduling, and placement changes.

---

## Tables

### internships

Program definition — the root container for a cohort of students.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| academic_year_id | varchar(36) | FK → academic_years(id), CAS | Links to academic calendar. Cascade deletes program if year removed |
| name | varchar(255) | NOT NULL | Program name (e.g., "PKL 2025/2026 Genap") |
| start_date | date | NOT NULL | Internship execution start |
| end_date | date | NOT NULL | Internship execution end |
| registration_start_date | date | NULLABLE | When registration opens |
| registration_end_date | date | NULLABLE | When registration closes |
| description | text | NULLABLE | Program description, objectives |
| status | varchar(255) | DEFAULT 'draft' | State machine: 'draft' → 'open' → 'active' → 'completed' → 'archived' |
| requires_presentation | boolean | DEFAULT false | Whether final presentation is mandatory |
| presentation_weight | integer | DEFAULT 50 | % weight of presentation in final score |
| report_weight | integer | DEFAULT 50 | % weight of report in final score |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
draft ──► open ──► active ──► completed ──► archived
           │         │
           └─────────┘ (back to draft if corrections needed)
```

- `draft` — Being configured, not visible to students
- `open` — Registration phase, students can apply
- `active` — Internship in progress
- `completed` — All students finished, grading done
- `archived` — Read-only historical record

**Constraint:** `presentation_weight + report_weight` should equal 100 (enforced in application).

**Foreign Keys:** Referenced by 10+ tables. This is the second most-referenced table after `users`.

### briefings

Pre-internship orientation sessions.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| title | varchar(255) | NOT NULL | Briefing title |
| description | text | NULLABLE | Detailed agenda |
| date | datetime | NOT NULL | Scheduled date/time |
| location | varchar(255) | NULLABLE | Physical room or virtual link |
| is_mandatory | boolean | DEFAULT true | Whether attendance is required |
| internship_id | varchar(36) | FK → internships(id), CAS | Parent program |
| created_by | varchar(36) | FK → users(id), idx | Who created the briefing |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### briefing_attendances

Attendance records for briefing sessions.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| briefing_id | varchar(36) | FK → briefings(id), CAS | Which briefing |
| user_id | varchar(36) | FK → users(id), CAS | Which student |
| attended | boolean | DEFAULT false | Marked present/absent |
| notes | text | NULLABLE | Reason for absence, etc. |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** One record per `[briefing_id, user_id]` pair (enforced in application, no DB unique constraint because absent students may not have a record).

### internship_document_requirements

Documents that students must submit for a specific internship program.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| internship_id | varchar(36) | FK → internships(id), CAS | Parent program |
| document_id | varchar(36) | FK → documents(id), idx | Required document template |
| is_mandatory | boolean | DEFAULT true | Whether submission is required |
| sort_order | integer | DEFAULT 0 | Display ordering |
| created_at | timestamp | | |
| updated_at | timestamp | | |

### placement_change_requests

Student-initiated requests to change their placement during an active internship.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student's registration |
| from_placement_id | varchar(36) | FK → placements(id) | Current placement |
| to_placement_id | varchar(36) | FK → placements(id), NULLABLE | Requested placement |
| reason | text | NOT NULL | Why the change is needed |
| requested_by | varchar(36) | FK → users(id) | Student who requested |
| status | varchar(255) | DEFAULT 'pending' | 'pending' → 'approved' / 'rejected' |
| processed_by | varchar(36) | FK → users(id), NULLABLE | Admin who processed |
| processed_at | timestamp | NULLABLE | |
| rejection_reason | text | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
pending ──► approved  (quota decremented on from, incremented on to)
pending ──► rejected  (no quota changes)
```

### schedules

Calendar events linked to internship programs.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| title | varchar(255) | NOT NULL | Event title |
| description | text | NULLABLE | |
| start_at | datetime | NOT NULL | Event start |
| end_at | datetime | NULLABLE | Event end |
| type | varchar(255) | NOT NULL | 'briefing', 'presentation', 'visit', 'deadline', 'other' |
| location | varchar(255) | NULLABLE | |
| internship_id | varchar(36) | FK → internships(id), idx | Associated program (nullable for school-wide events) |
| created_by | varchar(36) | FK → users(id) | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

## Key Queries

### Get currently active internship:

```sql
SELECT * FROM internships
WHERE status = 'active'
  AND start_date <= CURDATE()
  AND end_date >= CURDATE()
LIMIT 1;
```

### Count total placements and filled slots per program:

```sql
SELECT i.name,
       COUNT(p.id) AS total_slots,
       SUM(p.filled_quota) AS filled,
       SUM(p.quota) - SUM(p.filled_quota) AS available
FROM internships i
LEFT JOIN placements p ON p.internship_id = i.id
WHERE i.id = ?
GROUP BY i.id;
```

---

## State Machine

`internships.status` transitions are governed by the `InternshipStatus` state machine (Spatie Model States):
```
draft ──────► open ──────► active ──────► completed ──────► archived
  │              │                            │
  └──◄──────────┘                            │
       (re-open)                             │
       ┌─────────────────────────────────────┘
       │        (re-activate)
       └────────────►
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `internships.academic_year_id` | `academic_years.id` | 02-institution |
| `internships.id` | `placements.internship_id` | 03-partnership |
| `internships.id` | `registrations.internship_id` | 05-registration |
| `internships.id` | `assignments.internship_id` | 08-assignment |
| `internships.id` | `rubrics.internship_id` | 09-assessment |
| `internships.id` | `schedules.internship_id` | 04-internship |
| `internships.id` | `briefings.internship_id` | 04-internship |
| `internships.id` | `internship_document_requirements.internship_id` | 04-internship |
