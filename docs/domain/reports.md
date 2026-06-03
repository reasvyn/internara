# Reports — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Reports domain.

Generates reports and analytics across the application

For complete technical reference including API, models, actions, and components, see [reports-reference.md](reports-reference.md).

---

## Key Principles

- Reports aggregate data from multiple domains
- Multiple export formats supported
- Role-based report access control
- Historical reports preserved

---

## Context Boundary

Consumes data from all domains. Admin controls visibility. Generates business intelligence and compliance reports.

---

## Domain Rules

- Report generation requires authorization
- Reports contain only user-accessible data
- Export formats vary by report type
- Generated reports archived for audit

---

## Aggregates

- **Report**: Core business entity for report management

---

## Quick References

### Actions & Business Logic
- **5** actions across all aggregates
- Business logic operations for reports domain

### Data & Persistence
- **2** models managing core data
- Eloquent relationships and queries

### User Interface
- **1** Livewire components for real-time interaction
- Views in `resources/views/reports/`

### Authorization
- **0** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [reports-reference.md](reports-reference.md).
