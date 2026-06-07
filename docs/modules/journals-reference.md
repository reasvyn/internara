# Journals — Technical Reference

> Last updated: 2026-06-03 Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Journals** module.

---

## Overview

Manages student logbooks, attendance tracking, and schedule management

### Module Statistics

- **Actions**: 17 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 7 UI components
- **Policies**: 3 authorization rules
- **Submodules**: 5 module submodules

### Submodules

- `AbsenceRequest`
- `Attendance`
- `IndustryAssessment`
- `Logbook`
- `Schedule`

---

## Dependency Graph

This module depends on:

- **Core**
- **Enrollment**
- **Guidance**
- **Program**
- **User**

---

## Actions

| File                                                            | Class                            | Extends      |
| --------------------------------------------------------------- | -------------------------------- | ------------ |
| `Attendance/Actions/ClockInAction.php`                          | `ClockInAction`                  | `BaseAction` |
| `Attendance/Actions/ClockOutAction.php`                         | `ClockOutAction`                 | `BaseAction` |
| `Logbook/Actions/CompileLogbookReportAction.php`                | `CompileLogbookReportAction`     | `BaseAction` |
| `Attendance/Actions/CreateAttendanceAction.php`                 | `CreateAttendanceAction`         | `BaseAction` |
| `Logbook/Actions/CreateLogbookAction.php`                       | `CreateLogbookAction`            | `BaseAction` |
| `Schedule/Actions/CreateScheduleAction.php`                     | `CreateScheduleAction`           | `BaseAction` |
| `Attendance/Actions/DeleteAttendanceAction.php`                 | `DeleteAttendanceAction`         | `BaseAction` |
| `Logbook/Actions/DeleteLogbookAction.php`                       | `DeleteLogbookAction`            | `BaseAction` |
| `Schedule/Actions/DeleteScheduleAction.php`                     | `DeleteScheduleAction`           | `BaseAction` |
| `AbsenceRequest/Actions/ProcessAbsenceAction.php`               | `ProcessAbsenceAction`           | `BaseAction` |
| `AbsenceRequest/Actions/SubmitAbsenceAction.php`                | `SubmitAbsenceAction`            | `BaseAction` |
| `IndustryAssessment/Actions/SubmitIndustryAssessmentAction.php` | `SubmitIndustryAssessmentAction` | `BaseAction` |
| `Logbook/Actions/SubmitLogbookAction.php`                       | `SubmitLogbookAction`            | `BaseAction` |
| `Attendance/Actions/UpdateAttendanceAction.php`                 | `UpdateAttendanceAction`         | `BaseAction` |
| `Logbook/Actions/UpdateLogbookAction.php`                       | `UpdateLogbookAction`            | `BaseAction` |
| `Schedule/Actions/UpdateScheduleAction.php`                     | `UpdateScheduleAction`           | `BaseAction` |
| `Attendance/Actions/VerifyAttendanceAction.php`                 | `VerifyAttendanceAction`         | `BaseAction` |

---

## Models

| File                                               | Class                |
| -------------------------------------------------- | -------------------- |
| `AbsenceRequest/Models/AbsenceRequest.php`         | `AbsenceRequest`     |
| `Attendance/Models/Attendance.php`                 | `Attendance`         |
| `IndustryAssessment/Models/IndustryAssessment.php` | `IndustryAssessment` |
| `Logbook/Models/Logbook.php`                       | `Logbook`            |
| `Schedule/Models/Schedule.php`                     | `Schedule`           |

---

## Livewire Components

| File                                                     | Component                | Extends             |
| -------------------------------------------------------- | ------------------------ | ------------------- |
| `AbsenceRequest/Livewire/AbsenceRequestForm.php`         | `AbsenceRequestForm`     | `Component`         |
| `Attendance/Livewire/AttendanceManager.php`              | `AttendanceManager`      | `Component`         |
| `IndustryAssessment/Livewire/IndustryAssessmentForm.php` | `IndustryAssessmentForm` | `Component`         |
| `Logbook/Livewire/LogbookEntry.php`                      | `LogbookEntry`           | `Component`         |
| `Logbook/Livewire/LogbookManager.php`                    | `LogbookManager`         | `BaseRecordManager` |
| `Schedule/Livewire/ScheduleIndex.php`                    | `ScheduleIndex`          | `Component`         |
| `Attendance/Livewire/StudentClockIn.php`                 | `StudentClockIn`         | `Component`         |

---

## Authorization Policies

| File                                       | Policy             |
| ------------------------------------------ | ------------------ |
| `Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` |
| `Logbook/Policies/LogbookPolicy.php`       | `LogbookPolicy`    |
| `Schedule/Policies/SchedulePolicy.php`     | `SchedulePolicy`   |

---

## File Organization

```
app/Journals/
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

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Attendance`, `AbsenceRequest`, `Logbook`, `IndustryAssessment`, `Schedule`
- **Business Logic (`app/`)**: Located in
  [app/Journals/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Journals/)
- **Routing (`routes/`)**:
  [routes/web/journals.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/journals.php)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/journals/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/journals/)
- **Testing (`tests/`)**: Feature `tests/Feature/Journals/`, Unit `tests/Unit/Journals/`

_For overview and business context, see [journals.md](journals.md)_
