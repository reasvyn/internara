# Reports — Technical Reference

> Last updated: 2026-06-05
> Changes: Fixed overview description to match actual code (student reports, not BI)

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Student final report writing, revision workflow, and supervisor review

### Module Statistics
- **Actions**: 5 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 1 UI components
- **Policies**: 0 authorization rules
- **Submodules**: 1 module submodules

### Submodules
- **Report**: Student-authored internship report with status workflow (DRAFT → SUBMITTED → VERIFIED), scoring, and supervisor revision support

---

## Dependency Graph

This module depends on:
- **Certification**
- **Core**
- **Enrollment**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Report/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` |
| `Report/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` |
| `Report/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` |
| `Report/Actions/RequestReportRevisionAction.php` | `RequestReportRevisionAction` | `BaseAction` |
| `Report/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Report/Models/Report.php` | `Report` |
| `Report/Models/ReportRevision.php` | `ReportRevision` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Report/Livewire/ReportWriter.php` | `ReportWriter` | `Component` |

---

## File Organization

```
app/Reports/
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

*For overview and business context, see [reports.md](reports.md)*
