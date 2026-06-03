# Guidance — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Guidance domain.

Manages mentoring relationships, student guidance, and supervision logs

For complete technical reference including API, models, actions, and components, see [guidance-reference.md](guidance-reference.md).

---

## Key Principles

- Mentors provide academic guidance and support
- Supervisors provide workplace guidance
- Handbooks document expectations and policies
- Supervision logs track interactions

---

## Context Boundary

Links students with mentors/supervisors. User defines relationships. Handbook captures policy acknowledgments. Journals records activity.

---

## Domain Rules

- Each student has one primary mentor
- Each internship has one assigned supervisor
- Handbook acknowledgement required before placement
- Supervision logs are immutable records

---

## Aggregates

- **Handbook**: Core business entity for handbook management
- **HandbookAcknowledgement**: Core business entity for handbookacknowledgement management
- **Mentee**: Core business entity for mentee management
- **Mentor**: Core business entity for mentor management
- **SupervisionLog**: Core business entity for supervisionlog management

---

## Quick References

### Actions & Business Logic
- **15** actions across all aggregates
- Business logic operations for guidance domain

### Data & Persistence
- **5** models managing core data
- Eloquent relationships and queries

### User Interface
- **9** Livewire components for real-time interaction
- Views in `resources/views/guidance/`

### Authorization
- **4** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [guidance-reference.md](guidance-reference.md).
