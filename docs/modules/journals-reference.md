# Journals — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Journals** module.

---

## Overview

Manages daily student activity tracking: logbooks, attendance (clock in/out), schedules, absence requests, and industry assessments.

### Submodules

- `Logbook` — Daily activity log entries
- `Attendance` — Clock in/out and attendance tracking
- `Schedule` — Student work schedules
- `AbsenceRequest` — Absence submissions and approvals
- `IndustryAssessment` — Industry mentor assessments

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Actions/CreateLogbookAction.php` | `CreateLogbookAction` | `BaseAction` |
| `Logbook/Actions/UpdateLogbookAction.php` | `UpdateLogbookAction` | `BaseAction` |
| `Logbook/Actions/DeleteLogbookAction.php` | `DeleteLogbookAction` | `BaseAction` |
| `Logbook/Actions/SubmitLogbookAction.php` | `SubmitLogbookAction` | `BaseAction` |
| `Logbook/Actions/CompileLogbookReportAction.php` | `CompileLogbookReportAction` | Read |
| `Attendance/Actions/CreateAttendanceAction.php` | `CreateAttendanceAction` | `BaseAction` |
| `Attendance/Actions/UpdateAttendanceAction.php` | `UpdateAttendanceAction` | `BaseAction` |
| `Attendance/Actions/DeleteAttendanceAction.php` | `DeleteAttendanceAction` | `BaseAction` |
| `Attendance/Actions/ClockInAction.php` | `ClockInAction` | `BaseAction` |
| `Attendance/Actions/ClockOutAction.php` | `ClockOutAction` | `BaseAction` |
| `Attendance/Actions/VerifyAttendanceAction.php` | `VerifyAttendanceAction` | `BaseAction` |
| `Schedule/Actions/CreateScheduleAction.php` | `CreateScheduleAction` | `BaseAction` |
| `Schedule/Actions/UpdateScheduleAction.php` | `UpdateScheduleAction` | `BaseAction` |
| `Schedule/Actions/DeleteScheduleAction.php` | `DeleteScheduleAction` | `BaseAction` |
| `AbsenceRequest/Actions/SubmitAbsenceAction.php` | `SubmitAbsenceAction` | `BaseAction` |
| `AbsenceRequest/Actions/ProcessAbsenceAction.php` | `ProcessAbsenceAction` | `BaseAction` |
| `IndustryAssessment/Actions/SubmitIndustryAssessmentAction.php` | `SubmitIndustryAssessmentAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Models/Logbook.php` | `Logbook` | `BaseModel` |
| `Attendance/Models/Attendance.php` | `Attendance` | `BaseModel` |
| `Schedule/Models/Schedule.php` | `Schedule` | `BaseModel` |
| `AbsenceRequest/Models/AbsenceRequest.php` | `AbsenceRequest` | `BaseModel` |
| `IndustryAssessment/Models/IndustryAssessment.php` | `IndustryAssessment` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Logbook/Enums/LogbookStatus.php` | `LogbookStatus` | `LabelEnum`, `StatusEnum` | draft, submitted, verified, rejected |
| `Attendance/Enums/AttendanceStatus.php` | `AttendanceStatus` | `LabelEnum`, `StatusEnum` | present, late, absent, excused |
| `AbsenceRequest/Enums/AbsenceReasonType.php` | `AbsenceReasonType` | `LabelEnum` | sick, personal, family, other |
| `AbsenceRequest/Enums/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `LabelEnum`, `StatusEnum` | pending, approved, rejected |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Logbook/Entities/LogbookState.php` | `LogbookState` | `BaseEntity` |
| `Attendance/Entities/AttendanceStatus.php` | `AttendanceStatus` | `BaseEntity` |
| `Schedule/Entities/ScheduleStatus.php` | `ScheduleStatus` | `BaseEntity` |
| `AbsenceRequest/Entities/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Logbook/Policies/LogbookPolicy.php` | `LogbookPolicy` | `BasePolicy` |
| `Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` | `BasePolicy` |
| `Schedule/Policies/SchedulePolicy.php` | `SchedulePolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Logbook/Livewire/LogbookManager.php` | `LogbookManager` | `BaseRecordManager` |
| `Logbook/Livewire/LogbookEntry.php` | `LogbookEntry` | `Component` |
| `Attendance/Livewire/AttendanceManager.php` | `AttendanceManager` | `BaseRecordManager` |
| `Attendance/Livewire/StudentClockIn.php` | `StudentClockIn` | `Component` |
| `Schedule/Livewire/ScheduleIndex.php` | `ScheduleIndex` | `Component` |
| `AbsenceRequest/Livewire/AbsenceRequestForm.php` | `AbsenceRequestForm` | `Component` |
| `IndustryAssessment/Livewire/IndustryAssessmentForm.php` | `IndustryAssessmentForm` | `Component` |

