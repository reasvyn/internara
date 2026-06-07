# Journals — Documentation Overview

> Last updated: 2026-06-04 Changes: Rewrote overview with developer-friendly content, added error
> handling, failure modes, and CLI commands

Daily activity tracking: logbook entries, attendance with clock-in/out, absence requests, and
schedule management.

For complete technical reference including API, models, actions, and components, see
[journals-reference.md](journals-reference.md).

---

## Key Principles

- **One logbook entry per day** — students write one daily entry covering activities, learnings,
  challenges, and plans. The system enforces a maximum of one entry per calendar day per student.
- **Logbook has a draft→submit→verify workflow** — entries start in DRAFT, move to SUBMITTED
  (student submits), then VERIFIED (mentor acknowledges). Mentor can request REVISION_REQUIRED which
  sends it back to DRAFT.
- **Dual Mentor Fallback & Optionality** — Industry supervisors verify daily journals, but to avoid
  blocking student workflows, all supervisor actions are optional. If a supervisor is inactive, the
  assigned school teacher can bypass the supervisor queue and verify the journal entry directly.
- **Attendance is timestamp-based** — students clock in and out. Duration is auto-computed. Optional
  GPS data can be attached. Records are immutable after a configurable window (default 24h).
- **Absence requires approval** — planned or unplanned absences must be submitted with a reason.
  Single-day absences are approved by the mentor. Extended absences require additional approval.
- **Schedules define work expectations** — admins create schedules with recurring events. Students
  see their weekly work plan. Conflicts are detected and warned.

---

## Context Boundary

Tracks activity within an enrollment (student + program). Enrollment provides the registration
context. Program defines the schedule template. Evaluation consumes logbook and attendance data for
scoring. SysAdmin configures compliance thresholds (e.g., N days without entry triggers
notification).

---

## Module Rules

- **One logbook entry per calendar day per student.** Attempting to create a second entry returns a
  `ConflictException`.
- **Logbook draft workflow**: DRAFT → SUBMITTED → VERIFIED (or FINALIZED). Industry supervisor
  approves SUBMITTED.
- **Teacher Verification Bypass**: If an entry remains in `SUBMITTED` for more than 48 hours without
  supervisor action, the school teacher can bypass the supervisor and directly sign off/finalize the
  entry (tagged as `verified_by_fallback` in audit logs).
- **Attendance records are immutable after the configurable window** (default 24 hours from
  clock-out). Corrections require admin override.
- **Absence requests** require a reason. Extended absences (configurable threshold, default 3+ days)
  require secondary approval from a coordinator.
- **Compliance monitoring**: if a student has no logbook entry for N days (default 3), the mentor is
  notified. At N+2 days, the program coordinator is also notified.
- **Schedule conflict detection**: overlapping events within the same program trigger a warning but
  do not block creation.
- **Past events are immutable**: corrections to past schedule events require cancellation +
  recreation.

---

## Submodules

- **Logbook**: Daily journal entry — date, activities, learnings, challenges, plans, attachments.
  Draft workflow with mentor review. Calendar view with color coding (green=verified,
  yellow=submitted, blue=draft, gray=no entry).
- **Attendance**: Clock-in/out records with timestamp, optional GPS, auto-computed duration.
  Immutable after grace period. Mentor compliance monitoring.
- **AbsenceRequest**: Planned or unplanned absence with reason, optional documents. Single-day
  approval by mentor, extended approval escalates.
- **Schedule**: Program events with title, description, times, location, category. Recurring events
  (daily/weekly/biweekly/monthly). Conflict detection. Past events immutable.
- **IndustryAssessment**: Assessment of student by industry supervisor (separate from the Assessment
  module's rubric-based evaluations).

---

## CLI Commands

| Command                                 | Purpose                                                                      |
| --------------------------------------- | ---------------------------------------------------------------------------- |
| `php artisan journals:check-compliance` | Run compliance check — notify mentors of students missing N+ days of entries |

---

## Error Handling & Failure Modes

- **Duplicate logbook entry**: Student tries to write a second entry for the same date. Blocked with
  "You have already written a logbook entry for this date."
- **Late clock-out**: If a student forgets to clock out, the attendance record stays open. Admin can
  force-close the record with a manual time.
- **Absence without approval**: Unapproved absences are marked as `PENDING`. If not approved within
  the program's grace period, they auto-escalate to the admin.
- **Immutable attendance edit**: Attempting to modify an attendance record after the 24h window
  throws a `RejectedException`. The UI disables editing for past records.

---

## Quick References

### Actions & Business Logic

- **17** actions across all submodules
- Logbook CRUD, submit/verify/revision workflow, clock-in/out, absence request/approve, schedule
  CRUD, industry assessment

### Data & Persistence

- **5** models: `Logbook`, `Attendance`, `AbsenceRequest`, `Schedule`, `IndustryAssessment`
- UUID PKs. `Logbook` has unique constraint on (student_id, date). `Attendance` has
  clock_in/clock_out timestamps

### User Interface

- **7** Livewire components
- Logbook entry form, calendar view, attendance clock-in widget, absence request form, schedule
  manager

### Authorization

- **3** policies
- Students manage own entries, mentors review/verify assigned students, admins manage schedules and
  overrides

---

For complete technical reference, see [journals-reference.md](journals-reference.md).
