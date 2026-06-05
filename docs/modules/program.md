# Program — Documentation Overview

> Last updated: 2026-06-05
> Changes: Added Error Handling & Failure Modes section

Manages internship and practicum programs, phases, and requirements

For complete technical reference including API, models, actions, and components, see [program-reference.md](program-reference.md).

---

## Key Principles

- Programs define internship specifications
- Phases structure the internship timeline
- Groups organize students by cohort
- Document requirements list deliverables
- Schedules define work hours and periods

---

## Context Boundary

Owns program, phase, group, and schedule models. Used by Enrollment for phase progression. Linked with Partners and Academics.

---

## Module Rules

- Program must specify duration and credit hours
- Phases must be consecutive with no gaps
- Group capacity cannot exceed company limit
- All document requirements specified upfront

---

## Submodules

- **DocumentRequirement**: Core business entity for documentrequirement management
- **Internship**: Core business entity for internship management
- **InternshipGroup**: Core business entity for internshipgroup management
- **InternshipPhase**: Core business entity for internshipphase management

---

## Error Handling & Failure Modes

- **Program modification after activation**: Once a program is published or active, certain fields (duration, credit hours, phases) are locked. Modification attempts throw a `RejectedException`.
- **Phase gap validation**: Creating or updating phases with overlapping or non-consecutive dates is rejected with a `ValidationFailedException`.
- **Group capacity exceeded**: Assigning students to a group beyond its defined capacity throws a `ConflictException`.
- **Document requirement modification in use**: Requirements linked to existing enrollments cannot be deleted. The system returns a `RejectedException` with details of affected registrations.

---

## Quick References

### Actions & Business Logic
- **16** actions across all submodules
- Business logic operations for program module

### Data & Persistence
- **5** models managing core data
- Eloquent relationships and queries

### User Interface
- **4** Livewire components for real-time interaction
- Views in `resources/views/program/`

### Authorization
- **3** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [program-reference.md](program-reference.md).
