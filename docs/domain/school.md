# School Domain

## Purpose

School provides the institutional foundation — schools, departments, and academic years that
define the structural boundaries for all operational domains.

---

## Design Principles

### 1. Singleton School

The school is created once during setup. There is exactly one school per installation.

### 2. Single Active Academic Year

Only one academic year can be active at any time. Activating a new year deactivates all others.

### 3. Department Deletion Guard

Departments with active profiles cannot be deleted — enforced by `DepartmentState` entity.

---

## Models

| Model | Key Fields |
|---|---|
| `School` | name, institutional_code, address, email, phone, logo (media) |
| `Department` | name, description, school_id |
| `AcademicYear` | name, start_date, end_date, is_active |

## Actions

| Action | Type |
|---|---|
| `UpdateSchoolAction` | Command |
| `CreateDepartmentAction` | Command |
| `UpdateDepartmentAction` | Command |
| `DeleteDepartmentAction` | Command |
| `CreateAcademicYearAction` | Command |
| `UpdateAcademicYearAction` | Command |
| `ActivateAcademicYearAction` | Command |
| `DeleteAcademicYearAction` | Command |
| `BulkDeleteAcademicYearsAction` | Command |

## Entities

| Entity | Purpose |
|---|---|
| `SchoolState` | canBeCreated — enforces singleton |
| `DepartmentState` | canBeDeleted — checks active profiles |
| `AcademicYearState` | canBeActivated, canBeDeleted — lifecycle rules |

## Where to Find It

- `app/Domain/School/Models/School.php`
- `app/Domain/School/Models/Department.php`
- `app/Domain/School/Models/AcademicYear.php`
- `app/Domain/School/Actions/` — 9 Actions
- `app/Domain/School/Policies/` — AcademicYearPolicy, DepartmentPolicy, SchoolPolicy
