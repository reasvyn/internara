# Attendance — API Reference
> Last updated: 2026-05-25
> Changes: docs: update entire documentation to reflect actual implementation


Total: 22 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Actions/ClockInAction.php` | `ClockInAction` | `BaseAction` | Records clock-in with IP tracking and schedule validation |
| `Attendance/Actions/ClockOutAction.php` | `ClockOutAction` | `BaseAction` | Records clock-out with IP tracking |
| `Attendance/Actions/CreateAttendanceAction.php` | `CreateAttendanceAction` | `BaseAction` | Creates an attendance record |
| `Attendance/Actions/DeleteAttendanceAction.php` | `DeleteAttendanceAction` | `BaseAction` | Deletes an attendance record |
| `Attendance/Actions/ProcessAbsenceAction.php` | `ProcessAbsenceAction` | `BaseAction` | Approves or rejects an absence request |
| `Attendance/Actions/SubmitAbsenceAction.php` | `SubmitAbsenceAction` | `BaseAction` | Submits a new absence request |
| `Attendance/Actions/UpdateAttendanceAction.php` | `UpdateAttendanceAction` | `BaseAction` | Updates an attendance record |
| `Attendance/Actions/VerifyAttendanceAction.php` | `VerifyAttendanceAction` | `BaseAction` | Verifies an attendance record for validity |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Entities/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `BaseEntity` | Read-only DTO for absence request status |
| `Attendance/Entities/AttendanceStatus.php` | `AttendanceStatus` | `BaseEntity` | Read-only DTO for attendance day status |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Attendance/Enums/AbsenceReasonType.php` | `AbsenceReasonType` | `LabelEnum` | Absence reason types (sick, personal, etc.) |
| `Attendance/Enums/AbsenceRequestStatus.php` | `AbsenceRequestStatus` | `LabelEnum`, `StatusEnum` | Absence request lifecycle status |
| `Attendance/Enums/AttendanceStatus.php` | `AttendanceStatus` | `LabelEnum`, `StatusEnum` | Attendance day status |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Http/Requests/ClockInRequest.php` | `ClockInRequest` | `FormRequest` | Validation for clock-in |
| `Attendance/Http/Requests/ClockOutRequest.php` | `ClockOutRequest` | `FormRequest` | Validation for clock-out |
| `Attendance/Http/Requests/SubmitAbsenceRequest.php` | `SubmitAbsenceRequest` | `FormRequest` | Validation for absence submission |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Livewire/AbsenceRequestForm.php` | `AbsenceRequestForm` | `Component` | Absence request submission form |
| `Attendance/Livewire/AttendanceManager.php` | `AttendanceManager` | `Component` | Manages attendance records and absence requests |
| `Attendance/Livewire/StudentClockIn.php` | `StudentClockIn` | `Component` | Student clock-in/out interface |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Models/AbsenceRequest.php` | `AbsenceRequest` | `BaseModel` | Eloquent model for absence requests |
| `Attendance/Models/Attendance.php` | `Attendance` | `BaseModel` | Eloquent model for attendance logs |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Attendance/Policies/AttendancePolicy.php` | `AttendancePolicy` | `BasePolicy` | Authorization for attendance operations |

## Where to Find It

- `app/Domain/Attendance/Models/`
- `app/Domain/Attendance/Actions/`

## Dependency Graph

```
Attendance Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── User         → User model (attendee identity)
├── Registration → Registration records (attendance context)
├── Mentee       → Mentee records (student attendance)
└── Mentor       → Mentor records (supervisor attendance)
```

Consumed by:
  Internship (closure requirements), Mentee (progress tracking),
  Mentor (supervision records)

