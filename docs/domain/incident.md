# Incident — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Incident domain.

Tracks incident reports and workplace concerns requiring intervention

For complete technical reference including API, models, actions, and components, see [incident-reference.md](incident-reference.md).

---

## Key Principles

- Incident reports document problems and concerns
- Severity levels enable prioritization
- Automated routing to appropriate handlers
- Resolution tracking provides audit trail

---

## Context Boundary

Created by students/supervisors. Routed to Admin/Teachers. Linked with Program internships for context.

---

## Domain Rules

- Report requires description and severity
- Severity determines routing and escalation
- Submitted reports cannot be deleted
- Resolution documented for compliance

---

## Aggregates

- **IncidentReport**: Core business entity for incidentreport management

---

## Quick References

### Actions & Business Logic
- **3** actions across all aggregates
- Business logic operations for incident domain

### Data & Persistence
- **1** models managing core data
- Eloquent relationships and queries

### User Interface
- **2** Livewire components for real-time interaction
- Views in `resources/views/incident/`

### Authorization
- **1** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [incident-reference.md](incident-reference.md).
