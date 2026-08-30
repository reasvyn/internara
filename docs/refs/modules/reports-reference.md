# Reports — Technical Reference

## Description

Detailed structural and implementation reference for the **Reports** module.

---

## Overview

Manages final student grade card: score aggregation, grade calculation, and coordinator
finalization.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/StudentReport/Actions/CalculateFinalGradeAction.php` | `CalculateFinalGradeAction` | `BaseCommandAction` |
| `Domain/StudentReport/Actions/CaptureStudentReportSnapshotAction.php` | `CaptureStudentReportSnapshotAction` | `BaseCommandAction` |
| `Domain/StudentReport/Actions/CreateStudentReportAction.php` | `CreateStudentReportAction` | `BaseCommandAction` |
| `Domain/StudentReport/Actions/DeleteStudentReportAction.php` | `DeleteStudentReportAction` | `BaseCommandAction` |
| `Domain/StudentReport/Actions/DownloadStudentReportAction.php` | `DownloadStudentReportAction` | `BaseCommandAction` |
| `Domain/StudentReport/Actions/FinalizeStudentReportAction.php` | `FinalizeStudentReportAction` | `BaseCommandAction` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/StudentReport/Data/CreateStudentReportData.php` | `CreateStudentReportData` | `BaseData` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/StudentReport/Models/StudentReport.php` | `StudentReport` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/StudentReport/Enums/StudentReportStatus.php` | `StudentReportStatus` | `LabelEnum` | — |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/StudentReport/Events/GradeCalculated.php` | `GradeCalculated` | `BaseEvent` |
| `Domain/StudentReport/Events/StudentReportFinalized.php` | `StudentReportFinalized` | `BaseEvent` |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Domain/StudentReport/Http/Controllers/StudentReportController.php` | `StudentReportController` | `BaseController` |

## Observers

| File                                  | Observer         | Observes |
| ------------------------------------- | ---------------- | -------- |
| `Report/Observers/ReportObserver.php` | `ReportObserver` | `Report` |


## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/StudentReport/Livewire/StudentReportsManager.php` | `StudentReportsManager` | `BaseRecordManager` |


## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/StudentReport/Listeners/LogGradeCalculated.php` | `LogGradeCalculated` | — |
| `Domain/StudentReport/Listeners/LogStudentReportFinalized.php` | `LogStudentReportFinalized` | — |


## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/StudentReport/Policies/StudentReportPolicy.php` | `StudentReportPolicy` | `BasePolicy` |

## Routes

File: `routes/web/reports.php` Only admin download route: `sysadmin.reports.download`

## Tests

Tests are located in `tests/Reports/`. See [Testing](../../guides/infra/testing.md)
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
- **Business Logic**: `app/Modules/Reports/`
- **Routing**: `routes/web/reports.php`
- **Testing**: `tests/Reports/`
- **Dependencies**: User, Program, Assessment, Assignment, Enrollment, Core

_For overview and business context, see [reports.md](reports.md)._
