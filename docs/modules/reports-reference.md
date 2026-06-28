# Reports — Technical Reference

> **Last updated:** 2026-06-27
> **Changes:** remove revision feature, ReportRevision model, revision_requested enum value from Reports module

## Description
Detailed structural and implementation reference for the **Reports** module.

---


## Overview

Manages final student grade compilation, score aggregation, and coordinator sign-off.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Report/Actions/CreateReportAction.php` | `CreateReportAction` | `BaseAction` |
| `Report/Actions/SubmitReportAction.php` | `SubmitReportAction` | `BaseAction` |
| `Report/Actions/ApproveReportAction.php` | `ApproveReportAction` | `BaseAction` |
| `Report/Actions/AddSupervisorReportNotesAction.php` | `AddSupervisorReportNotesAction` | `BaseAction` |
| `Report/Actions/SaveReportDraftAction.php` | `SaveReportDraftAction` | `BaseAction` |
| `Report/Actions/CalculateFinalGradeAction.php` | `CalculateFinalGradeAction` | Process `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Report/Models/Report.php` | `Report` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Report/Enums/ReportStatus.php` | `ReportStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, approved, finalized |

---

## Events

| File | Event | Dispatched By |
| ---- | ----- | ------------- |
| `Report/Events/GradeCalculated.php` | `GradeCalculated` | `CalculateFinalGradeAction` |
| `Report/Events/ReportApproved.php` | `ReportApproved` | `ApproveReportAction` |
| `Report/Events/ReportSubmitted.php` | `ReportSubmitted` | `SubmitReportAction` |
| `Report/Events/ReportFinalized.php` | `ReportFinalized` | `FinalizeReportAction` |

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Report/Http/Controllers/ReportController.php` | `ReportController` | `BaseController` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Report/Livewire/ReportWriter.php` | `ReportWriter` | `Component` |

## Observers

| File | Observer | Observes |
| ---- | -------- | -------- |
| `Report/Observers/ReportObserver.php` | `ReportObserver` | `Report` |

---

## Routes

File: `routes/web/reports.php`
Naming pattern: `reports.{resource}.{action}`

## Views

Views are located in `resources/views/reports/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Reports/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `ReportFactory` | `Report` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_reports_table` | `reports` |

---

## Architectural Integration

- **Submodules**: `Report`
- **Business Logic**: `app/Reports/`
- **Routing**: `routes/web/reports.php`
- **Views**: `resources/views/reports/`
- **Testing**: `tests/Feature/Reports/`, `tests/Unit/Reports/`
- **Dependencies**: User, Program, Assessment, Enrollment, Core

*For overview and business context, see [reports.md](reports.md).*
