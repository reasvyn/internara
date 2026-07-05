# Reports — Technical Reference

> **Last updated:** 2026-07-03 **Changes:** remove deleted actions/events/Livewire; add
> FinalizeReportAction, CreateReportData DTO; update ReportStatus enum

## Description

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Manages final student grade card: score aggregation, grade calculation, and coordinator
finalization.

## Actions

| File                                           | Class                       | Extends             |
| ---------------------------------------------- | --------------------------- | ------------------- |
| `Report/Actions/CreateReportAction.php`        | `CreateReportAction`        | `BaseCommandAction` |
| `Report/Actions/CalculateFinalGradeAction.php` | `CalculateFinalGradeAction` | `BaseCommandAction` |
| `Report/Actions/FinalizeReportAction.php`      | `FinalizeReportAction`      | `BaseCommandAction` |

## Data / DTOs

| File                               | Class              | Extends    |
| ---------------------------------- | ------------------ | ---------- |
| `Report/Data/CreateReportData.php` | `CreateReportData` | `BaseData` |

## Models

| File                       | Class    | Extends     |
| -------------------------- | -------- | ----------- |
| `Report/Models/Report.php` | `Report` | `BaseModel` |

## Enums

| File                            | Enum           | Implements                | Values           |
| ------------------------------- | -------------- | ------------------------- | ---------------- |
| `Report/Enums/ReportStatus.php` | `ReportStatus` | `LabelEnum`, `StatusEnum` | draft, finalized |

## Events

| File                                | Event             | Dispatched By               |
| ----------------------------------- | ----------------- | --------------------------- |
| `Report/Events/GradeCalculated.php` | `GradeCalculated` | `CalculateFinalGradeAction` |
| `Report/Events/ReportFinalized.php` | `ReportFinalized` | `FinalizeReportAction`      |

## HTTP Controllers

| File                                           | Controller         | Extends          |
| ---------------------------------------------- | ------------------ | ---------------- |
| `Report/Http/Controllers/ReportController.php` | `ReportController` | `BaseController` |

## Observers

| File                                  | Observer         | Observes |
| ------------------------------------- | ---------------- | -------- |
| `Report/Observers/ReportObserver.php` | `ReportObserver` | `Report` |

## Routes

File: `routes/web/reports.php` Only admin download route: `sysadmin.reports.download`

## Tests

Tests are located in `tests/{Feature,Unit}/Reports/`. See [Testing](../infrastructure/testing.md)
for the testing conventions.

## Factories

| Factory         | Model    |
| --------------- | -------- |
| `ReportFactory` | `Report` |

## Migrations

| Migration              | Table     |
| ---------------------- | --------- |
| `create_reports_table` | `reports` |

---

## Architectural Integration

- **Submodules**: `Report`
- **Business Logic**: `app/Reports/`
- **Routing**: `routes/web/reports.php`
- **Testing**: `tests/Feature/Reports/`, `tests/Unit/Reports/`
- **Dependencies**: User, Program, Assessment, Assignment, Enrollment, Core

_For overview and business context, see [reports.md](reports.md)._
