# Enrollment — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Student registration into programs, placement slot assignment, phase progression, and placement change requests.

For complete technical reference including API, models, actions, and components, see [enrollment-reference.md](enrollment-reference.md).

---

## Key Principles

- **Registration is the gateway** — students must register for a program before accessing any program features (logbook, attendance, assignments). Registration creates the `Mentee` role and activates the student in the system.
- **Placement bridges company slots to students** — each placement consumes one slot quota from a company. Capacity enforcement is atomic (increment/decrement never exceeds limit).
- **Phase progression is sequential** — students move through program phases in order. Each phase has completion criteria that must be met before advancing. Skipping is not allowed.
- **Guest applications feed into registration** — unauthenticated users can submit an intent-to-register form. Admin reviews and approves, which auto-creates User + Mentee + Registration records.

---

## Context Boundary

Depends on User (for student identity), Program (for internship specs and phases), Academics (for school/department context). Journals tracks phase activity. Assessment and Evaluation consume enrollment data for scoring.

---

## Domain Rules

- **Phases must be traversed sequentially**: Phase 1 → Phase 2 → Phase 3. Cannot skip or reorder. Each transition validates completion criteria.
- **Enrollment status drives dashboard and notifications**: active students see program features; archived students see read-only data.
- **Placement capacity is atomic**: slot quota uses database-level locking. Never oversells a company slot.
- **Direct placement (admin)**: admin can assign a student to a slot directly, which auto-creates the Mentee record and Registration in a single process action.
- **Placement change requests**: students can request slot changes with a reason. Admin reviews, approves, or rejects. Approved changes atomically decrement old slot and increment new slot.
- **Guest applications**: create an `AccountApplication` record. Admin approves or rejects. Approval triggers `RegisterStudentProcess` to create User + Mentee + Registration atomically.

---

## Aggregates

- **Registration**: Core enrollment record linking a student (Mentee) to a Program. Tracks phase progression status, start/end dates, and overall enrollment state.
- **AccountApplication**: Guest application form — personal data, school, program preferences. Admin review/approval creates the full User account.
- **RegistrationDocument**: Uploaded documents required by the program's DocumentRequirement rules. Validated against program requirements.
- **Placement**: Company slot assignment linking a student to a specific company slot within a program. Atomic capacity enforcement.
- **PlacementChangeRequest**: Student-initiated request to change placement. Admin workflow with approval/rejection and atomic slot rebalancing.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan enrollment:check-progress` | Verify phase progression integrity for all active enrollments |

---

## Error Handling & Failure Modes

- **Capacity exceeded**: If a placement is attempted on a fully booked slot, the atomic increment check throws a `RejectedException`. The UI shows "Slot is full."
- **Missing phase prerequisites**: Attempting to advance a phase without meeting completion criteria throws a `ValidationFailedException` with details on what's missing.
- **Guest application duplicate**: The system checks for existing pending applications from the same email. Duplicates are blocked with a `ConflictException`.
- **Registration for inactive program**: Programs must be in `PUBLISHED` or `ACTIVE` status to accept registrations. Attempts on `DRAFT`, `COMPLETED`, or `CANCELLED` programs are rejected.

---

## Quick References

### Actions & Business Logic
- **13** actions across all aggregates
- Registration create/verify, placement assign/change/approve, phase advance, guest application approve/reject, slot capacity management

### Data & Persistence
- **5** models: `Registration`, `AccountApplication`, `RegistrationDocument`, `Placement`, `PlacementChangeRequest`
- UUID PKs, `HasFactory`. `Placement` has atomic quota counter

### User Interface
- **9** Livewire components
- Registration wizard, application review page, placement manager, phase progression tracker, change request UI

### Authorization
- **5** policies
- Students manage own registrations, admins manage all registrations and placements

---

For complete technical reference, see [enrollment-reference.md](enrollment-reference.md).
