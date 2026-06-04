# Journals — Technical Reference

> Last updated: 2026-06-03
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Journals** domain.

---

## Overview

Manages student logbooks, attendance tracking, and schedule management

### Domain Statistics
- **Actions**: 17 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 7 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 5 domain aggregates

### Aggregates
- `AbsenceRequest`
- `Attendance`
- `IndustryAssessment`
- `Logbook`
- `Schedule`

---

## Dependency Graph

This domain depends on:
- **Core**
- **Enrollment**
- **Guidance**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Attendance/Actions/ClockInAction.php` | `ClockInAction` | `BaseAction` |
| `Aggregates/Attendance/Actions/ClockOutAction.php` | `ClockOutAction` | `BaseAction` |
| `Aggregates/Logbook/Actions/CompileLogbookReportAction.php` | `CompileLogbookReportAction` | `BaseAction` |
| `Aggregates/Attendance/Actions/CreateAttendanceAction.php` | `CreateAttendanceAction` | `BaseAction` |
| `Aggregates/Logbook/Actions/CreateLogbookAction.php` | `CreateLogbookAction` | `BaseAction` |
| `Aggregates/Schedule/Actions/CreateScheduleAction.php` | `CreateScheduleAction` | `BaseAction` |
| `Aggregates/Attendance/Actions/DeleteAttendanceAction.php` | `DeleteAttendanceAction` | `BaseAction` |
| `Aggregates/Logbook/Actions/DeleteLogbookAction.php` | `DeleteLogbookAction` | `BaseAction` |
| `Aggregates/Schedule/Actions/DeleteScheduleAction.php` | `DeleteScheduleAction` | `BaseAction` |
| `Aggregates/AbsenceRequest/Actions/ProcessAbsenceAction.php` | `ProcessAbsenceAction` | `BaseAction` |
| `Aggregates/AbsenceRequest/Actions/SubmitAbsenceAction.php` | `SubmitAbsenceAction` | `BaseAction` |
| `Aggregates/IndustryAssessment/Actions/SubmitIndustryAssessmentAction.php` | `SubmitIndustryAssessmentAction` | `BaseAction` |
| `Aggregates/Logbook/Actions/SubmitLogbookAction.php` | `SubmitLogbookAction` | `BaseAction` |
| `Aggregates/Attendance/Actions/UpdateAttendanceAction.php` | `UpdateAttendanceAction` | `BaseAction` |
| `Aggregates/Logbook/Actions/UpdateLogbookAction.php` | `UpdateLogbookAction` | `BaseAction` |
| `Aggregates/Schedule/Actions/UpdateScheduleAction.php` | `UpdateScheduleAction` | `BaseAction` |
| `Aggregates/Attendance/Actions/VerifyAttendanceAction.php` | `VerifyAttendanceAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/AbsenceRequest/Models/AbsenceRequest.php` | `AbsenceRequest` |
| `Aggregates/Attendance/Models/Attendance.php` | `Attendance` |
| `Aggregates/IndustryAssessment/Models/IndustryAssessment.php` | `IndustryAssessment` |
| `Aggregates/Logbook/Models/Logbook.php` | `Logbook` |
| `Aggregates/Schedule/Models/Schedule.php` | `Schedule` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/AbsenceRequest/Livewire/AbsenceRequestForm.php` | `AbsenceRequestForm` | `Component` |
| `Aggregates/Attendance/Livewire/AttendanceManager.php` | `AttendanceManager` | `Component` |
| `Aggregates/IndustryAssessment/Livewire/IndustryAssessmentForm.php` | `IndustryAssessmentForm` | `Component` |
| `Aggregates/Logbook/Livewire/LogbookEntry.php` | `LogbookEntry` | `Component` |
| `Aggregates/Logbook/Livewire/LogbookManager.php` | `LogbookManager` | `BaseRecordManager` |
| `Aggregates/Schedule/Livewire/ScheduleIndex.php` | `ScheduleIndex` | `Component` |
| `Aggregates/Attendance/Livewire/StudentClockIn.php` | `StudentClockIn` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` |
| `Aggregates/Logbook/Policies/LogbookPolicy.php` | `LogbookPolicy` |
| `Aggregates/Schedule/Policies/SchedulePolicy.php` | `SchedulePolicy` |

---

## File Organization

```
app/Domain/Journals/
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

*For overview and business context, see [journals.md](journals.md)*
