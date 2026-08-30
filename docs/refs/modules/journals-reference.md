# Journals — Technical Reference

## Description

Detailed structural and implementation reference for the **Journals** module.

---

## Overview

Manages daily student activity tracking: logbooks, attendance (clock in/out), absence requests, and
field monitoring visit scheduling/verification.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/AbsenceRequest/Actions/ProcessAbsenceAction.php` | `ProcessAbsenceAction` | `BaseProcessAction` |
| `Domain/AbsenceRequest/Actions/SubmitAbsenceAction.php` | `SubmitAbsenceAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/ClockInAction.php` | `ClockInAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/ClockOutAction.php` | `ClockOutAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/CreateAttendanceAction.php` | `CreateAttendanceAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/DeleteAttendanceAction.php` | `DeleteAttendanceAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/UpdateAttendanceAction.php` | `UpdateAttendanceAction` | `BaseCommandAction` |
| `Domain/Attendance/Actions/VerifyAttendanceAction.php` | `VerifyAttendanceAction` | `BaseCommandAction` |
| `Domain/Logbook/Actions/CompileLogbookReportAction.php` | `CompileLogbookReportAction` | `BaseCommandAction` |
| `Domain/Logbook/Actions/CreateLogbookAction.php` | `CreateLogbookAction` | `BaseCommandAction` |
| `Domain/Logbook/Actions/DeleteLogbookAction.php` | `DeleteLogbookAction` | `BaseCommandAction` |
| `Domain/Logbook/Actions/SubmitLogbookAction.php` | `SubmitLogbookAction` | `BaseCommandAction` |
| `Domain/Logbook/Actions/UpdateLogbookAction.php` | `UpdateLogbookAction` | `BaseCommandAction` |
| `Domain/MonitoringVisit/Actions/CreateVisitAction.php` | `CreateVisitAction` | `BaseCommandAction` |
| `Domain/MonitoringVisit/Actions/VerifyVisitAction.php` | `VerifyVisitAction` | `BaseCommandAction` |
| `Domain/SupervisionLog/Actions/CreateLogAction.php` | `CreateLogAction` | `BaseCommandAction` |
| `Domain/SupervisionLog/Actions/CreateSupervisionLogAction.php` | `CreateSupervisionLogAction` | `BaseCommandAction` |
| `Domain/SupervisionLog/Actions/DeleteLogAction.php` | `DeleteLogAction` | `BaseCommandAction` |
| `Domain/SupervisionLog/Actions/ReviewLogAction.php` | `ReviewLogAction` | `BaseCommandAction` |
| `Domain/SupervisionLog/Actions/VerifySupervisionLogAction.php` | `VerifySupervisionLogAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Attendance/Models/Attendance.php` | `Attendance` | `BaseModel` |
| `Domain/Logbook/Models/Logbook.php` | `Logbook` | `BaseModel` |
| `Domain/MonitoringVisit/Models/MonitoringVisit.php` | `MonitoringVisit` | `BaseModel` |
| `Domain/SupervisionLog/Models/SupervisionLog.php` | `SupervisionLog` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/AbsenceRequest/Enums/AbsenceReasonType.php` | `AbsenceReasonType` | `LabelEnum` | — |
| `Domain/AbsenceRequest/Enums/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `LabelEnum` | — |
| `Domain/Attendance/Enums/AttendanceStatus.php` | `AttendanceStatus` | `LabelEnum` | — |
| `Domain/Logbook/Enums/LogbookStatus.php` | `LogbookStatus` | `LabelEnum` | — |
| `Domain/MonitoringVisit/Enums/VisitMethod.php` | `VisitMethod` | `LabelEnum` | — |
| `Domain/SupervisionLog/Enums/SupervisionLogStatus.php` | `SupervisionLogStatus` | `LabelEnum` | — |
| `Domain/SupervisionLog/Enums/SupervisionType.php` | `SupervisionType` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/AbsenceRequest/Policies/AbsenceRequestPolicy.php` | `AbsenceRequestPolicy` | `BasePolicy` |
| `Domain/Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` | `BasePolicy` |
| `Domain/Logbook/Policies/LogbookPolicy.php` | `LogbookPolicy` | `BasePolicy` |
| `Domain/MonitoringVisit/Policies/MonitoringVisitPolicy.php` | `MonitoringVisitPolicy` | `BasePolicy` |
| `Domain/SupervisionLog/Policies/SupervisionLogPolicy.php` | `SupervisionLogPolicy` | `BasePolicy` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Attendance/Events/AttendanceClockIn.php` | `AttendanceClockIn` | `BaseEvent` |
| `Domain/Attendance/Events/AttendanceClockOut.php` | `AttendanceClockOut` | `BaseEvent` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/AbsenceRequest/Livewire/AbsenceRequestForm.php` | `AbsenceRequestForm` | `BaseFormView` |
| `Domain/Attendance/Livewire/AttendanceManager.php` | `AttendanceManager` | `BaseRecordManager` |
| `Domain/Attendance/Livewire/StudentClockIn.php` | `StudentClockIn` | `Component` |
| `Domain/Logbook/Livewire/Forms/LogbookForm.php` | `LogbookForm` | `BaseFormView` |
| `Domain/Logbook/Livewire/LogbookEntry.php` | `LogbookEntry` | `BaseRecordEntry` |
| `Domain/Logbook/Livewire/LogbookManager.php` | `LogbookManager` | `BaseRecordManager` |
| `Domain/MonitoringVisit/Livewire/StudentVisitList.php` | `StudentVisitList` | `BaseRecordManager` |
| `Domain/MonitoringVisit/Livewire/VisitManager.php` | `VisitManager` | `BaseRecordManager` |
| `Domain/SupervisionLog/Livewire/StudentLogManager.php` | `StudentLogManager` | `BaseRecordManager` |
| `Domain/SupervisionLog/Livewire/SupervisionManager.php` | `SupervisionManager` | `BaseRecordManager` |
| `Domain/SupervisionLog/Livewire/SupervisorLogManager.php` | `SupervisorLogManager` | `BaseRecordManager` |
| `Domain/SupervisionLog/Livewire/SupervisorReviewManager.php` | `SupervisorReviewManager` | `BaseRecordManager` |

