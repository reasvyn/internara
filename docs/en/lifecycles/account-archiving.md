# Account Archiving

**Event:** Archiving user accounts and preserving records for compliance.

**Phase:** 7 — Archiving

**Previous Event:** [Period Closing](period-closing.md)

**Next Event:** *(none — this is the terminal phase of the lifecycle)*

---

## Overview

Account archiving is the final phase of the user lifecycle. It moves accounts to a terminal ARCHIVED state where login is blocked, data is preserved, and no further transitions are possible. This is distinct from deletion — records remain in the database for compliance and reporting.

## Trigger

- End of academic period (graduating cohort)
- Student withdrawal or transfer
- Formal data retention policy requirements
- GDPR or institutional compliance deadlines

## Pre-conditions

- Internship period is **COMPLETED** (recommended)
- All assessments are finalized (recommended)
- User is logged in as Super Admin or Admin
- Target accounts are not PROTECTED (super admin accounts cannot be archived)

## Actors

| Actor | Role | Can archive | Can mass archive | Can delete |
|---|---|---|---|---|
| Super Admin | System administrator | Yes | Yes | Yes (via user manager) |
| Admin | School administrator | Yes | Yes | Yes (via user manager) |
| Student | — | No | No | No |

---

## What Archiving Does

| Aspect | Behavior |
|---|---|
| **Login** | Blocked — archived users cannot authenticate |
| **Data** | Preserved — all related records remain in the database |
| **Visibility** | Hidden from active user lists (but visible in filtered/search views) |
| **Reversal** | Not possible — ARCHIVED is a terminal state |
| **Deletion** | Separate operation — archive is not delete |

## What Is Preserved

When a user is archived, these records remain intact:

- **User identity** — name, email, username
- **Profile** — personal data, contact info, identifiers
- **Roles** — role assignments (historical)
- **Registrations** — internship registrations (historical)
- **Logbooks** — all journal entries
- **Submissions** — all assignment submissions
- **Attendance** — all attendance records
- **Assessments** — final scores and rubric data
- **Supervision logs** — mentor interaction records
- **Activity log** — audit trail entries

---

## Event A: Single Account Archival

### Flow

```
Admin → User Manager → Find User → Toggle Status → Archived
```

1. Navigate to the relevant user manager (All Users, Students, etc.)
2. Find the target user
3. The status toggle or account lifecycle manager transitions the status
4. `ToggleUserStatusAction` or direct status transition sets ARCHIVED

### From the Account Lifecycle Manager

```
Admin → Admin → Accounts → Lifecycle → Select User → Archive
```

The account lifecycle manager provides a dedicated interface with:
- User list with current status badges
- Lock/unlock/reinstate/archive buttons
- Status history
- Clone detection results

---

## Event B: Bulk Archival

### Flow (via Student Manager)

```
Admin → Users → Students → Apply Filters → Archive All Filtered
```

1. Navigate to **Admin → Users → Students**
2. Apply filters (department, academic year, registration status)
3. Click **Archive All Filtered**
4. `performMassAction` executes the archival on the filtered query
5. Each user's status is transitioned to ARCHIVED

### Flow (via User Manager Bulk Delete)

```
Admin → Users → All Users → Select Users → Delete Selected
```

The `deleteSelected` method:
1. Iterates selected users
2. Calls `DeleteUserAction` for each
3. Skips self-deletion

---

## Event C: GDPR Compliance

The system includes a `gdpr_deletion_logs` table for tracking formal deletion requests.

### Archival vs. Deletion

| | Archival | Deletion |
|---|---|---|
| **Data** | Preserved | Removed |
| **Reversible** | No (terminal) | No |
| **Login** | Blocked | Blocked |
| **Compliance** | Retains records | Full removal |
| **Use case** | Standard end-of-life | Formal GDPR request |

The `gdpr_deletion_logs` table tracks:
- Who requested the deletion
- When it was processed
- What data was affected
- Audit trail for compliance

---

## Event D: New Cycle Preparation

After archiving, the system is ready for a new internship cycle:

1. **Create new Academic Year** — set it as active
2. **Create new Internships** — start in DRAFT status
3. **Update Company partnerships** — refresh placement quotas
4. **Add new Users** — teachers, new student cohorts
5. **Repeat from Phase 2** — [Internship Creation](internship-creation.md)

Archived users remain in the database for reference but are excluded from active operations.

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **ARCHIVED is terminal** | `AccountStatus::isTerminal()` returns true for ARCHIVED |
| **No login** | `AccountStatus::allowsLogin()` returns false for ARCHIVED |
| **Protected accounts** | Super Admin (PROTECTED) accounts cannot be archived |
| **Data preserved** | All related records remain in the database |
| **Mass archival available** | Admin can archive filtered users in bulk |

## Seamless Connection

Account archiving is the final event in the lifecycle. After archiving:

- The system data is preserved for audit and compliance
- The institution can begin a new cycle at [Internship Creation](internship-creation.md)
- The full lifecycle repeats for the next cohort of students
