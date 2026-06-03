# Academics — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Reference for the Academics domain aggregates

This reference details the class structures, models, actions, and Livewire components belonging to the **Academics** domain.

---

## Actions

### Institutional Metadata Actions
| File | Class | Extends | Description |
|---|---|---|---|
| `Academics/Aggregates/School/Actions/UpdateSchoolAction.php` | `UpdateSchoolAction` | `BaseAction` | Modifies base school identifiers, principal information, and contact credentials |
| `Academics/Aggregates/Department/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseAction` | Provisions a study department program |
| `Academics/Aggregates/Department/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseAction` | Updates department definitions and program keahlian |
| `Academics/Aggregates/Department/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseAction` | Deletes a study program (aborts if references exist) |
| `Academics/Aggregates/AcademicYear/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseAction` | Creates an academic calendar span |
| `Academics/Aggregates/AcademicYear/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseAction` | Edits start and end dates of calendar spans |
| `Academics/Aggregates/AcademicYear/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseAction` | Removes academic span records |
| `Academics/Aggregates/AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` | Removes selected academic span records in batches |
| `Academics/Aggregates/AcademicYear/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseAction` | Set academic span as active and deactivates others |

---

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Academics/Aggregates/School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` | Form handling legal school info, logos, and contacts |
| `Academics/Aggregates/Department/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` | Study program list, search, and bulk deletion |
| `Academics/Aggregates/AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` | Grid toggle, edit, and calendar span creations |

### Livewire Form Objects
| File | Class | Extends | Used By |
|---|---|---|---|
| `Academics/Aggregates/School/Livewire/Forms/SchoolForm.php` | `SchoolForm` | `Form` | `SchoolEditor` |
| `Academics/Aggregates/Department/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` | `Form` | `DepartmentManager` |
| `Academics/Aggregates/AcademicYear/Livewire/Forms/AcademicYearForm.php` | `AcademicYearForm` | `Form` | `AcademicYearManager` |

---

## Models

### School (`School.php`)
- **Extends**: `BaseModel`
- **Fields**: name, institutional_code, email, phone, address, website, principal_name

### Department (`Department.php`)
- **Extends**: `BaseModel`
- **Fields**: name, code, description, is_active

### AcademicYear (`AcademicYear.php`)
- **Extends**: `BaseModel`
- **Fields**: name, start_date, end_date, is_active

---

## Policies

- `SchoolPolicy`: Restricts modifications of basic school layouts to administrators.
- `DepartmentPolicy`: Validates studies creation, editing, and deletion. Prevents deletion when referenced by students.
- `AcademicYearPolicy`: Gated CRUD checks. Protects the active year from deletion.

---

## Middleware

- `RequireSetupAccessMiddleware` (`app/Domain/Academics/Http/Middleware/RequireSetupAccessMiddleware.php`): Checks setup status globally. If false, redirects HTTP calls to `/setup`. Bypasses livewire/assets.
- `ProtectSetupRouteMiddleware` (`app/Domain/Academics/Http/Middleware/ProtectSetupRouteMiddleware.php`): Limits token endpoint calls to 20/min/IP. Ensures valid token session hash exists. Throws 404 once installed.
