# Journals — Technical Reference

> **Last updated:** 2026-06-16
> **Changes:** sync — fix file tree indentation (6→8 spaces under Attendance/ and Logbook/)

Detailed structural and implementation reference for the **Journals** module.

---

## Overview

Manages daily student activity tracking: logbooks, attendance (clock in/out), and absence requests.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Actions/CreateLogbookAction.php` | `CreateLogbookAction` | `BaseCommandAction` |
| `Logbook/Actions/UpdateLogbookAction.php` | `UpdateLogbookAction` | `BaseCommandAction` |
| `Logbook/Actions/DeleteLogbookAction.php` | `DeleteLogbookAction` | `BaseCommandAction` |
| `Logbook/Actions/SubmitLogbookAction.php` | `SubmitLogbookAction` | `BaseCommandAction` |
| `Logbook/Actions/CompileLogbookReportAction.php` | `CompileLogbookReportAction` | Read |
| `Attendance/Actions/CreateAttendanceAction.php` | `CreateAttendanceAction` | `BaseCommandAction` |
| `Attendance/Actions/UpdateAttendanceAction.php` | `UpdateAttendanceAction` | `BaseCommandAction` |
| `Attendance/Actions/DeleteAttendanceAction.php` | `DeleteAttendanceAction` | `BaseCommandAction` |
| `Attendance/Actions/ClockInAction.php` | `ClockInAction` | `BaseCommandAction` |
| `Attendance/Actions/ClockOutAction.php` | `ClockOutAction` | `BaseCommandAction` |
| `Attendance/Actions/VerifyAttendanceAction.php` | `VerifyAttendanceAction` | `BaseCommandAction` |
| `AbsenceRequest/Actions/SubmitAbsenceAction.php` | `SubmitAbsenceAction` | `BaseCommandAction` |
| `AbsenceRequest/Actions/ProcessAbsenceAction.php` | `ProcessAbsenceAction` | `BaseCommandAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Models/Logbook.php` | `Logbook` | `BaseModel` |
| `Attendance/Models/Attendance.php` | `Attendance` | `BaseModel` |
| `AbsenceRequest/Models/AbsenceRequest.php` | `AbsenceRequest` | `BaseModel` (uses `attendances` table) |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Logbook/Enums/LogbookStatus.php` | `LogbookStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified, rejected |
| `Attendance/Enums/AttendanceStatus.php` | `AttendanceStatus` | `LabelEnum`, `StatusEnum` | present, late, early_out, absent, permission, sick |
| `AbsenceRequest/Enums/AbsenceReasonType.php` | `AbsenceReasonType` | `LabelEnum` | sick, permission, emergency, other |
| `AbsenceRequest/Enums/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Entities/LogbookState.php` | `LogbookState` | `BaseEntity` |
| `Attendance/Entities/AttendanceState.php` | `AttendanceState` | `BaseEntity` |
| `AbsenceRequest/Entities/AbsenceRequestState.php` | `AbsenceRequestState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Logbook/Policies/LogbookPolicy.php` | `LogbookPolicy` | `BasePolicy` |
| `Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` | `BasePolicy` |
---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Logbook/Livewire/LogbookManager.php` | `LogbookManager` | `BaseRecordManager` |
| `Logbook/Livewire/LogbookEntry.php` | `LogbookEntry` | `Component` |
| `Attendance/Livewire/AttendanceManager.php` | `AttendanceManager` | `BaseRecordManager` |
| `Attendance/Livewire/StudentClockIn.php` | `StudentClockIn` | `Component` |
| `AbsenceRequest/Livewire/AbsenceRequestForm.php` | `AbsenceRequestForm` | `Component` |

## Form Requests

| File | Request | Purpose |
| ---- | ------- | ------- |
| `Logbook/Http/Requests/CreateLogbookEntryRequest.php` | `CreateLogbookEntryRequest` | Logbook entry validation |
| `Attendance/Http/Requests/ClockInRequest.php` | `ClockInRequest` | Clock in validation |
| `Attendance/Http/Requests/ClockOutRequest.php` | `ClockOutRequest` | Clock out validation |
| `AbsenceRequest/Http/Requests/SubmitAbsenceRequest.php` | `SubmitAbsenceRequest` | Absence request validation |

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Http/Controllers/LogbookReportController.php` | `LogbookReportController` | `BaseController` |

---

## Routes

File: `routes/web/journals.php`
Naming pattern: `journals.{resource}.{action}`

## Views

Views are located in `resources/views/journals/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Journals/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `LogbookFactory` | `Logbook` |
| `AttendanceFactory` | `Attendance` |
| `AbsenceRequestFactory` | `AbsenceRequest` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_logbooks_table` | `logbooks` |
| `create_attendances_table` | `attendances` |

---


---

## Architectural Integration

- **Submodules**: `Logbook`, `Attendance`, `AbsenceRequest`
- **Business Logic**: `app/Journals/`
- **Routing**: `routes/web/journals.php`
- **Views**: `resources/views/journals/`
- **Testing**: `tests/Feature/Journals/`, `tests/Unit/Journals/`
- **Dependencies**: Enrollment, Program, Core
- **Used By**: Evaluation

*For overview and business context, see [journals.md](journals.md).*
