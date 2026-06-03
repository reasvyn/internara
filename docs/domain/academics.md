# Academics — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Academics domain.

Manages educational institutions, departments, and academic calendar periods

For complete technical reference including API, models, actions, and components, see [academics-reference.md](academics-reference.md).

---

## Key Principles

- School is the primary institutional entity
- Departments organize academic divisions
- Academic years define curriculum timelines
- Administrative authorization required for all operations

---

## Context Boundary

Owns school, department, and academic year definitions. Serves as reference data for Enrollment and Program domains. Used by Admin for setup configuration.

---

## Domain Rules

- Only one active academic year at a time
- Departments cannot be deleted while referenced by active programs
- School details updated exclusively through admin interface
- All dates must be chronologically valid

---

## Aggregates

- **AcademicYear**: Core business entity for academicyear management
- **Department**: Core business entity for department management
- **School**: Core business entity for school management

---

## Quick References

### Actions & Business Logic
- **9** actions across all aggregates
- Business logic operations for academics domain

### Data & Persistence
- **3** models managing core data
- Eloquent relationships and queries

### User Interface
- **3** Livewire components for real-time interaction
- Views in `resources/views/academics/`

### Authorization
- **3** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [academics-reference.md](academics-reference.md).
