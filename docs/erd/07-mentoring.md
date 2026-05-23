# 07 — Mentoring & Teams

> **Lifecycle:** Mentor registration → Team assignment → Supervision visits → Verification
> **Domains:** `Mentor`
> **Tables:** 4 (`mentors`, `teams`, `team_user`, `supervision_logs`)

---

## Purpose

Manages mentor roles (teachers and industry supervisors), team groupings, and supervision visit records. Mentors are linked to registrations via the `registration_mentor` pivot (defined in [05-registration.md](05-registration.md)).

Two types of mentors exist (stored in `mentors.type`):
- **School mentor** — Teacher/school-side supervisor
- **Industry mentor** — Company-side on-site supervisor

---

## Tables

### mentors

Role activation — extends `User` with mentoring capabilities.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), idx | Backing user account |
| type | varchar(255) | NOT NULL | 'school_mentor' or 'industry_mentor' |
| is_active | boolean | DEFAULT true | Toggle without deleting |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraint:** A user can be both a `mentor` and a `mentee` (unusual but possible for teacher training programs).

**Referenced by:** `registration_mentor.mentor_id` (pivot to registration).

### teams

Groupings of users (mentors + mentees).

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| name | varchar(255) | NOT NULL | Team name (e.g., "Group A", "SME Corps") |
| description | text | NULLABLE | |
| owner_id | varchar(36) | FK → users(id), SNU | Team leader/creator |
| is_active | boolean | DEFAULT true | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Owner:** `owner_id` references `users`, not `mentors`. This allows non-mentor users (e.g., admins) to own teams.

### team_user

Pivot: Team ↔ User membership with role designation.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| team_id | varchar(36) | FK → teams(id), CAS, PK | |
| user_id | varchar(36) | FK → users(id), CAS, PK | |
| role | varchar(255) | DEFAULT 'mentee' | 'mentor', 'mentee' |
| assigned_by | varchar(36) | FK → users(id), SNU | Who added them |
| assigned_at | datetime | DEFAULT CURRENT_TIMESTAMP | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Composite primary key:** `[team_id, user_id]`.
**Role filter:** Eloquent relationships use `wherePivot('role', 'mentor')` to scope team members by role.

### supervision_logs

Records of supervision visits/meetings between mentors and students.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| registration_id | varchar(36) | FK → registrations(id), CAS | Student registration |
| supervisor_id | varchar(36) | FK → users(id) | Mentor who conducted the visit |
| type | varchar(255) | NOT NULL | 'visit', 'meeting', 'call', 'email' |
| date | date | NOT NULL | When the supervision occurred |
| topic | varchar(255) | NULLABLE | What was discussed |
| notes | text | NOT NULL | Detailed supervision notes |
| status | varchar(255) | DEFAULT 'pending' | 'pending', 'verified', 'flagged' |
| is_verified | boolean | DEFAULT false | Admin verified |
| verified_at | timestamp | NULLABLE | |
| attachment_path | varchar(255) | NULLABLE | Supporting document |
| created_at | timestamp | | |
| updated_at | timestamp | | |

---

## Key Queries

### All students supervised by a mentor:

```sql
SELECT u.name AS student, i.name AS program,
       c.name AS company, r.status
FROM registration_mentor rm
JOIN registrations r ON r.id = rm.registration_id
JOIN mentees m ON m.id = r.mentee_id
JOIN users u ON u.id = m.user_id
JOIN internships i ON i.id = r.internship_id
LEFT JOIN placements p ON p.id = r.placement_id
LEFT JOIN companies c ON c.id = p.company_id
WHERE rm.mentor_id = ?
ORDER BY u.name;
```

### Recent supervision visits for a registration:

```sql
SELECT sl.date, sl.type, sl.topic, sl.status,
       u.name AS supervisor
FROM supervision_logs sl
JOIN users u ON u.id = sl.supervisor_id
WHERE sl.registration_id = ?
ORDER BY sl.date DESC;
```

### Team composition:

```sql
SELECT tu.role, u.name, u.email
FROM team_user tu
JOIN users u ON u.id = tu.user_id
WHERE tu.team_id = ?
ORDER BY tu.role, u.name;
```

---

## Cross-Lifecycle References

| Column | References | Lifecycle |
|---|---|---|
| `mentors.user_id` | `users.id` | 01-auth |
| `mentors.id` | `registration_mentor.mentor_id` | 05-registration |
| `supervision_logs.registration_id` | `registrations.id` | 05-registration |
| `supervision_logs.supervisor_id` | `users.id` | 01-auth |
| `teams.owner_id` | `users.id` | 01-auth |
| `team_user.user_id` | `users.id` | 01-auth |
