# Enrollment — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Removed references to the eliminated `Mentee` record/role, linking registrations directly to student user accounts.

Student registration into programs, placement slot assignment, and placement change requests.

For complete technical reference including API, models, actions, and components, see [enrollment-reference.md](enrollment-reference.md).

---

## Key Principles

- **Registration is the gateway** — Students must register for a program before accessing any program features (logbook, attendance, assignments). Registration activates the student's program access.
- **Placement bridges company slots to students** — Each placement consumes one slot quota from a company. Capacity enforcement is atomic (increment/decrement never exceeds limit).
- **Guest applications feed into registration** — Unauthenticated users can submit an intent-to-register form. Admin reviews and approves, which auto-creates User and Registration records.

---

## Context Boundary

Depends on **User** (for student identity), **Program** (for internship specs and weights), and **Academics** (for department calendar context). **Journals** tracks student operation activity. **Assessment** and **Reports** compile grades from registrations.

---

## Module Rules

- **Enrollment status drives dashboard access:** Active students see program features; archived or pending students see read-only or restricted screens.
- **Placement capacity is atomic:** Slot quota uses database-level locking, ensuring placements never oversell a company slot.
- **Direct placement (admin):** Admins can assign a student to a slot directly, which creates the Registration in a single transaction.
- **Placement change requests:** Students can request slot changes. Admins review, approve, or reject. Approved changes atomically decrement the old slot quota and increment the new slot quota.
- **Guest applications:** Create an `AccountApplication` record. Admin approval triggers the student registration process to create User and Registration atomically.

---

## Submodules

- **Registration**: Core enrollment record linking a student user to a Program. Tracks start/end dates, placements, cohort group assignments, and overall enrollment state.
- **AccountApplication**: Guest application form. Admin review and approval provisions user credentials.
- **RegistrationDocument**: Verifies documents uploaded against program requirements.
- **Placement**: Company slot assignment linking a student to a specific company slot within a program.
- **PlacementChangeRequest**: Student-initiated request to change placement due to workplace conflicts.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan enrollment:check-progress` | Verify placement integrity for all active enrollments |

---

## Error Handling & Failure Modes

- **Capacity exceeded**: Placements attempted on fully booked slots throw a `RejectedException` ("Slot is full").
- **Guest application duplicate**: Existing pending applications from the same email block new submissions with a `ConflictException`.
- **Registration for inactive program**: Programs must be in `ACTIVE` status to accept registrations. Attempts on `DRAFT` or `CLOSED` programs are rejected.

---

## Quick References

### Actions & Business Logic
- **13** actions across the module:
  - Registration create/verify, placement assign/change/approve, guest application approve/reject, slot capacity management.

### Data & Persistence
- **5** models: `Registration`, `AccountApplication`, `RegistrationDocument`, `Placement`, `PlacementChangeRequest`.
- UUID PKs. `Placement` has database-level quota counter locks.

### User Interface
- **9** Livewire components:
  - Registration wizard, application review page, placement manager, registration verification dashboard, change request management tables.

### Authorization
- **5** policies: `RegistrationPolicy`, `AccountApplicationPolicy`, `RegistrationDocumentPolicy`, `PlacementPolicy`, `PlacementChangeRequestPolicy`.

---

For complete technical reference, see [enrollment-reference.md](enrollment-reference.md).