## Form Requests

| File | Request | Purpose |
|---|---|---|
| `Domain/AbsenceRequest/Http/Requests/SubmitAbsenceRequest.php` | `SubmitAbsenceRequest` | — |
| `Domain/Attendance/Http/Requests/ClockInRequest.php` | `ClockInRequest` | — |
| `Domain/Attendance/Http/Requests/ClockOutRequest.php` | `ClockOutRequest` | — |
| `Domain/Logbook/Http/Requests/CreateLogbookEntryRequest.php` | `CreateLogbookEntryRequest` | — |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Http/Controllers/LogbookReportController.php` | `LogbookReportController` | `BaseController` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/AbsenceRequest/Data/ProcessAbsenceData.php` | `ProcessAbsenceData` | `BaseData` |
| `Domain/AbsenceRequest/Data/SubmitAbsenceData.php` | `SubmitAbsenceData` | `BaseData` |
| `Domain/Attendance/Data/ClockInData.php` | `ClockInData` | `BaseData` |
| `Domain/Attendance/Data/ClockOutData.php` | `ClockOutData` | `BaseData` |
| `Domain/MonitoringVisit/Data/CreateVisitData.php` | `CreateVisitData` | `BaseData` |
| `Domain/SupervisionLog/Data/CreateLogData.php` | `CreateLogData` | `BaseData` |
| `Domain/SupervisionLog/Data/CreateSupervisionLogData.php` | `CreateSupervisionLogData` | `BaseData` |
| `Domain/SupervisionLog/Data/ReviewLogData.php` | `ReviewLogData` | `BaseData` |

## Routes

File: `routes/web/journals.php` Named routes: `student.logbook`, `student.attendance`,
`student.attendance.absence`, `student.supervision-logs`, `student.monitoring-visits`,
`sysadmin.attendance`, `sysadmin.logbook`, `sysadmin.logbook.report`, `supervision.logs`,
`monitoring-visits.index`

## Views

Views are located in `resources/views/journals/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Journals/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory                 | Model            |
| ----------------------- | ---------------- |
| `LogbookFactory`        | `Logbook`        |
| `AttendanceFactory`     | `Attendance`     |
| `AbsenceRequestFactory` | `AbsenceRequest` |
| `MonitoringVisitFactory` | `MonitoringVisit` |
| `SupervisionLogFactory` | `SupervisionLog` |

## Migrations

| Migration                      | Table          |
| ------------------------------ | -------------- |
| `create_attendances_table`     | `attendances`  |
| `create_logbooks_table`        | `logbooks`     |
| `create_supervision_logs_table` | `supervision_logs` |
| `create_monitoring_visits_table` | `monitoring_visits` |

---

## Architectural Integration

- **Submodules**: `Logbook`, `Attendance`, `AbsenceRequest`, `MonitoringVisit`, `SupervisionLog`
- **Business Logic**: `app/Modules/Journals/`
- **Routing**: `routes/web/journals.php`
- **Views**: `resources/views/journals/`
- **Testing**: `tests/Journals/`
- **Dependencies**: Enrollment, Program, Core
- **Used By**: Evaluation, Reports

_For overview and business context, see [journals.md](journals.md)._
