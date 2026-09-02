# Academics — Technical Reference

## Description

Detailed structural and implementation reference for the **Academics** module.

---

## Overview

Manages educational structure: academic years, departments (jurusan), and school information.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/AcademicYear/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseCommandAction` |
| `Domain/AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseCommandAction` |
| `Domain/AcademicYear/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseCommandAction` |
| `Domain/AcademicYear/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseCommandAction` |
| `Domain/AcademicYear/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseCommandAction` |
| `Domain/Department/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseCommandAction` |
| `Domain/Department/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseCommandAction` |
| `Domain/Department/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseCommandAction` |
| `Domain/School/Actions/GetSchoolEntityAction.php` | `GetSchoolEntityAction` | `BaseCommandAction` |
| `Domain/School/Actions/SaveSchoolProfileAction.php` | `SaveSchoolProfileAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/AcademicYear/Models/AcademicYear.php` | `AcademicYear` | `BaseModel` |
| `Domain/Department/Models/Department.php` | `Department` | `BaseModel` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/AcademicYear/Data/AcademicYearData.php` | `AcademicYearData` | `BaseData` |
| `Domain/Department/Data/DepartmentData.php` | `DepartmentData` | `BaseData` |

## Support

| File                                                | Class                | Purpose                                                          |
| --------------------------------------------------- | -------------------- | ---------------------------------------------------------------- |
| `AcademicYear/Support/AcademicYearPeriod.php`       | `AcademicYearPeriod` | Active school-year computation (July–June convention), shared by seeders and settings UI (FR-AY40) |

## Entities

| File | Class | Extends |
|---|---|---|
| `Domain/School/Entities/SchoolEntity.php` | `SchoolEntity` | `BaseEntity` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/AcademicYear/Events/AcademicYearActivated.php` | `AcademicYearActivated` | `BaseEvent` |
| `Domain/AcademicYear/Events/AcademicYearCreated.php` | `AcademicYearCreated` | `BaseEvent` |
| `Domain/AcademicYear/Events/AcademicYearDeleted.php` | `AcademicYearDeleted` | `BaseEvent` |
| `Domain/AcademicYear/Events/AcademicYearUpdated.php` | `AcademicYearUpdated` | `BaseEvent` |
| `Domain/Department/Events/DepartmentCreated.php` | `DepartmentCreated` | `BaseEvent` |
| `Domain/Department/Events/DepartmentDeleted.php` | `DepartmentDeleted` | `BaseEvent` |
| `Domain/Department/Events/DepartmentUpdated.php` | `DepartmentUpdated` | `BaseEvent` |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` | `BasePolicy` |
| `Domain/Department/Policies/DepartmentPolicy.php` | `DepartmentPolicy` | `BasePolicy` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Domain/AcademicYear/Livewire/Forms/AcademicYearForm.php` | `AcademicYearForm` | `BaseFormView` |
| `Domain/Department/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` |
| `Domain/Department/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` | `BaseFormView` |
| `Domain/School/Livewire/Forms/SchoolForm.php` | `SchoolForm` | `BaseFormView` |
| `Domain/School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` |

## Routes

File: `routes/web/academics.php` Named routes: `sysadmin.school`, `sysadmin.departments`,
`sysadmin.academic-years`

## Views

Views are located in `resources/views/academics/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the
design system.

## Tests

Tests are located in `tests/Academics/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory               | Model          |
| --------------------- | -------------- |
| `AcademicYearFactory` | `AcademicYear` |
| `DepartmentFactory`   | `Department`   |

## Migrations

| Migration                     | Table            |
| ----------------------------- | ---------------- |
| `create_academic_years_table` | `academic_years` |
| `create_departments_table`    | `departments`    |

---

## Architectural Integration

- **Submodules**: `AcademicYear`, `Department`, `School`
- **Business Logic**: `app/Modules/Academics/`
- **Routing**: `routes/web/academics.php`
- **Views**: `resources/views/academics/`
- **Testing**: `tests/Academics/`
- **Dependencies**: Core (BaseModel, BaseAction, BaseEntity, BaseData, BasePolicy)
- **Events Consumed By**: `User/Dashboard` (cache invalidation listeners)
- **Used By**: Program, Enrollment, Assessment, User/Dashboard

_For overview and business context, see [academics.md](academics.md)._
