# Assignment — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Assignment domain.

Manages coursework assignments and submission tracking

For complete technical reference including API, models, actions, and components, see [assignment-reference.md](assignment-reference.md).

---

## Key Principles

- Assignments define course deliverables and deadlines
- Submissions capture student work and grades
- Grading provides feedback to students
- Late submission tracking for compliance

---

## Context Boundary

Owns assignment and submission models. Teachers manage content; students submit work. Linked with Program for course context.

---

## Domain Rules

- Deadlines cannot be in the past
- Submissions locked after deadline by default
- Grading feedback required for complete submission
- Only instructors can create/modify assignments

---

## Aggregates

- **Assignment**: Core business entity for assignment management
- **Submission**: Core business entity for submission management

---

## Quick References

### Actions & Business Logic
- **7** actions across all aggregates
- Business logic operations for assignment domain

### Data & Persistence
- **3** models managing core data
- Eloquent relationships and queries

### User Interface
- **3** Livewire components for real-time interaction
- Views in `resources/views/assignment/`

### Authorization
- **2** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [assignment-reference.md](assignment-reference.md).
