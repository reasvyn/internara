# Journals — Logbooks, Attendance, Absence & Monitoring Visits

## Description

Daily activity tracking: logbook entries with mentor verification, attendance with clock-in/out, and
absence requests with approval workflow.

## Purpose & Boundary

Journals is the operational hub for daily internship activities. Students record daily logbook
entries (one per calendar day), clock attendance in and out, and request absences with
justification. Industry supervisors review and verify logbook entries. Teachers have Cross-Role
Proxy verification capability when supervisors are inactive.

Out of scope: rubric-based competency assessment (Assessment), task assignments (Assignment), final
grade compilation (Reports).

## Submodules

### Logbook

Daily journal entry: date, activities performed, learnings, challenges, future plans, and optional
file attachments. Status workflow: `draft` → `submitted` → `verified` (by supervisor or teacher via
Cross-Role Proxy). Mentor can return to `draft` via `revision_required`. Exactly one entry per
student per calendar day — duplicates return `RejectedException`. Compliance monitoring notifies
mentors after N days (default 3) of missing entries.

### Attendance

Clock-in/clock-out records with auto-computed duration and optional GPS metadata. Records become
immutable after a configurable grace period (default 24 hours from clock-out). Admin override
available for corrections. Duration calculations power compliance monitoring and attendance reports.

### AbsenceRequest

Planned or unplanned absence submission with reason, optional supporting documents, and date range.
Single-day absences approved by mentor. Extended absences (configurable threshold, default 3+ days)
require secondary approval from coordinator. Unapproved absences auto-escalate after program grace
period.

### SupervisionLog

Student mentoring session records reviewed by industry supervisors. Students log mentoring topics and
learnings; supervisors review and provide feedback with optional revision requests. Status workflow:
`draft` → `submitted` → `reviewed` → `acknowledged`. Supervisor can request revision at any review
stage, returning the entry to `submitted`. Teacher Cross-Role Proxy applies after 48h supervisor
inactivity window.

### MonitoringVisit

Field monitoring visit scheduling, verification, and tracking. Supports multiple visit methods
(site visit, virtual meeting, phone call), location tracking, and supervisor verification workflow.
Visit status progresses through scheduled → verified. Linked to a registration and requires teacher
assignment.

## Key Concepts

### Cross-Role Proxy Verification

To prevent blocking student workflows when industry supervisors are inactive, school teachers can
activate Cross-Role Proxy (see [4](../../adr/adr-cross-role-proxy.md)) to verify entries via the
mentor proxy bridge. Entries verified via proxy are tagged with `proxy_role = 'supervisor'` in the
activity log. This ensures logbook progression is never blocked by supervisor unavailability.

### Compliance Monitoring

If a student has no logbook entry for N consecutive days (default 3), the mentor receives a
notification. At N+2 days, the program coordinator is also notified. The `journals:check-compliance`
command runs this check on demand or via the scheduler.

### One-Entry-Per-Day Enforcement

The system enforces exactly one logbook entry per calendar day per student at the database level
(unique constraint on `student_id` + `date`). This prevents duplicate entries and ensures a clean
daily record.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- Program (schedule templates, compliance thresholds)
- User (student, mentor, supervisor identity)

## Used By

- Evaluation (logbook, attendance, and supervision log data for scoring)
- Reports (attendance, logbook compliance, supervision log, and visit compliance data for grade card)

## Design Principles

- **One entry per student per calendar day** — the database enforces a unique constraint on `student_id + date` for logbook entries. This prevents duplicate records and ensures a clean daily audit trail. Any attempt to create a second entry for the same day throws `RejectedException`.
- **Cross-Role Proxy is a default, not an exception** — teacher-as-supervisor verification must be explicit and tagged (`proxy_role = 'supervisor'` in the activity log). The 48-hour inactivity window before proxy activates is a configuration default, not a hard rule. Logbook progression must never permanently stall due to supervisor unavailability.
- **Attendance becomes immutable after the grace period** — clock-in/out records are locked after a configurable grace period (default 24 hours from clock-out). Admin override is available for corrections, but passive auto-lock ensures the attendance record is the primary source of truth for compliance calculations.
- **Compliance monitoring is proactive, not reactive** — the `journals:check-compliance` command runs on a schedule and sends escalating notifications (mentor at N days → coordinator at N+2 days). Students who fall behind receive no silent pass; the system surfaces the gap, not the teacher.

## How It Works

*Content to be added — verify against actual implementation.*
