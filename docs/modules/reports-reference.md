# Reports — Technical Reference

> Last updated: 2026-06-06  
> Changes: Redefined to reference Final Grade Card models, actions, and Livewire components.

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Student Final Grade Card aggregation, feedback compilation, and coordinator sign-off.

### Module Statistics

- **Actions**: 5 business logic operations
- **Models**: 2 data entities (`Report`, `ReportRevision`)
- **Livewire Components**: 1 UI component
- **Policies**: 0 authorization rules
- **Submodules**: 1 module submodule

### Submodules

- **Report**: Represents the student's Final Grade Card containing the aggregated scores,
  grades, company feedback, and finalization workflow.

---

## Dependency Graph

This module depends on:

- **Core** (base classes and DTOs)
- **Enrollment** (registration records)
- **User** (evaluators and students)
- **Assessment** (rubric scores)

---

## Actions

| File                                           | Class                       | Extends      |
| ---------------------------------------------- | --------------------------- | ------------ |
| `Report/Actions/CreateReportAction.php`              | `CreateReportAction`              | `BaseAction` |
| `Report/Actions/SubmitReportAction.php`              | `SubmitReportAction`              | `BaseAction` |
| `Report/Actions/ApproveReportAction.php`             | `ApproveReportAction`             | `BaseAction` |
| `Report/Actions/RequestReportRevisionAction.php`     | `RequestReportRevisionAction`     | `BaseAction` |
| `Report/Actions/AddSupervisorReportNotesAction.php`  | `AddSupervisorReportNotesAction`  | `BaseAction` |

---

## Models

| File                              | Class            |
| --------------------------------- | ---------------- |
| `Report/Models/Report.php`        | `Report`         |
| `Report/Models/ReportRevision.php`| `ReportRevision` |

---

## Enums

| File                               | Class          | Type                       |
| ---------------------------------- | -------------- | -------------------------- |
| `Report/Enums/ReportStatus.php`    | `ReportStatus` | String-backed, `LabelEnum` |

---

## Livewire Components

| File                                    | Component      | Extends     |
| --------------------------------------- | -------------- | ----------- |
| `Report/Livewire/ReportWriter.php`      | `ReportWriter` | `Component` |

---

## HTTP Controllers

| File                                                      | Controller         | Extends          |
| --------------------------------------------------------- | ------------------ | ---------------- |
| `Report/Http/Controllers/ReportController.php`            | `ReportController` | `BaseController` |

---

## Authorization Policies

None. Grade card authorization is handled through the Enrollment module's policy layer.

---

## File Organization

```
app/Reports/
└── Report/                  ← Submodule root
    ├── Actions/
    ├── Enums/
    ├── Http/
    │   └── Controllers/
    ├── Livewire/
    └── Models/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Report`
- **Business Logic (`app/`)**: Located in
  [app/Reports/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Reports/)
- **Routing (`routes/`)**:
  [routes/web/reports.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/reports.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/reports/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/reports/)
- **Testing (`tests/`)**: Feature `tests/Feature/Reports/`, Unit `tests/Unit/Reports/`

_For overview and business context, see [reports.md](reports.md)_
