# Academics — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Academics domain.

Detailed structural and implementation reference for the **Academics** domain.

---

## Overview

Manages educational institutions, departments, and academic calendar periods

### Domain Statistics
- **Actions**: 9 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 3 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 3 domain aggregates

### Aggregates
- `AcademicYear`
- `Department`
- `School`

---

## Dependency Graph

This domain depends on:
- **Admin**
- **Assessment**
- **Core**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/AcademicYear/Actions/ActivateAcademicYearAction.php` | `ActivateAcademicYearAction` | `BaseAction` |
| `Aggregates/AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` | `BulkDeleteAcademicYearsAction` | `BaseAction` |
| `Aggregates/AcademicYear/Actions/CreateAcademicYearAction.php` | `CreateAcademicYearAction` | `BaseAction` |
| `Aggregates/Department/Actions/CreateDepartmentAction.php` | `CreateDepartmentAction` | `BaseAction` |
| `Aggregates/AcademicYear/Actions/DeleteAcademicYearAction.php` | `DeleteAcademicYearAction` | `BaseAction` |
| `Aggregates/Department/Actions/DeleteDepartmentAction.php` | `DeleteDepartmentAction` | `BaseAction` |
| `Aggregates/AcademicYear/Actions/UpdateAcademicYearAction.php` | `UpdateAcademicYearAction` | `BaseAction` |
| `Aggregates/Department/Actions/UpdateDepartmentAction.php` | `UpdateDepartmentAction` | `BaseAction` |
| `Aggregates/School/Actions/UpdateSchoolAction.php` | `UpdateSchoolAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/AcademicYear/Models/AcademicYear.php` | `AcademicYear` |
| `Aggregates/Department/Models/Department.php` | `Department` |
| `Aggregates/School/Models/School.php` | `School` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/AcademicYear/Livewire/AcademicYearManager.php` | `AcademicYearManager` | `BaseRecordManager` |
| `Aggregates/Department/Livewire/DepartmentManager.php` | `DepartmentManager` | `BaseRecordManager` |
| `Aggregates/School/Livewire/SchoolEditor.php` | `SchoolEditor` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/AcademicYear/Policies/AcademicYearPolicy.php` | `AcademicYearPolicy` |
| `Aggregates/Department/Policies/DepartmentPolicy.php` | `DepartmentPolicy` |
| `Aggregates/School/Policies/SchoolPolicy.php` | `SchoolPolicy` |

---

## File Organization

```
app/Domain/Academics/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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
