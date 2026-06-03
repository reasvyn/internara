# Partners — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Partners domain.

Manages partner companies and partnership agreements

For complete technical reference including API, models, actions, and components, see [partners-reference.md](partners-reference.md).

---

## Key Principles

- Companies are industrial partners hosting internships
- Partnerships define contractual relationships
- Company profiles enable student/supervisor matching
- Partnership agreements document terms

---

## Context Boundary

Owns company and partnership models. Program links internships to companies. Guidance assigns company supervisors.

---

## Domain Rules

- Partnership agreement must be signed
- Company must have valid contact information
- Partnership suspension pauses new internships
- Historical partnerships preserved for audit

---

## Aggregates

- **Company**: Core business entity for company management
- **Partnership**: Core business entity for partnership management

---

## Quick References

### Actions & Business Logic
- **10** actions across all aggregates
- Business logic operations for partners domain

### Data & Persistence
- **2** models managing core data
- Eloquent relationships and queries

### User Interface
- **2** Livewire components for real-time interaction
- Views in `resources/views/partners/`

### Authorization
- **2** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [partners-reference.md](partners-reference.md).
