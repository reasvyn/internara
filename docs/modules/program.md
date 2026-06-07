# Program — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Inlined internship phases and document requirements as JSON structures inside the
> Internship model, reducing submodule and table count.

Manages internship programs, timelines, and cohort student groupings (groups).

For complete technical reference including API, models, actions, and components, see
[program-reference.md](program-reference.md).

---

## Key Principles

- **Internship Programs Define Specifications** — Every program defines the duration, dates, and
  grading weights for specific student cohorts.
- **JSON Timelines & Requirements** — Program phases (e.g. Observation, Practice, Reporting) and
  required document checklists are saved directly as JSON columns inside the `internships` table,
  preventing table sprawl and keeping configurations cohesive.
- **Groups Organize Student Cohorts** — Cohorts of students placed at the same company slot are
  organized under `internship_groups` with assigned supervisors for bulk coordination and
  monitoring.

---

## Context Boundary

The **Program** module:

- Owns `Internship`, `InternshipGroup`, `InternshipGroupMember`, and `InternshipPhase` models.
- Consumed by **Enrollment** for registration scopes and placement groupings.
- Links with **Partners** for company details and **Academics** for calendar scoping.

---

## Module Rules

- **Program Date Bounds:** Program timelines must fall within the active academic year's date
  limits.
- **Phase Timeline Structure:** Program phases stored inside the `phases` JSON array must be
  chronologically ordered and contiguous (no overlapping dates or gaps).
- **Group Capacity Constraint:** Assigning students to a group must not violate the designated
  company slot capacity.

---

## Submodules

- **Internship**: Core program definition. Houses dates, status (`draft` | `active` | `closed`),
  grading weights configuration, phases JSON, and required document templates checklist JSON.
- **InternshipGroup**: Cohort student/mentor group management. Handles group details and member
  mapping.
- **InternshipPhase**: Program phase templates defining standard phase names, order, and duration
  defaults.

---

## Error Handling & Failure Modes

- **Modifying Active Programs:** Changing weights, credit hours, or timeline limits on a published
  or active program throws a `RejectedException` to protect data integrity.
- **Invalid JSON Timelines:** Creating non-consecutive phase dates or overlapping ranges in the JSON
  payload returns a `ValidationFailedException`.
- **Group Quota Exceeded:** Attempting to assign students to a cohort group beyond the company
  placement slot quota returns a `ConflictException`.

---

## Quick References

### Actions & Business Logic

- **13** actions across all submodules:
    - Internship CRUD and status adjustments (including `CheckCloseReadinessAction`).
    - InternshipGroup CRUD and member management (add/remove member).
    - InternshipPhase CRUD (create, update, delete).

### Data & Persistence

- **4** models: `Internship`, `InternshipGroup`, `InternshipGroupMember`, `InternshipPhase`.
- UUID PKs. `phases` and `required_document_ids` stored as JSON on `internships`.

### User Interface

- **3** Livewire components:
    - `InternshipManager` — Program settings and JSON checklist editors.
    - `InternshipGroupManager` — Cohort group assignment tables.
    - `InternshipPhaseManager` — Phase template management.

### Authorization

- **3** policies: `InternshipPolicy`, `InternshipGroupPolicy`, `InternshipPhasePolicy`.
- Restricted to admin/superadmin.

---

For complete technical reference, see [program-reference.md](program-reference.md).
