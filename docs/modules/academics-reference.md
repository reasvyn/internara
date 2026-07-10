# Academics — Technical Reference

> **Last updated:** 2026-07-05 **Changes:** sync — fix base class extends: BaseAction →
> BaseCommandAction/BaseReadAction

## Description

Detailed structural and implementation reference for the **Academics** module.

---

## Overview

Manages educational structure: academic years, departments (jurusan), and school information.

## Actions

| File                                                     | Class                           | Extends             |
| -------------------------------------------------------- | ------------------------------- | ------------------- |
| `AcademicYear/Actions/CreateAcademicYearAction.php`      | `CreateAcademicYearAction`      | `BaseCommandAction` |
| `AcademicYear/Actions/UpdateAcademicYearAction.php`      | `UpdateAcademicYearAction`      | `BaseCommandAction` |
| `AcademicYear/Actions/DeleteAcademicYearAction.php`      | `DeleteAcademicYearAction`      | `BaseCommandAction` |
| `AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseCommandAction` |
| `AcademicYear/Actions/ActivateAcademicYearAction.php`    | `ActivateAcademicYearAction`    | `BaseCommandAction` |
| `Department/Actions/CreateDepartmentAction.php`          | `CreateDepartmentAction`        | `BaseCommandAction` |
| `Department/Actions/UpdateDepartmentAction.php`          | `UpdateDepartmentAction`        | `BaseCommandAction` |
| `Department/Actions/DeleteDepartmentAction.php`          | `DeleteDepartmentAction`        | `BaseCommandAction` |
| `School/Actions/SaveSchoolProfileAction.php`             | `SaveSchoolProfileAction`       | `BaseCommandAction` |

---

## Models

| File                                   | Class          | Extends     |
| -------------------------------------- | -------------- | ----------- |
| `AcademicYear/Models/AcademicYear.php` | `AcademicYear` | `BaseModel` |
| `Department/Models/Department.php`     | `Department`   | `BaseModel` |

---

## Data / DTOs

| File                                     | Class              | Extends    |
| ---------------------------------------- | ------------------ | ---------- |
| `AcademicYear/Data/AcademicYearData.php` | `AcademicYearData` | `BaseData` |
| `Department/Data/DepartmentData.php`     | `DepartmentData`   | `BaseData` |

## Entities

| File                                          | Class               | Extends      |
| --------------------------------------------- | ------------------- | ------------ |
| `AcademicYear/Entities/AcademicYearState.php` | `AcademicYearState` | `BaseEntity` |
| `Department/Entities/DepartmentState.php`     | `DepartmentState`   | `BaseEntity` |
| `School/Entities/SchoolEntity.php`            | `SchoolEntity`      | `BaseEntity` |

## Events

| File                                            | Class                   | Dispatched By                                               |
| ----------------------------------------------- | ----------------------- | ----------------------------------------------------------- |
| `AcademicYear/Events/AcademicYearCreated.php`   | `AcademicYearCreated`   | `CreateAcademicYearAction`                                  |
| `AcademicYear/Events/AcademicYearActivated.php` | `AcademicYearActivated` | `ActivateAcademicYearAction`                                |
| `AcademicYear/Events/AcademicYearUpdated.php`   | `AcademicYearUpdated`   | `UpdateAcademicYearAction`                                  |
| `AcademicYear/Events/AcademicYearDeleted.php`   | `AcademicYearDeleted`   | `DeleteAcademicYearAction`, `BulkDeleteAcademicYearsAction` |
| `Department/Events/DepartmentCreated.php`       | `DepartmentCreated`     | `CreateDepartmentAction`                                    |
| `Department/Events/DepartmentDeleted.php`       | `DepartmentDeleted`     | `DeleteDepartmentAction`                                    |
| `Department/Events/DepartmentUpdated.php`       | `DepartmentUpdated`     | `UpdateDepartmentAction`                                    |

---

## Policies

| File                                           | Policy               | Extends      |
| ---------------------------------------------- | -------------------- | ------------ |
| `AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` | `BasePolicy` |
| `Department/Policies/DepartmentPolicy.php`     | `DepartmentPolicy`   | `BasePolicy` |

---

## Livewire Components

| File                                            | Component             | Extends             |
| ----------------------------------------------- | --------------------- | ------------------- |
| `AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Department/Livewire/DepartmentManager.php`     | `DepartmentManager`   | `BaseRecordManager` |
| `School/Livewire/SchoolEditor.php`              | `SchoolEditor`        | `Component`         |

## Livewire Forms

| File                                               | Form               |
| -------------------------------------------------- | ------------------ |
| `AcademicYear/Livewire/Forms/AcademicYearForm.php` | `AcademicYearForm` |
| `Department/Livewire/Forms/DepartmentForm.php`     | `DepartmentForm`   |
| `School/Livewire/Forms/SchoolForm.php`             | `SchoolForm`       |

---

## Routes

File: `routes/web/academics.php` Naming pattern: `academics.{resource}.{action}`

## Views

Views are located in `resources/views/academics/`. See [UI/UX](../foundation/ui-ux.md) for the
design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Academics/`. See [Testing](../infrastructure/testing.md)
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

---

## Architectural Integration

- **Submodules**: `AcademicYear`, `Department`, `School`
- **Business Logic**: `app/Academics/`
- **Routing**: `routes/web/academics.php`
- **Views**: `resources/views/academics/`
- **Testing**: `tests/Academics/`, `tests/Academics/`
- **Dependencies**: Core (BaseModel, BaseAction, BaseEntity, BaseData, BasePolicy)
- **Events Consumed By**: `User/Dashboard` (cache invalidation listeners)
- **Used By**: Program, Enrollment, Assessment, User/Dashboard

_For overview and business context, see [academics.md](academics.md)._
