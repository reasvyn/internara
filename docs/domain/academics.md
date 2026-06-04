# Academics — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Institutional structure: schools, departments, and academic calendar management.

For complete technical reference including API, models, actions, and components, see [academics-reference.md](academics-reference.md).

---

## Key Principles

- **School is the root entity** — every student, teacher, and program belongs to a school. The system supports a single school (self-hosted, single-tenant).
- **Departments organize academic divisions** — teachers and students are assigned to departments. Programs belong to departments.
- **Academic years define the calendar** — only one academic year can be active at a time. Switching activates the new year and deactivates all others.
- **Setup wizard drives initial creation** — the first-run wizard creates the school, a default department, and optionally the first academic year.

---

## Context Boundary

Owns `School`, `Department`, and `AcademicYear` models. Serves as reference data for Program (programs belong to departments) and Enrollment (registrations are scoped by academic year). SysAdmin reads school/department data for system configuration.

---

## Domain Rules

- **Exactly one active academic year** at any time. Activating a new year automatically deactivates the previous one via a single-active constraint.
- **Department deletion is guarded** — a department cannot be deleted if it has active programs, users, or placements. The guard returns a clear error message identifying the blocking records.
- **School details** can only be updated through the admin interface (not directly in the database).
- **Dates must be chronologically valid**: `start_date` must precede `end_date`, academic years cannot overlap, and program dates must fall within the parent academic year.
- **Bulk delete** of inactive academic years is supported with result summaries.

---

## Aggregates

- **School**: Single school profile — legal name, code, address, contact details, logo. Created during setup, editable by admin.
- **Department**: CRUD-managed academic divisions. Guarded deletion prevents data loss. Bulk operations supported.
- **AcademicYear**: Calendar periods with single-active constraint. Bulk deletion of inactive years. Supports `start_date` / `end_date` validation.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan academics:activate-year {year}` | Activate a specific academic year |

---

## Error Handling & Failure Modes

- **Deleting a referenced department**: Throws a `ConflictException` listing the active programs or users blocking deletion. The UI shows this error in a flash message.
- **Activating overlapping years**: The single-active constraint prevents this. Trying to set two active years throws a `RejectedException`.
- **Missing academic year**: If no academic year exists (after setup), programs cannot be created. The setup wizard creates the first year.

---

## Quick References

### Actions & Business Logic
- **9** actions across all aggregates
- School CRUD, department management (with deletion guard), academic year activation/deactivation, bulk year deletion

### Data & Persistence
- **3** models: `School`, `Department`, `AcademicYear`
- UUID PKs, `HasFactory`, `AcademicYear` has `is_active` boolean with single-active scope

### User Interface
- **3** Livewire components
- School editor, department manager (table with search/sort/paginate/bulk), academic year manager with activation toggle

### Authorization
- **3** policies: `SchoolPolicy`, `DepartmentPolicy`, `AcademicYearPolicy`
- All restricted to admin/superadmin. No student or teacher can modify academic structure

---

For complete technical reference, see [academics-reference.md](academics-reference.md).
