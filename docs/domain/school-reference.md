# School — API Reference
> Last updated: 2026-05-26
> Changes: docs: update school-reference and user-reference for recent changes; add AcademicYearEditTest


Total: 24 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `School/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseAction` | Activates an academic year and deactivates others |
| `School/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` | Deletes multiple academic years at once |
| `School/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseAction` | Creates a new academic year |
| `School/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseAction` | Creates a new department |
| `School/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseAction` | Deletes an academic year |
| `School/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseAction` | Deletes a department |
| `School/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseAction` | Updates an academic year |
| `School/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseAction` | Updates a department's details |
| `School/Actions/UpdateSchoolAction.php` | `UpdateSchoolAction` | `BaseAction` | Updates school information |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `School/Entities/AcademicYearState.php` | `AcademicYearState` | `BaseEntity` | Read-only DTO for academic year state |
| `School/Entities/DepartmentState.php` | `DepartmentState` | `BaseEntity` | Read-only DTO for department state |
| `School/Entities/SchoolState.php` | `SchoolState` | `BaseEntity` | Read-only DTO for school state |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `School/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` | CRUD manager for academic years with toggleSelectAll bulk selection |
| `School/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` | CRUD manager for departments |
| `School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` | School information editor |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `School/Livewire/Forms/AcademicYearForm.php` | `AcademicYearForm` | `Form` | name, start_date, end_date | `AcademicYearManager` |
| `School/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` | `Form` | id, name, description | `DepartmentManager` |
| `School/Livewire/Forms/SchoolForm.php` | `SchoolForm` | `Form` | name, institutional_code, email, phone, address, website, fax, principal_name | `SchoolEditor` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `School/Models/AcademicYear.php` | `AcademicYear` | `BaseModel` | Eloquent model for academic years |
| `School/Models/Department.php` | `Department` | `BaseModel` | Eloquent model for departments/study programs |
| `School/Models/School.php` | `School` | `BaseModel` | Eloquent model for school/institution |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `School/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` | `BasePolicy` | Authorization for academic year operations |
| `School/Policies/DepartmentPolicy.php` | `DepartmentPolicy` | `BasePolicy` | Authorization for department operations |
| `School/Policies/SchoolPolicy.php` | `SchoolPolicy` | `BasePolicy` | Authorization for school operations |
