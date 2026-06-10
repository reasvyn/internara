# Journals

> **Last updated:** 2026-06-10

Daily activity tracking: logbook entries with mentor verification, attendance with clock-in/out, absence requests with approval workflow, and schedule management.

## Purpose & Boundary

Journals is the operational hub for daily internship activities. Students record daily logbook entries (one per calendar day), clock attendance in and out, request absences with justification, and view their work schedules. Industry supervisors review and verify logbook entries. Teachers have fallback verification capability when supervisors are inactive.

Out of scope: rubric-based competency assessment (Assessment), task assignments (Assignment), final grade compilation (Reports).

## Submodules

### Logbook
Daily journal entry: date, activities performed, learnings, challenges, future plans, and optional file attachments. Status workflow: `draft` → `submitted` → `verified` (by supervisor or teacher fallback). Mentor can return to `draft` via `revision_required`. Exactly one entry per student per calendar day — duplicates return `ConflictException`. Compliance monitoring notifies mentors after N days (default 3) of missing entries.

### Attendance
Clock-in/clock-out records with auto-computed duration and optional GPS metadata. Records become immutable after a configurable grace period (default 24 hours from clock-out). Admin override available for corrections. Duration calculations power compliance monitoring and attendance reports.

### AbsenceRequest
Planned or unplanned absence submission with reason, optional supporting documents, and date range. Single-day absences approved by mentor. Extended absences (configurable threshold, default 3+ days) require secondary approval from coordinator. Unapproved absences auto-escalate after program grace period.

### Schedule
Program event management: title, description, start/end times, location, category. Supports recurring events (daily, weekly, biweekly, monthly). Conflict detection warns on overlapping events but does not block creation. Past events are immutable — corrections require cancellation and recreation.

## Key Concepts

### Teacher Fallback Verification

To prevent blocking student workflows when industry supervisors are inactive, school teachers can bypass the supervisor queue after 48 hours. Entries verified via fallback are tagged `verified_by_fallback` in audit logs. This ensures logbook progression is never blocked by supervisor unavailability.

### Compliance Monitoring

If a student has no logbook entry for N consecutive days (default 3), the mentor receives a notification. At N+2 days, the program coordinator is also notified. The `journals:check-compliance` command runs this check on demand or via the scheduler.

### One-Entry-Per-Day Enforcement

The system enforces exactly one logbook entry per calendar day per student at the database level (unique constraint on `student_id` + `date`). This prevents duplicate entries and ensures a clean daily record.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- Program (schedule templates, compliance thresholds)
- User (student, mentor, supervisor identity)

## Used By

- Evaluation (logbook and attendance data for scoring)
- Reports (attendance and logbook compliance for grade card)
