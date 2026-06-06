# Reports — Technical Reference

> Last updated: 2026-06-06  
> Changes: Redefined to reference Final Grade Card (Rapor PKL) models, actions, and Livewire components.

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Student Final Grade Card (*Rapor PKL*) aggregation, feedback compilation, and coordinator sign-off.

### Module Statistics
- **Actions**: 3 business logic operations
- **Models**: 1 data entity (`Report`)
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 1 module submodules

### Submodules
- **Report**: Represents the student's Grade Card (*Rapor PKL*) containing the aggregated scores, grades, company feedback, and finalization workflow.

---

## Dependency Graph

This module depends on:
- **Core** (base classes and DTOs)
- **Enrollment** (registration records)
- **User** (evaluators and students)
- **Assessment** (rubric scores)

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Report/Actions/CalculateFinalGradeAction.php` | `CalculateFinalGradeAction` | `BaseAction` |
| `Report/Actions/FinalizeReportCardAction.php` | `FinalizeReportCardAction` | `BaseAction` |
| `Report/Actions/UpdateReportCardAction.php` | `UpdateReportCardAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Report/Models/Report.php` | `Report` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Report/Livewire/ReportCardViewer.php` | `ReportCardViewer` | `Component` |
| `Report/Livewire/ReportCardManager.php` | `ReportCardManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Report/Policies/ReportCardPolicy.php` | `ReportCardPolicy` | `BasePolicy` |

---

## File Organization

```
app/Reports/
├── Report/                  ← Submodule root
│   ├── Actions/
│   ├── Models/
│   ├── Policies/
│   └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Report`
- **Business Logic (`app/`)**: Located in [app/Reports/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Reports/)
- **Routing (`routes/`)**: [routes/web/reports.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/reports.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/reports/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/reports/)
- **Testing (`tests/`)**: Feature `tests/Feature/Reports/`, Unit `tests/Unit/Reports/`


*For overview and business context, see [reports.md](reports.md)*
