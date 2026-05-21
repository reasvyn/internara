# 02 — Institutional Setup

> **Lifecycle:** Institution registration → Department creation → Academic calendar → System configuration
> **Domains:** `School`, `Settings`, `Setup`
> **Tables:** 5 (`schools`, `departments`, `academic_years`, `settings`, `setups`)

---

## Purpose

Defines the educational institution structure. Schools are the top-level organizational unit. Departments are sub-units (study programs/jurusan). Academic years define the temporal boundaries for internship programs. Settings and setups handle application-level configuration and first-run installation.

No table in this lifecycle references any table outside `02-institution` and `01-auth`.

---

## Tables

### schools

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| institutional_code | varchar(255) | UNIQUE, idx, NOT NULL | Government/national institution code (NPSN) |
| name | varchar(255) | UNIQUE, NOT NULL | Full institution name |
| address | text | NULLABLE | Physical location |
| email | varchar(255) | UNIQUE, NULLABLE | Official contact |
| phone | varchar(255) | NULLABLE | |
| website | varchar(255) | NULLABLE | |
| fax | varchar(255) | NULLABLE | |
| principal_name | varchar(255) | NULLABLE | Head of institution |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `institutional_code`, UNIQUE on `name`, UNIQUE on `email`, index on `institutional_code`.

**Foreign Keys:** Referenced by `departments.school_id`, `profiles.school_id`, `account_applications.school_id`.

**Data lifecycle:** Schools are created during setup. Rarely deleted. Deactivation via `is_active` if needed.

### departments

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | UNIQUE, NOT NULL | Department name (e.g., "Rekayasa Perangkat Lunak") |
| description | text | NULLABLE | |
| school_id | varchar(36) | FK → schools(id), SNU, idx | Parent institution. Set null if school removed |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Foreign Keys:** `school_id` → `schools(id)` ON DELETE SET NULL.

**Referenced by:** `profiles.department_id`, `account_applications.department_id`.

### academic_years

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | UNIQUE, NOT NULL | e.g., "2025/2026" or "2025-2026 Ganjil" |
| start_date | date | NOT NULL | First day of academic period |
| end_date | date | NOT NULL | Last day of academic period |
| is_active | boolean | DEFAULT false | Only one year should be active at a time |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `name`.

**Foreign Keys:** Referenced by `internships.academic_year_id`, `assessments.academic_year_id`.

**Data lifecycle:** Created annually before internship registration opens. Only one year should be active. Students register under the active `academic_years.name` string (copied into `registrations.academic_year` as a snapshot).

### settings

Key-value configuration store. Not backed by a model — accessed via `setting()` helper.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| key | varchar(255) | NOT NULL | Dot-notation key (e.g., `app_name`, `primary_color`) |
| value | text | NULLABLE | Serialized value |
| type | varchar(255) | DEFAULT 'string' | 'string', 'boolean', 'integer', 'json' |
| description | text | NULLABLE | Developer-facing explanation |
| group | varchar(255) | NULLABLE | Category for admin UI grouping |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** None. Key lookups use full table scan (small table).

### setups

Application installation and provisioning state. Only one row ever exists.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| is_installed | boolean | DEFAULT false | True after setup wizard completes |
| setup_token | varchar(255) | NULLABLE | One-time access token for setup routes |
| token_expires_at | timestamp | NULLABLE | Setup window expiration |
| completed_steps | text | NULLABLE | JSON array of completed wizard step keys |
| school_id | varchar(255) | NULLABLE | Selected school during setup |
| department_id | varchar(255) | NULLABLE | Selected department during setup |
| recovery_key | text | NULLABLE | Encrypted recovery key for super admin access |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

## Key Queries

### Get active academic year:

```sql
SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1;
```

### Find all departments for a school:

```sql
SELECT d.* FROM departments d
JOIN schools s ON s.id = d.school_id
WHERE s.institutional_code = 'NPSN001';
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `schools.id` | `profiles.school_id` | 01-auth |
| `schools.id` | `departments.school_id` | 02-institution |
| `departments.id` | `profiles.department_id` | 01-auth |
| `academic_years.id` | `internships.academic_year_id` | 04-internship |
| `academic_years.id` | `assessments.academic_year_id` | 09-assessment |
