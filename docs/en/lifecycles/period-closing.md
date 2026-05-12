# Period Closing

**Event:** Concluding an internship period and locking all associated activities.

**Phase:** 6 — Period Closing

**Previous Event:** [Assessment & Scoring](assessment-scoring.md)

**Next Events:** [Report Generation](report-generation.md), [Account Archiving](account-archiving.md)

---

## Overview

Period closing concludes the active phase of an internship program. It transitions the internship to a terminal state (COMPLETED), locks all operational activities, and prepares the system for reporting and archival. This is a critical checkpoint that should only be performed when all outstanding items are resolved.

## Trigger

- Internship end date has passed
- All assessments are finalized
- All submissions are graded
- Admin or Teacher initiates the close

## Pre-conditions

- All student assessments are **finalized** (recommended)
- All assignment submissions are **graded** or **verified** (recommended)
- All supervision logs are **verified** (recommended)
- All attendance records are reconciled (recommended)
- No pending verifications (recommended)
- User is logged in as Super Admin, Admin, or Teacher

## Actors

| Actor | Role | Can close individual | Can batch close |
|---|---|---|---|
| Super Admin | System administrator | Yes | Yes |
| Admin | School administrator | Yes | Yes |
| Teacher | Academic supervisor | Yes (own internships) | No |

---

## Event A: Closing a Single Internship

### Flow

```
Admin → Internships → Select → Edit → Set Status to Completed → Save
```

The `UpdateInternshipAction` transitions the internship from **ACTIVE → COMPLETED**.

### Validation

The system validates the status transition via `InternshipStatus::canTransitionTo()`:
- ACTIVE → COMPLETED is a valid transition
- COMPLETED is **terminal** — no further transitions possible

### Post-Close Effects

Once the internship is COMPLETED:

| Feature | Behavior after close |
|---|---|
| **Logbook** | No new entries can be created or submitted |
| **Attendance** | No new records can be created |
| **Assignments** | No new submissions accepted |
| **Submissions** | Existing are read-only |
| **Supervision** | No new logs |
| **Assessment** | Existing are read-only (if finalized) |
| **Registration** | No new registrations accepted |
| **Placement** | Placement quotas are locked |

---

## Event B: Batch Closing

### Flow

```
Admin → Internships → Apply Filters → Close All Filtered
```

For mass closure across multiple internships:

1. Apply filters (academic year, date range, status, department, etc.)
2. Click **Close All Filtered**
3. `BatchUpdateInternshipStatusAction` executes:
   - Updates all matching internships to COMPLETED
   - Only internships in ACTIVE status are affected
   - Each transition is validated individually

---

## Event C: Pre-Close Checklist

Before closing, the system should verify:

### Recommended Checklist

See the [System Lifecycle pre-close integrity check](system-lifecycle.md#pre-close-system-integrity-check-recommended) for the recommended items to verify before closing. This checklist is a best practice, not enforced by the system.

---

## Event D: Handling Edge Cases

### What if a student has incomplete items?

The internship can still be closed. Incomplete items remain in the database but become read-only. The admin should:

1. Document any incomplete items
2. Handle exceptions outside the system if needed
3. The student's registration status can be transitioned separately

### What if an internship has no placements?

The internship can be closed normally. Placements are not required for the close operation.

### What if an academic year needs to end?

1. Close all internships under the academic year
2. Optionally archive user accounts
3. Set a new academic year as active
4. Start the planning phase for the new period

---

## State Changes

| Component | Before | After |
|---|---|---|
| Internship | ACTIVE | COMPLETED (terminal) |
| All linked records | Writable | Read-only (effectively) |
| Registrations | Active | Can be transitioned individually |
| Activity log | — | Audit entry for close operation |

## Seamless Connection

After closing:

- **[Report Generation](report-generation.md)** — generate completion reports, student transcripts, and institutional summaries
- **[Account Archiving](account-archiving.md)** — archive graduated students and prepare for the next cycle
