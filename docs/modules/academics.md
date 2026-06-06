# Academics — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Removed the separate School submodule and policy. The school's profile (NPSN code, name, contact) is now stored inside the global config `settings` table as namespaces (`school.*`), since the application is single-tenant.

Institutional structure: departments and academic calendar management.

For complete technical reference including API, models, actions, and components, see [academics-reference.md](academics-reference.md).

---

## Key Principles

- **Single School Context (Single-Tenant)** — The system is designed to run once per school. All school-wide attributes (legal name, NPSN, principal name) are managed as global configurations inside `settings`.
- **Departments Organize Academic Divisions** — Teachers and students are assigned to departments. Internship programs are scoped by department.
- **Academic Years Define the Calendar** — Only one academic year can be active at a time. Activating a new year deactivates all others.

---

## Context Boundary

The **Academics** module owns:
- **`Department`** and **`AcademicYear`** models.
- Provides reference calendars for **Program** (internship programs belong to an academic year) and **Enrollment** (registrations are scoped by calendars).
- Serves department and calendar references used by **User** and **SysAdmin** profiles.

---

## Module Rules

- **Exactly One Active Academic Year** at any time. Activating a new year automatically deactivates others.
- **Department Deletion is Guarded** — A department cannot be deleted if it has active programs, users, or placements.
- **Chronological Calendar Limits** — Academic years cannot overlap, and `start_date` must precede `end_date`.

---

## Submodules

- **Department**: CRUD-managed academic majors (divisions). Guarded deletion prevents orphan database records.
- **AcademicYear**: School calendar periods with single-active constraints and start/end dates.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan academics:activate-year {year}` | Activate a specific academic year |

---

## Error Handling & Failure Modes

- **Deleting a Referenced Department:** Throws a `ConflictException` listing active programs or users blocking deletion.
- **Activating Overlapping Years:** Throws a `RejectedException` if the dates overlap or if more than one active year constraint is violated.

---

## Quick References

### Actions & Business Logic
- **8** actions across submodules:
  - Department CRUD (create, update, delete)
  - Academic year CRUD (create, update, delete, activate, deactivate)

### Data & Persistence
- **2** models: `Department`, `AcademicYear`.
- UUID PKs. `AcademicYear` has `is_active` boolean check scopes.

### User Interface
- **2** Livewire components:
  - `DepartmentManager` — Major CRUD table.
  - `AcademicYearManager` — Calendar management table.

### Authorization
- **2** policies: `DepartmentPolicy`, `AcademicYearPolicy`.
- Restricted to admin/superadmin. No students or teachers can modify academics structure.

---

For complete technical reference, see [academics-reference.md](academics-reference.md).
