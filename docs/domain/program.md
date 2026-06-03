# Program — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Program domain.

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

## Domain Rules

- Program must specify duration and credit hours
- Phases must be consecutive with no gaps
- Group capacity cannot exceed company limit
- All document requirements specified upfront

---

## Aggregates

- **DocumentRequirement**: Core business entity for documentrequirement management
- **Internship**: Core business entity for internship management
- **InternshipGroup**: Core business entity for internshipgroup management
- **InternshipPhase**: Core business entity for internshipphase management

---

## Quick References

### Actions & Business Logic
- **16** actions across all aggregates
- Business logic operations for program domain

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
