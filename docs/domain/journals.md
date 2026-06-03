# Journals — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Journals domain.

Manages student logbooks, attendance tracking, and schedule management

For complete technical reference including API, models, actions, and components, see [journals-reference.md](journals-reference.md).

---

## Key Principles

- Logbooks capture daily activities and reflections
- Attendance verifies presence and manages absences
- Schedules define work expectations
- Industry assessments track progress

---

## Context Boundary

Tracks Enrollment phase activity. Linked with Program schedules. Evaluation consumes performance data.

---

## Domain Rules

- Logbook entries are date-based and immutable
- Attendance requires supervisor signature
- Absence requests require explicit approval
- Schedule adjustments require supervisor authorization

---

## Aggregates

- **AbsenceRequest**: Core business entity for absencerequest management
- **Attendance**: Core business entity for attendance management
- **IndustryAssessment**: Core business entity for industryassessment management
- **Logbook**: Core business entity for logbook management
- **Schedule**: Core business entity for schedule management

---

## Quick References

### Actions & Business Logic
- **17** actions across all aggregates
- Business logic operations for journals domain

### Data & Persistence
- **5** models managing core data
- Eloquent relationships and queries

### User Interface
- **7** Livewire components for real-time interaction
- Views in `resources/views/journals/`

### Authorization
- **3** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [journals-reference.md](journals-reference.md).
