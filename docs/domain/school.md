# School Domain

## Purpose

School provides the institutional foundation that all operational domains build on. It answers
three structural questions: which school exists, what departments it contains, and what academic
years define the temporal boundaries for programs. This is structural configuration data — it
defines the organizational hierarchy that every other domain references for context.

## Boundary

**In scope:** School profile (legal name, institutional code, address, contact information,
logo via media library), department definitions (name, description, school association),
academic year management (name, start/end dates, is_active flag with single-active constraint),
school single-record enforcement (configurable via `school.single_record`), import/export of
departments via CSV.

**Out of scope:** Internship program definitions (Internship domain), user profiles and their
department associations (User domain), partnership company management (Partnership domain),
registration processing (Registration domain), runtime application configuration (Settings domain).

## Key Concepts

**Schools.** A single school profile representing the educational institution. Has: name,
institutional code, address, email, phone, fax, principal name, and optional logo (via
spatie/laravel-medialibrary with a webp thumb conversion). The school is created once during
setup and edited via the admin SchoolEditor Livewire component. The `SchoolState` entity enforces
a single-record constraint (configurable via `school.single_record`).

**Departments.** Organizational units within the school. Each department has a name and optional
description, and belongs to the school. Departments are managed via the `DepartmentManager`
Livewire component (extends `BaseRecordManager`), with full CRUD, search, sort, pagination,
bulk selection, CSV import/export/template download, and stats display. The `DepartmentState`
entity checks if a department can be deleted (no associated user profiles).

**Academic Years.** Defines temporal boundaries for internship programs. Each year has a name,
start/end dates, and an `is_active` flag. At most one academic year can be active at a time
(enforced by `ActivateAcademicYearAction`). The `AcademicYearState` entity checks active status,
activation eligibility, and deletability (cannot delete active years or years with related
internships/assessments).

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to set up the school profile so that the institution's identity is configured
- **Admin:** As an admin, I want to manage departments so that the organizational structure is maintained
- **Admin:** As an admin, I want to create academic years so that internship programs have temporal boundaries
- **Admin:** As an admin, I want to activate and deactivate academic years so that only one year is active at a time
- **Admin:** As an admin, I want to import/export departments via CSV so that I can manage data in bulk
- School follows a single-record pattern — only one school profile exists.
- At most one academic year can be marked as active at any time; activating a new year
deactivates the current one.
- A department cannot be deleted if it has any user profiles associated.
- An academic year cannot be deleted if it is active or has related internship/assessment records.
- A department is always loaded with its school relationship (`$with = ['school']`).
- The school logo is stored via spatie/laravel-medialibrary with a `thumb` webp conversion.

### Process Flow

```
Academic Year Lifecycle:

INACTIVE ──→ ACTIVE (only one at a time)
```

- At most one academic year can be active at any time
- Activating a new year automatically deactivates the current one
- An active academic year cannot be deleted
- A department cannot be deleted if any user profiles reference it

### Key Operations

| Action | Description |
|--------|-------------|
| `UpdateSchoolAction` | Updates the school profile |
| `CreateDepartmentAction` | Creates a new department |
| `UpdateDepartmentAction` | Updates department details |
| `DeleteDepartmentAction` | Deletes a department (blocked if associated profiles exist) |
| `CreateAcademicYearAction` | Creates a new academic year |
| `UpdateAcademicYearAction` | Updates academic year details |
| `DeleteAcademicYearAction` | Deletes an inactive academic year |
| `ActivateAcademicYearAction` | Activates an academic year (deactivates current) |
| `BulkDeleteAcademicYearsAction` | Batch deletes academic years |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `School`, `Department`, `AcademicYear` |
| **Entities** | `SchoolState` (single-record enforcement); `DepartmentState` (deletion guard); `AcademicYearState` (active status, deletion/activation eligibility) |
| **Livewire** | `SchoolEditor`, `DepartmentManager`, `AcademicYearIndex` |
| **Policies** | `SchoolPolicy`, `DepartmentPolicy`, `AcademicYearPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Core | BaseModel, BaseAction, BaseEntity, BasePolicy, BaseRecordManager, SmartLogger, RejectedException |
| Internship | hasManyThrough for internships via departments |
| Assessment | hasMany for assessments via academic years |
| User | Profile model for department association |
| Shared | CsvHandler for import/export functionality |


