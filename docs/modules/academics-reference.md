# Academics — Technical Reference

> Last updated: 2026-06-06  
> Changes: Reduced School submodule to Entity + Livewire editor (no standalone model, actions, or
> policies). School metadata is managed via the `settings` table.

Detailed structural and implementation reference for the **Academics** module.

---

## Overview

Manages academic majors (departments) and calendar years.

### Module Statistics

- **Actions**: 8 business logic operations
- **Models**: 2 data entities (`Department`, `AcademicYear`)
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 3 module submodules

### Submodules

- `School`
- `AcademicYear`
- `Department`

---

## Dependency Graph

This module depends on:

- **Core** (base classes)
- **User** (teachers/students department assignments)
- **SysAdmin** (settings configs)

---

## Actions

| File                                                     | Class                           | Extends      |
| -------------------------------------------------------- | ------------------------------- | ------------ |
| `AcademicYear/Actions/CreateAcademicYearAction.php`      | `CreateAcademicYearAction`      | `BaseAction` |
| `AcademicYear/Actions/UpdateAcademicYearAction.php`      | `UpdateAcademicYearAction`      | `BaseAction` |
| `AcademicYear/Actions/DeleteAcademicYearAction.php`      | `DeleteAcademicYearAction`      | `BaseAction` |
| `AcademicYear/Actions/ActivateAcademicYearAction.php`    | `ActivateAcademicYearAction`    | `BaseAction` |
| `AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` |
| `Department/Actions/CreateDepartmentAction.php`          | `CreateDepartmentAction`        | `BaseAction` |
| `Department/Actions/UpdateDepartmentAction.php`          | `UpdateDepartmentAction`        | `BaseAction` |
| `Department/Actions/DeleteDepartmentAction.php`          | `DeleteDepartmentAction`        | `BaseAction` |

---

## Models

| File                                   | Class          |
| -------------------------------------- | -------------- |
| `AcademicYear/Models/AcademicYear.php` | `AcademicYear` |
| `Department/Models/Department.php`     | `Department`   |

---

## Livewire Components

| File                                            | Component             | Extends             |
| ----------------------------------------------- | --------------------- | ------------------- |
| `School/Livewire/SchoolEditor.php`              | `SchoolEditor`        | `Component`         |
| `AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Department/Livewire/DepartmentManager.php`     | `DepartmentManager`   | `BaseRecordManager` |

---

## Authorization Policies

| File                                           | Policy               |
| ---------------------------------------------- | -------------------- | ------------ |
| `AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` | `BasePolicy` |
| `Department/Policies/DepartmentPolicy.php`     | `DepartmentPolicy`   | `BasePolicy` |

---

## File Organization

```
app/Academics/
├──            ← Submodule roots
│   ├── School/
│   │   ├── Entities/
│   │   └── Livewire/
│   ├── AcademicYear/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Livewire/
│   └── Department/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `School`, `AcademicYear`, `Department`
- **Business Logic (`app/`)**: Located in
  [app/Academics/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Academics/)
- **Routing (`routes/`)**:
  [routes/web/academics.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/academics.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/academics/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/academics/)
- **Testing (`tests/`)**: Feature `tests/Feature/Academics/`, Unit `tests/Unit/Academics/`

_For overview and business context, see [academics.md](academics.md)_
