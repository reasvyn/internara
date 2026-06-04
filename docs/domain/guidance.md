# Guidance — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Mentoring relationships, supervision logs, handbooks, and acknowledgement tracking.

For complete technical reference including API, models, actions, and components, see [guidance-reference.md](guidance-reference.md).

---

## Key Principles

- **Mentors guide, supervisors oversee** — Teachers act as academic mentors (guidance, grading, logbook review). Company contacts act as workplace supervisors (daily oversight, attendance verification, evaluation input). A student has one of each.
- **Handbooks are mandatory reading** — Before starting a program, students must read and acknowledge handbooks. Acknowledgements are immutable (user, timestamp, IP recorded).
- **Supervision logs are private notes** — Mentors write observations, concerns, and action items. These are visible only to the mentor and admins — not to students.
- **Mentee activation is automatic** — When a student is placed in a program via Enrollment's placement system, the Mentee record is created automatically. No manual activation needed.

---

## Context Boundary

Links students (from User domain) with mentors and supervisors. Program domain provides the program context for mentoring relationships. Journals logs the daily activity that mentors review. SysAdmin manages role assignments that determine who can be a mentor.

---

## Domain Rules

- **Each student has exactly one primary mentor** (teacher) per program. The mentor is assigned during registration verification.
- **Each placement has one company supervisor** assigned by the partner company.
- **Handbook acknowledgement is required** before a student can begin phase 1 activities (logbook, attendance).
- **Supervision logs are immutable** — once written, they cannot be edited or deleted. Corrections require a new log entry referencing the original.
- **Handbooks are role-filtered**: can be targeted to all users, students only, teachers only, or supervisors only.
- **Acknowledgements are single-use per handbook version**: a student acknowledges each handbook version once. Re-publishing a handbook requires re-acknowledgement.

---

## Aggregates

- **Mentee**: Student role activation record linked to a User. Tracks which program the student is enrolled in and who their mentor is.
- **Mentor**: Teacher or supervisor assigned as a guide. Tracks relationship type (academic mentor vs workplace supervisor) and active period.
- **SupervisionLog**: Private notes written by mentors — observations, concerns, action items. Immutable after creation. Searchable and filterable by the mentor.
- **Handbook**: Versioned documents with Markdown content, role targeting, active/inactive status. Supports re-publishing with new version numbers.
- **HandbookAcknowledgement**: Immutable record of user + handbook + timestamp + IP. One per user per handbook version.

---

## Error Handling & Failure Modes

- **Missing mentor assignment**: If a student has no mentor when trying to access guidance features, the system shows a clear message directing them to contact an admin.
- **Unacknowledged handbook**: If a student tries to start their logbook before acknowledging a required handbook, the system blocks the action with a "Handbook acknowledgement required" error. A direct link to the handbook page is provided.
- **Editing an immutable supervision log**: The system rejects PUT/PATCH requests on supervision logs with a `RejectedException`. The UI disables the edit button for written entries.

---

## Quick References

### Actions & Business Logic
- **15** actions across all aggregates
- Mentor/mentee assignment, supervision log CRUD, handbook management, acknowledgement recording, role filtering

### Data & Persistence
- **5** models: `Mentee`, `Mentor`, `SupervisionLog`, `Handbook`, `HandbookAcknowledgement`
- UUID PKs. `SupervisionLog` is immutable after creation. `HandbookAcknowledgement` is append-only

### User Interface
- **9** Livewire components
- Mentee dashboard, supervision log manager, handbook reader, acknowledgement status tracker

### Authorization
- **4** policies
- Mentors manage own supervision logs, admins manage handbooks and assignments, students read own mentor info

---

For complete technical reference, see [guidance-reference.md](guidance-reference.md).
