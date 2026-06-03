# Enrollment — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Enrollment domain.

Manages student registration and placement phase progression

For complete technical reference including API, models, actions, and components, see [enrollment-reference.md](enrollment-reference.md).

---

## Key Principles

- Registration tracks program enrollment
- Phases define internship progression stages
- State-driven transitions between phases
- Enrollment determines feature access

---

## Context Boundary

Owns registration and phase models. Uses User for students, Program for internship specs, Academics for curriculum context.

---

## Domain Rules

- Phases must be traversed sequentially
- Phase transitions validated against completion criteria
- Students cannot skip phases
- Enrollment status drives dashboard and notifications

---

## Quick References

### Actions & Business Logic
- **13** actions across all aggregates
- Business logic operations for enrollment domain

### Data & Persistence
- **5** models managing core data
- Eloquent relationships and queries

### User Interface
- **9** Livewire components for real-time interaction
- Views in `resources/views/enrollment/`

### Authorization
- **5** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [enrollment-reference.md](enrollment-reference.md).
