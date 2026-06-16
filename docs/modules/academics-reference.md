# Academics — Technical Reference

> **Last updated:** 2026-06-16
> **Changes:** sync — add SaveSchoolProfileAction, SchoolForm, 3 new Events (AcademicYearUpdated, AcademicYearDeleted, DepartmentUpdated) with dispatch mappings

Detailed structural and implementation reference for the **Academics** module.

---

## Overview

Manages educational structure: academic years, departments (jurusan), and school information.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `AcademicYear/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseAction` |
| `AcademicYear/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseAction` |
| `AcademicYear/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseAction` |
| `AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` |
| `AcademicYear/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseAction` |
| `Department/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseAction` |
| `Department/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseAction` |
| `Department/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseAction` |
| `School/Actions/SaveSchoolProfileAction.php` | `SaveSchoolProfileAction` | `BaseCommandAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `AcademicYear/Models/AcademicYear.php` | `AcademicYear` | `BaseModel` |
| `Department/Models/Department.php` | `Department` | `BaseModel` |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `AcademicYear/Data/AcademicYearData.php` | `AcademicYearData` | `BaseData` |
| `Department/Data/DepartmentData.php` | `DepartmentData` | `BaseData` |

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `AcademicYear/Entities/AcademicYearState.php` | `AcademicYearState` | `BaseEntity` |
| `Department/Entities/DepartmentState.php` | `DepartmentState` | `BaseEntity` |
| `School/Entities/SchoolEntity.php` | `SchoolEntity` | `BaseEntity` |

## Events

| File | Class | Dispatched By |
| ---- | ----- | ------------- |
| `AcademicYear/Events/AcademicYearCreated.php` | `AcademicYearCreated` | `CreateAcademicYearAction` |
| `AcademicYear/Events/AcademicYearActivated.php` | `AcademicYearActivated` | `ActivateAcademicYearAction` |
| `AcademicYear/Events/AcademicYearUpdated.php` | `AcademicYearUpdated` | `UpdateAcademicYearAction` |
| `AcademicYear/Events/AcademicYearDeleted.php` | `AcademicYearDeleted` | `DeleteAcademicYearAction`, `BulkDeleteAcademicYearsAction` |
| `Department/Events/DepartmentCreated.php` | `DepartmentCreated` | `CreateDepartmentAction` |
| `Department/Events/DepartmentDeleted.php` | `DepartmentDeleted` | `DeleteDepartmentAction` |
| `Department/Events/DepartmentUpdated.php` | `DepartmentUpdated` | `UpdateDepartmentAction` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` | `BasePolicy` |
| `Department/Policies/DepartmentPolicy.php` | `DepartmentPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Department/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` |
| `School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `AcademicYear/Livewire/Forms/AcademicYearForm.php` | `AcademicYearForm` |
| `Department/Livewire/Forms/DepartmentForm.php` | `DepartmentForm` |
| `School/Livewire/Forms/SchoolForm.php` | `SchoolForm` |

---

## Routes

File: `routes/web/academics.php`
Naming pattern: `academics.{resource}.{action}`

## Views

Views are located in `resources/views/academics/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Academics/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `AcademicYearFactory` | `AcademicYear` |
| `DepartmentFactory` | `Department` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_academic_years_table` | `academic_years` |
| `create_departments_table` | `departments` |

---


---

## Architectural Integration

- **Submodules**: `AcademicYear`, `Department`, `School`
- **Business Logic**: `app/Academics/`
- **Routing**: `routes/web/academics.php`
- **Views**: `resources/views/academics/`
- **Testing**: `tests/Feature/Academics/`, `tests/Unit/Academics/`
- **Dependencies**: Core (BaseModel, BaseAction, BaseEntity, BaseData, BasePolicy)
- **Events Consumed By**: `User/Dashboard` (cache invalidation listeners)
- **Used By**: Program, Enrollment, Assessment, User/Dashboard

*For overview and business context, see [academics.md](academics.md).*
