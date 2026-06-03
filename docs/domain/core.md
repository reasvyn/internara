# Core — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Core domain.

Provides foundational infrastructure, base classes, and application-wide utilities

For complete technical reference including API, models, actions, and components, see [core-reference.md](core-reference.md).

---

## Key Principles

- BaseModel provides standard persistence features
- BaseAction enforces single-responsibility pattern
- BasePolicy standardizes authorization
- Shared contracts and utilities available to all domains

---

## Context Boundary

Foundational - all domains depend on Core. Core has minimal external dependencies.

---

## Domain Rules

- All models extend BaseModel or Authenticatable
- All business logic encapsulated in Actions
- Authorization checked through Policies
- Consistent exception handling across domains

---

## Quick References

### Actions & Business Logic
- **1** actions across all aggregates
- Business logic operations for core domain

### Data & Persistence
- **2** models managing core data
- Eloquent relationships and queries

### User Interface
- **3** Livewire components for real-time interaction
- Views in `resources/views/core/`

### Authorization
- **1** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [core-reference.md](core-reference.md).
