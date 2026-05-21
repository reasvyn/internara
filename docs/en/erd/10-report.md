# 10 — Reports & Certification

> **Lifecycle:** Report creation → Chapter drafting → Submission → Revision → Grading → Certificate issuance
> **Domains:** `Internship` (report), `Certificate`
> **Tables:** 4 (`reports`, `report_revisions`, `certificates`, `certificate_templates`)

---

## Purpose

Manages the final two stages of the internship lifecycle: the written report and the completion certificate. Reports go through a revision workflow with mentor feedback. Certificates are generated from templates upon successful completion.

---

## Tables

### reports

Student internship reports with revision workflow.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student's registration |
| title | varchar(255) | NOT NULL | Report title |
| status | varchar(255) | idx, DEFAULT 'draft' | State machine |
| chapter_structure | json | NULLABLE | Outline/table of contents |
| content | json | NULLABLE | Report body (stored as structured JSON) |
| submitted_at | datetime | NULLABLE | When student submitted for grading |
| graded_by | varchar(36) | FK → users(id), NUL | Teacher who graded |
| graded_at | datetime | NULLABLE | When grading was done |
| score | float | NULLABLE | Numerical grade |
| feedback | text | NULLABLE | Overall feedback |
| supervisor_notes | text | NULLABLE | Industry supervisor input |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions (Spatie Model States):**
```
draft ──► submitted ──► in_review ──► graded
                              │
                              └──► revision_needed ──► submitted
```

- `draft` — Student is writing
- `submitted` — Ready for review
- `in_review` — Mentor is currently reviewing
- `revision_needed` — Sent back with feedback
- `graded` — Final score assigned, no more changes

**Content storage:** Report content is stored as JSON (not raw text). The `chapter_structure` defines the outline, and `content` maps chapter keys to their text content.

### report_revisions

Round-based revision tracking.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| report_id | varchar(36) | FK → reports(id), CAS | Parent report |
| round | integer | NOT NULL | Revision number (1, 2, 3...) |
| feedback | text | NOT NULL | Mentor's revision requests |
| requested_by | varchar(36) | FK → users(id) | Mentor who requested revision |
| requested_at | datetime | NOT NULL | When revision was requested |
| resubmitted_at | datetime | NULLABLE | When student resubmitted |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** `round` values increment per revision cycle. No gaps.

### certificate_templates

Layout templates for certificate generation.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | NOT NULL | Template name |
| layout | varchar(255) | DEFAULT 'portrait' | 'portrait', 'landscape' |
| content_template | text | NOT NULL | HTML/Blade template with placeholders |
| is_active | boolean | DEFAULT true | |
| created_by | varchar(36) | FK → users(id) | Template creator |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Template variables:** `{{student_name}}`, `{{internship_name}}`, `{{start_date}}`, `{{end_date}}`, `{{certificate_number}}`, `{{issue_date}}`.

### certificates

Issued completion certificates linked to registrations.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id) | Student's registration |
| certificate_number | varchar(255) | NOT NULL | Unique serial number (format: `INV-YYYY-NNNN`) |
| template_id | varchar(36) | FK → certificate_templates(id) | Which template was used |
| status | varchar(255) | DEFAULT 'issued' | 'issued', 'revoked' |
| issued_by | varchar(36) | FK → users(id) | Admin who issued |
| issued_at | datetime | NOT NULL | Date of issuance |
| metadata | text | NULLABLE | JSON with generated content snapshot |
| revoked_by | varchar(36) | FK → users(id), NULLABLE | Admin who revoked |
| revoked_at | datetime | NULLABLE | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
issued ──► revoked (irreversible, certificate invalidated)
```

**Certificate number format:** Auto-generated sequential number with year prefix (e.g., `INV-2026-0001`). Unique across all certificates.

---

## Key Queries

### Report revision history:

```sql
SELECT rr.round, rr.feedback, rr.requested_at, rr.resubmitted_at,
       u.name AS requested_by
FROM report_revisions rr
JOIN users u ON u.id = rr.requested_by
WHERE rr.report_id = ?
ORDER BY rr.round;
```

### Valid certificates for a registration:

```sql
SELECT c.certificate_number, ct.name AS template,
       c.issued_at, c.status
FROM certificates c
JOIN certificate_templates ct ON ct.id = c.template_id
WHERE c.registration_id = ?
ORDER BY c.issued_at DESC;
```

### Student completion status:

```sql
SELECT r.id, r.status AS reg_status,
       rp.status AS report_status, rp.score AS report_score,
       c.status AS cert_status, c.certificate_number
FROM registrations r
LEFT JOIN reports rp ON rp.registration_id = r.id
LEFT JOIN certificates c ON c.registration_id = r.id
WHERE r.id = ?;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `reports.registration_id` | `registrations.id` | 05-registration |
| `reports.graded_by` | `users.id` | 01-auth |
| `report_revisions.report_id` | `reports.id` | 10-report |
| `report_revisions.requested_by` | `users.id` | 01-auth |
| `certificates.registration_id` | `registrations.id` | 05-registration |
| `certificates.template_id` | `certificate_templates.id` | 10-report |
| `certificates.issued_by` | `users.id` | 01-auth |
| `certificates.revoked_by` | `users.id` | 01-auth |
| `certificate_templates.created_by` | `users.id` | 01-auth |