## Form Requests

| File | Request | Purpose |
| ---- | ------- | ------- |
| `Logbook/Http/Requests/CreateLogbookEntryRequest.php` | `CreateLogbookEntryRequest` | Logbook entry validation |
| `Attendance/Http/Requests/ClockInRequest.php` | `ClockInRequest` | Clock in validation |
| `Attendance/Http/Requests/ClockOutRequest.php` | `ClockOutRequest` | Clock out validation |
| `AbsenceRequest/Http/Requests/SubmitAbsenceRequest.php` | `SubmitAbsenceRequest` | Absence request validation |

---

## Routes

File: `routes/web/journals.php`
Naming pattern: `journals.{resource}.{action}`

---

## File Organization

```
app/Journals/
├── AbsenceRequest/
│   ├── Actions/
│   │   ├── ProcessAbsenceAction.php
│   │   └── SubmitAbsenceAction.php
│   ├── Entities/AbsenceRequestStatus.php
│   ├── Enums/
│   │   ├── AbsenceReasonType.php
│   │   └── AbsenceRequestStatus.php
│   ├── Http/Requests/SubmitAbsenceRequest.php
│   ├── Livewire/AbsenceRequestForm.php
│   └── Models/AbsenceRequest.php
├── Attendance/
│   ├── Actions/
│   │   ├── ClockInAction.php
│   │   ├── ClockOutAction.php
│   │   ├── CreateAttendanceAction.php
│   │   ├── DeleteAttendanceAction.php
│   │   ├── UpdateAttendanceAction.php
│   │   └── VerifyAttendanceAction.php
│   ├── Entities/AttendanceStatus.php
│   ├── Enums/AttendanceStatus.php
│   ├── Http/Requests/
│   │   ├── ClockInRequest.php
│   │   └── ClockOutRequest.php
│   ├── Livewire/
│   │   ├── AttendanceManager.php
│   │   └── StudentClockIn.php
│   ├── Models/Attendance.php
│   └── Policies/AttendancePolicy.php
├── IndustryAssessment/
│   ├── Actions/SubmitIndustryAssessmentAction.php
│   ├── Livewire/IndustryAssessmentForm.php
│   └── Models/IndustryAssessment.php
├── Logbook/
│   ├── Actions/
│   │   ├── CompileLogbookReportAction.php
│   │   ├── CreateLogbookAction.php
│   │   ├── DeleteLogbookAction.php
│   │   ├── SubmitLogbookAction.php
│   │   └── UpdateLogbookAction.php
│   ├── Entities/LogbookState.php
│   ├── Enums/LogbookStatus.php
│   ├── Http/Requests/CreateLogbookEntryRequest.php
│   ├── Livewire/
│   │   ├── LogbookEntry.php
│   │   └── LogbookManager.php
│   ├── Models/Logbook.php
│   └── Policies/LogbookPolicy.php
└── Schedule/
    ├── Actions/
    │   ├── CreateScheduleAction.php
    │   ├── DeleteScheduleAction.php
    │   └── UpdateScheduleAction.php
    ├── Entities/ScheduleStatus.php
    ├── Livewire/ScheduleIndex.php
    ├── Models/Schedule.php
    └── Policies/SchedulePolicy.php
```

---

## Architectural Integration

- **Submodules**: `Logbook`, `Attendance`, `Schedule`, `AbsenceRequest`, `IndustryAssessment`
- **Business Logic**: `app/Journals/`
- **Routing**: `routes/web/journals.php`
- **Views**: `resources/views/journals/`
- **Testing**: `tests/Feature/Journals/`, `tests/Unit/Journals/`
- **Dependencies**: Enrollment, Program, Core
- **Used By**: Evaluation

*For overview and business context, see [journals.md](journals.md).*
