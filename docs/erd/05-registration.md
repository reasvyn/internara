# 05 — Student Registration

> **Lifecycle:** Account application → Approval → Mentee creation → Program registration → Mentor assignment → Document upload
> **Domains:** `Mentee`, `Registration`, `Document` (partial)
> **Tables:** 5 (`mentees`, `registrations`, `account_applications`, `registration_mentor`, `registration_documents`)

---

## Purpose

The registration lifecycle is the operational core of Internara. It manages the journey from a prospective student applying for an internship account, through approval and program enrollment, to mentor assignments and document submissions.

`registrations` is the **most connected table** in the entire schema (~15 relationships). Almost every operational feature references it.

---

## Tables

### mentees

The student role activation. Links a `User` to the student subsystem.
Not all users are mentees — only those assigned the `student` role via RBAC.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), idx | One user can have at most one mentee record across all time |
| is_active | boolean | DEFAULT true | Soft deactivation (e.g., graduated, expelled) |
| internal_notes | text | NULLABLE | Administrative notes (not visible to student) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Creation trigger:** When a user is assigned the `student` role AND approved via `account_applications`, a `mentees` row is created.

### registrations

The central entity. Links a mentee to an internship program and placement.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | Referenced by ~15 child tables |
| mentee_id | varchar(36) | FK → mentees(id), CAS | Student participant |
| internship_id | varchar(36) | FK → internships(id), CAS | Program enrolled in |
| placement_id | varchar(36) | FK → placements(id), SNU | Assigned slot (null until placed) |
| academic_year | varchar(255) | NULLABLE | Snapshot of `academic_years.name` at registration time |
| start_date | date | NULLABLE | Actual internship start (may differ from program) |
| end_date | date | NULLABLE | Actual internship end |
| proposed_company_name | varchar(255) | NULLABLE | Student's preference (self-registration path) |
| proposed_company_address | text | NULLABLE | |
| status | varchar(255) | idx, DEFAULT 'pending' | State machine: see below |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** Index on `status`, composite on `[mentee_id, internship_id]`, composite on `[start_date, end_date]`.

**Status transitions (Spatie Model States):**
```
pending ──► active
```

- `pending` — Registration submitted, awaiting admin verification and placement
- `active` — Verified and placed; student can participate in internship activities

### account_applications

Self-service student applications (before user account exists).
Becomes a `User` + `Mentee` + `Registration` upon approval.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | NOT NULL | Applicant's full name |
| email | varchar(255) | NOT NULL | Will become `users.email` |
| phone | varchar(255) | NULLABLE | |
| address | text | NULLABLE | |
| national_identifier | varchar(255) | NULLABLE | Government ID |
| registration_number | varchar(255) | NULLABLE | Student number |
| school_id | varchar(36) | FK → schools(id), idx | Home school |
| department_id | varchar(36) | FK → departments(id), idx | Study program |
| class_name | varchar(255) | NULLABLE | Class/grade |
| entry_year | integer | NULLABLE | Year of enrollment |
| internship_id | varchar(36) | FK → internships(id), NOT NULL | Target program |
| placement_id | varchar(36) | FK → placements(id), NULLABLE | Preferred placement |
| academic_year | varchar(255) | NULLABLE | |
| proposed_company_name | varchar(255) | NULLABLE | Self-proposed company |
| proposed_company_address | text | NULLABLE | |
| status | varchar(255) | DEFAULT 'pending' | 'pending' → 'approved' / 'rejected' |
| processed_by | varchar(36) | FK → users(id), NULLABLE | Admin processor |
| processed_at | timestamp | NULLABLE | |
| rejection_reason | text | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
pending ──► approved  (creates User + Mentee + Registration)
pending ──► rejected  (email notification sent with reason)
```

### registration_mentor

Pivot table linking registrations to mentors. Composite primary key.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| registration_id | varchar(36) | FK → registrations(id), CAS, PK | |
| mentor_id | varchar(36) | FK → mentors(id), CAS, PK | |
| role | varchar(255) | NULLABLE | 'school_mentor' (teacher), 'industry_mentor' (supervisor) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** A registration can have multiple mentors (one school-side, one industry-side).

### registration_documents

Document submissions for a registration.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | |
| internship_document_requirement_id | varchar(36) | FK → internship_document_requirements(id) | Links to the required document definition |
| status | varchar(255) | DEFAULT 'pending' | 'pending' → 'verified' / 'rejected' |
| admin_notes | text | NULLABLE | | |
| verified_by | varchar(36) | FK → users(id), SNU | Admin who verified |
| verified_at | timestamp | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
pending ──► verified
pending ──► rejected
```

- `pending` — Document uploaded, awaiting verification
- `verified` — Admin has verified the document
- `rejected` — Document rejected; student can re-upload

---

## Key Queries

### Full student registration detail:

```sql
SELECT r.id, u.name, u.email, i.name AS program,
       c.name AS company, pl.name AS position,
       r.status, r.start_date, r.end_date
FROM registrations r
JOIN mentees m ON m.id = r.mentee_id
JOIN users u ON u.id = m.user_id
JOIN internships i ON i.id = r.internship_id
LEFT JOIN placements pl ON pl.id = r.placement_id
LEFT JOIN companies c ON c.id = pl.company_id
WHERE r.id = ?;
```

### Pending account applications:

```sql
SELECT * FROM account_applications
WHERE status = 'pending'
ORDER BY created_at ASC;
```

### Mentor-student assignments:

```sql
SELECT rm.role, u.name AS mentor_name,
       su.name AS student_name
FROM registration_mentor rm
JOIN mentors m ON m.id = rm.mentor_id
JOIN users u ON u.id = m.user_id
JOIN registrations r ON r.id = rm.registration_id
JOIN mentees me ON me.id = r.mentee_id
JOIN users su ON su.id = me.user_id
WHERE r.id = ?;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `mentees.user_id` | `users.id` | 01-auth |
| `registrations.mentee_id` | `mentees.id` | 05-registration |
| `registrations.internship_id` | `internships.id` | 04-internship |
| `registrations.placement_id` | `placements.id` | 03-partnership |
| `registration_mentor.mentor_id` | `mentors.id` | 07-mentoring |
| `registration_documents.internship_document_requirement_id` | `internship_document_requirements.id` | 04-internship |

**Registration is referenced by these child lifecycles:**
| Child Table | Column | Lifecycle |
|---|---|---|
| `attendances.registration_id` | 06-daily | |
| `absence_requests.registration_id` | 06-daily | |
| `logbooks.registration_id` | 06-daily | |
| `supervision_logs.registration_id` | 07-mentoring | |
| `submissions.registration_id` | 08-assignment | |
| `assessments.registration_id` | 09-assessment | |
| `presentations.registration_id` | 09-assessment | |
| `reports.registration_id` | 10-report | |
| `certificates.registration_id` | 10-report | |
| `incident_reports.registration_id` | 11-guidance | |
| `evaluations.registration_id` | 12-evaluation | |
| `placement_change_requests.registration_id` | 04-internship | |
