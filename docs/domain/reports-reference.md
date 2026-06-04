# Reports — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Reports** domain.

---

## Overview

Generates reports and analytics across the application

### Domain Statistics
- **Actions**: 5 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 1 UI components
- **Policies**: 0 authorization rules
- **Aggregates**: 1 domain aggregates

### Aggregates
- `Report`

---

## Dependency Graph

This domain depends on:
- **Certification**
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Report/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` |
| `Aggregates/Report/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` |
| `Aggregates/Report/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` |
| `Aggregates/Report/Actions/RequestReportRevisionAction.php` | `RequestReportRevisionAction` | `BaseAction` |
| `Aggregates/Report/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Report/Models/Report.php` | `Report` |
| `Aggregates/Report/Models/ReportRevision.php` | `ReportRevision` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Report/Livewire/ReportWriter.php` | `ReportWriter` | `Component` |

---

## File Organization

```
app/Domain/Reports/
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

*For overview and business context, see [reports.md](reports.md)*
