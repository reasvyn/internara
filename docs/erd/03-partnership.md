# 03 — Companies & Partnerships

> **Lifecycle:** Company registration → Partnership agreement → Placement slot creation
> **Domains:** `Partnership`, `Placement` (partially)
> **Tables:** 3 (`companies`, `partnerships`, `placements`)

---

## Purpose

Manages relationships with external organizations. Companies are the industry partners that host students. Partnerships are formal agreements (MoU) defining cooperation scope. Placements are quota-based slots within a company for a specific internship program.

---

## Tables

### companies

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | NOT NULL | Company legal name |
| address | text | NULLABLE | Physical/registered address |
| phone | varchar(255) | NULLABLE | |
| email | varchar(255) | NULLABLE | Official contact email |
| website | varchar(255) | NULLABLE | |
| description | text | NULLABLE | Company profile / industry focus |
| industry_sector | varchar(255) | NULLABLE | e.g., 'Technology', 'Manufacturing' |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Foreign Keys:** Referenced by `partnerships.company_id`, `placements.company_id`.

### partnerships

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| company_id | varchar(36) | FK → companies(id), CAS | Partner company |
| agreement_number | varchar(255) | NOT NULL | Official MoU reference number |
| title | varchar(255) | NOT NULL | Agreement title |
| start_date | date | NOT NULL | MoU effective date |
| end_date | date | NOT NULL | MoU expiry date |
| status | varchar(255) | DEFAULT 'active' | 'active', 'expired', 'terminated', 'renewed' |
| scope | text | NULLABLE | Agreement scope and terms |
| contact_person_name | varchar(255) | NULLABLE | Company-side contact |
| contact_person_phone | varchar(255) | NULLABLE | |
| contact_person_email | varchar(255) | NULLABLE | |
| signed_by_school | varchar(255) | NULLABLE | School representative name |
| signed_by_company | varchar(255) | NULLABLE | Company representative name |
| signed_at | date | NULLABLE | Signing date |
| notes | text | NULLABLE | Internal notes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status values:**
- `active` — Currently valid
- `expired` — Past end_date
- `terminated` — Early termination
- `renewed` — Replaced by a newer agreement

### placements

Placement slots define how many students a company can host for a specific internship program.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| company_id | varchar(36) | FK → companies(id), CAS | Host company |
| internship_id | varchar(36) | FK → internships(id), CAS | Parent program |
| name | varchar(255) | NOT NULL | Position/slot name (e.g., "Frontend Developer") |
| address | text | NULLABLE | Work location (may differ from company address) |
| quota | integer | DEFAULT 1 | Maximum students for this slot |
| filled_quota | integer | DEFAULT 0 | Current assignments counter |
| description | text | NULLABLE | Role description, requirements |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** `filled_quota` must never exceed `quota`. Enforced in application layer via `Placement` Action.

---

## Key Queries

### Find companies with available slots:

```sql
SELECT c.name, p.name AS position,
       (p.quota - p.filled_quota) AS available
FROM placements p
JOIN companies c ON c.id = p.company_id
WHERE p.quota > p.filled_quota
  AND p.internship_id = ?
ORDER BY available DESC;
```

### Get active partnerships (not expired):

```sql
SELECT c.name, p.agreement_number, p.end_date
FROM partnerships p
JOIN companies c ON c.id = p.company_id
WHERE p.status = 'active'
  AND p.end_date >= CURDATE()
ORDER BY p.end_date;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `companies.id` | `partnerships.company_id` | 03-partnership |
| `companies.id` | `placements.company_id` | 03-partnership |
| `placements.id` | `registrations.placement_id` | 05-registration |
| `placements.id` | `placement_change_requests.to_placement_id` | 05-registration |
| `placements.id` | `placement_change_requests.from_placement_id` | 05-registration |
| `placements.internship_id` | `internships.id` | 04-internship |
