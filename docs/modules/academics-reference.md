# Academics — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Academics** module.

---

## Overview

Manages educational institutions, departments, and academic calendar periods

### Module Statistics
- **Actions**: 9 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 3 authorization rules
- **Submodules**: 3 module submodules

### Submodules
- `AcademicYear`
- `Department`
- `School`

---

## Dependency Graph

This module depends on:
- **SysAdmin**
- **Assessment**
- **Core**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `AcademicYear/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseAction` |
| `AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` |
| `AcademicYear/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseAction` |
| `Department/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseAction` |
| `AcademicYear/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseAction` |
| `Department/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseAction` |
| `AcademicYear/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseAction` |
| `Department/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseAction` |
| `School/Actions/UpdateSchoolAction.php` | `UpdateSchoolAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `AcademicYear/Models/AcademicYear.php` | `AcademicYear` |
| `Department/Models/Department.php` | `Department` |
| `School/Models/School.php` | `School` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Department/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` |
| `School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` |
| `Department/Policies/DepartmentPolicy.php` | `DepartmentPolicy` |
| `School/Policies/SchoolPolicy.php` | `SchoolPolicy` |

---

## File Organization

```
app/Academics/
├──            ← Submodule roots
│   └── {SubModule}/
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

*For overview and business context, see [academics.md](academics.md)*
