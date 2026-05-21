# 11 — Guidance & Incidents

> **Lifecycle:** Handbook publishing → Student acknowledgement → Incident reporting → Resolution
> **Domains:** `Guidance`, `Incident`
> **Tables:** 3 (`handbooks`, `handbook_acknowledgements`, `incident_reports`)

---

## Purpose

Two unrelated concerns grouped for convenience: guidance documents that students must acknowledge, and incident reports for workplace issues. Both share a relationship to `users` and optional relationship to `registrations`, but otherwise independent.

---

## Tables

### handbooks

Versioned guidance documents.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| title | varchar(255) | NOT NULL | Handbook title |
| slug | varchar(255) | NOT NULL | URL-friendly identifier |
| content | text | NOT NULL | Full handbook body (Markdown/HTML) |
| version | integer | DEFAULT 1 | Incremented on each update |
| is_active | boolean | DEFAULT true | Only active handbooks require acknowledgement |
| published_at | timestamp | NULLABLE | When it was made available |
| created_by | varchar(36) | FK → users(id) | Author |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Versioning:** When content changes, `version` increments. Students must re-acknowledge new versions.

### handbook_acknowledgements

Records that a user has read and agreed to a handbook version.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Student |
| handbook_id | varchar(36) | FK → handbooks(id) | Handbook acknowledged |
| acknowledged_at | datetime | NOT NULL | When they clicked "I agree" |
| ip_address | varchar(45) | NULLABLE | Source IP (audit trail) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** Students should re-acknowledge when a new handbook version is published. Check: `handbook.version > handbook_acknowledgement.handbook.version_at_time_of_acknowledgement`.

### incident_reports

Workplace incident documentation.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id) | Affected student |
| reported_by | varchar(36) | FK → users(id) | Who reported |
| incident_date | datetime | NOT NULL | When it happened |
| type | varchar(255) | NOT NULL | 'accident', 'harassment', 'disciplinary', 'other' |
| severity | varchar(255) | NOT NULL | 'low', 'medium', 'high', 'critical' |
| description | text | NOT NULL | Detailed account |
| location | varchar(255) | NULLABLE | Where it occurred |
| action_taken | text | NULLABLE | Immediate response |
| status | varchar(255) | DEFAULT 'reported' | State machine |
| resolved_by | varchar(36) | FK → users(id), NULLABLE | Who resolved |
| resolved_at | datetime | NULLABLE | |
| resolution_notes | text | NULLABLE | Final outcome |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status transitions:**
```
reported ──► investigating ──► resolved
    │              │
    └── dismissed  └── escalated
```

- `reported` — Initial report filed
- `investigating` — Under review
- `resolved` — Action taken, closed
- `dismissed` — No action needed
- `escalated` — Needs higher authority

**Severity levels:**
- `low` — Minor issue, verbal warning
- `medium` — Formal warning, documentation
- `high` — Serious, possible removal from program
- `critical` — Emergency, immediate escalation

---

## Key Queries

### Students who haven't acknowledged latest handbook:

```sql
SELECT u.name, u.email
FROM users u
WHERE u.id NOT IN (
    SELECT ha.user_id
    FROM handbook_acknowledgements ha
    JOIN handbooks h ON h.id = ha.handbook_id
    WHERE h.is_active = 1
      AND h.version = (
          SELECT MAX(version) FROM handbooks WHERE id = h.id
      )
);
```

### Open incidents:

```sql
SELECT ir.id, ir.type, ir.severity, ir.incident_date,
       u.name AS student, ir.status
FROM incident_reports ir
JOIN registrations r ON r.id = ir.registration_id
JOIN mentees m ON m.id = r.mentee_id
JOIN users u ON u.id = m.user_id
WHERE ir.status NOT IN ('resolved', 'dismissed')
ORDER BY ir.severity DESC, ir.incident_date ASC;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `handbooks.created_by` | `users.id` | 01-auth |
| `handbook_acknowledgements.user_id` | `users.id` | 01-auth |
| `handbook_acknowledgements.handbook_id` | `handbooks.id` | 11-guidance |
| `incident_reports.registration_id` | `registrations.id` | 05-registration |
| `incident_reports.reported_by` | `users.id` | 01-auth |
| `incident_reports.resolved_by` | `users.id` | 01-auth |
