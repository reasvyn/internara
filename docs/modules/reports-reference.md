# Reports — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Manages final student grade compilation, score aggregation, coordinator sign-off, and report revision workflows.

### Submodules

- `Report` — Final reports, grade cards, and revisions

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Report/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` |
| `Report/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` |
| `Report/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` |
| `Report/Actions/RequestReportRevisionAction.php` | `RequestReportRevisionAction` | `BaseAction` |
| `Report/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Report/Models/Report.php` | `Report` | `BaseModel` |
| `Report/Models/ReportRevision.php` | `ReportRevision` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Report/Enums/ReportStatus.php` | `ReportStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, approved, revision_requested, finalized |

---

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Report/Http/Controllers/ReportController.php` | `ReportController` | `BaseController` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Report/Livewire/ReportWriter.php` | `ReportWriter` | `Component` |

---

## Routes

File: `routes/web/reports.php`
Naming pattern: `reports.{resource}.{action}`

---

## File Organization

```
app/Reports/
└── Report/
    ├── Actions/
    │   ├── AddSupervisorReportNotesAction.php
    │   ├── ApproveReportAction.php
    │   ├── CreateReportAction.php
    │   ├── RequestReportRevisionAction.php
    │   └── SubmitReportAction.php
    ├── Enums/ReportStatus.php
    ├── Http/Controllers/ReportController.php
    ├── Livewire/ReportWriter.php
    ├── Models/
    │   ├── Report.php
    │   └── ReportRevision.php
    └── Policies/
```

---

## Architectural Integration

- **Submodules**: `Report`
- **Business Logic**: `app/Reports/`
- **Routing**: `routes/web/reports.php`
- **Views**: `resources/views/reports/`
- **Testing**: `tests/Feature/Reports/`, `tests/Unit/Reports/`
- **Dependencies**: User, Program, Assessment, Enrollment, Core

*For overview and business context, see [reports.md](reports.md).*
