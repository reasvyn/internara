# 06 — Daily Execution

> **Lifecycle:** Daily clock-in → Absence request → Logbook entry → Mentor verification
> **Domains:** `Attendance`, `Logbook`
> **Tables:** 3 (`attendances`, `absence_requests`, `logbooks`)

---

## Purpose

Captures the day-to-day activities of students during their internship. Three parallel streams: attendance tracking (where/when), absence requests (exceptions), and logbook journaling (what they did/learned). All three are tied to a registration and have a strict one-entry-per-day constraint.

---

## Tables

### attendances

Daily clock-in/out with geo-location tracking.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), CAS | Student user |
| registration_id | varchar(36) | FK → registrations(id), CAS | Active registration |
| date | date | NOT NULL, idx | Calendar date |
| clock_in | time | NULLABLE | When they arrived |
| clock_out | time | NULLABLE | When they left |
| clock_in_ip | varchar(45) | NULLABLE | IP at clock-in |
| clock_out_ip | varchar(45) | NULLABLE | IP at clock-out |
| clock_in_latitude | decimal(10,8) | NULLABLE | Geo at clock-in |
| clock_in_longitude | decimal(11,8) | NULLABLE | Geo at clock-in |
| clock_out_latitude | decimal(10,8) | NULLABLE | Geo at clock-out |
| clock_out_longitude | decimal(11,8) | NULLABLE | Geo at clock-out |
| status | varchar(255) | idx, DEFAULT 'present' | 'present', 'late', 'absent', 'half_day' |
| is_verified | boolean | idx, DEFAULT false | Teacher/supervisor verified |
| verified_by | varchar(36) | FK → users(id), SNU | Who verified |
| verified_at | timestamp | NULLABLE | |
| notes | text | NULLABLE | Additional context |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `[user_id, date]`, on `registration_id`, composite on `[registration_id, date, status]`.

**Status values:**
- `present` — Clocked in on time
- `late` — Clocked in after threshold
- `absent` — No clock-in at all
- `half_day` — Left significantly early

**Unique constraint:** One attendance record per student per day (`[user_id, date]` UNIQUE). This prevents duplicate entries.

**Geo-tracking:** Coordinates are optional. Privacy concern — only collected when student clocks in/out via mobile.

### absence_requests

Formal absence requests with reason and optional attachment.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Student |
| registration_id | varchar(36) | FK → registrations(id) | Active registration |
| start_date | date | NOT NULL | First day of absence |
| end_date | date | NOT NULL | Last day of absence |
| reason_type | varchar(255) | NOT NULL | 'sick', 'personal', 'family', 'other' |
| reason_description | text | NOT NULL | Detailed explanation |
| attachment_path | varchar(255) | NULLABLE | Medical note, etc. |
| status | varchar(255) | DEFAULT 'pending' | 'pending' → 'approved' / 'rejected' |
| processed_by | varchar(36) | FK → users(id), SNU | Admin who reviewed |
| processed_at | timestamp | NULLABLE | |
| admin_notes | text | NULLABLE | Internal notes |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Duration:** `end_date` must be >= `start_date`. Can span multiple days.
**Overlap check:** Application-layer validation prevents overlapping absence requests.

### logbooks

Daily journal entries recording activities and learning outcomes.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), CAS | Student |
| registration_id | varchar(36) | FK → registrations(id), CAS | Active registration |
| date | date | NOT NULL | Entry date |
| content | text | NOT NULL | Daily activity description |
| learning_outcomes | text | NULLABLE | What the student learned |
| status | varchar(255) | idx, DEFAULT 'draft' | 'draft', 'submitted', 'verified' |
| is_verified | boolean | DEFAULT false | Mentor marked as reviewed |
| verified_by | varchar(36) | FK → users(id), SNU | Mentor who verified |
| verified_at | timestamp | NULLABLE | |
| mentor_feedback | text | NULLABLE | Mentor's comments |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `[user_id, date]`, on `registration_id`, on `status`, composite on `[registration_id, is_verified]`, composite on `[user_id, status]`.

**Status transitions:**
```
draft ──► submitted ──► verified
                │
                └──► draft (request revision, back to draft)
```

**Unique constraint:** One logbook entry per student per day (`[user_id, date]` UNIQUE).

---

## Cross-Table Relationships

```
registration
  │
  ├──1:*── attendance    (one per day)
  ├──1:*── absence_request (spans days)
  └──1:*── logbook       (one per day)
```

All three tables share the same pattern: `[user_id, registration_id]` identifies the student in their current program.

---

## Key Queries

### Today's attendance status:

```sql
SELECT u.name, a.clock_in, a.clock_out, a.status
FROM attendances a
JOIN users u ON u.id = a.user_id
WHERE a.date = CURDATE()
  AND a.registration_id = ?
ORDER BY u.name;
```

### Absence rate per student:

```sql
SELECT u.name,
       COUNT(DISTINCT ar.id) AS absence_count,
       COUNT(DISTINCT a.id) AS total_days
FROM registrations r
JOIN mentees m ON m.id = r.mentee_id
JOIN users u ON u.id = m.user_id
LEFT JOIN attendances a ON a.registration_id = r.id
LEFT JOIN absence_requests ar ON ar.registration_id = r.id
WHERE r.id = ?;
```

### Unverified logbooks (for mentor dashboard):

```sql
SELECT l.id, u.name, l.date, l.created_at
FROM logbooks l
JOIN users u ON u.id = l.user_id
WHERE l.is_verified = 0
  AND l.status = 'submitted'
  AND l.registration_id IN (
    SELECT rm.registration_id
    FROM registration_mentor rm
    WHERE rm.mentor_id = ?
  )
ORDER BY l.date DESC;
```

---

## Data Growth Considerations

| Table | Daily Rows | Annual Estimate | Retention |
|---|---|---|---|
| `attendances` | 1 per active student | ~25K (100 students × 250 days) | Permanent |
| `absence_requests` | Rare | ~500 | Permanent |
| `logbooks` | 1 per active student | ~25K | Permanent |

These three tables grow linearly with student count. At 1,000 students/year, expect ~250K rows/year for `attendances` and `logbooks` each. Indexes on `[registration_id, date]` keep common queries efficient.
