# Journals — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Journals domain

This reference defines the structured aggregates and code layout within the **Journals** domain.

---

## 1. Attendance Aggregate
Tracks daily student clock-in/outs, IP networks, geolocation coords, and locks attendance records.

- **Eloquent Models**:
  - `Attendance` (`app/Domain/Journals/Models/Attendance.php`)
- **Policies**:
  - `AttendancePolicy` (`app/Domain/Journals/Policies/AttendancePolicy.php`)
- **Command Actions**:
  - `ClockInAction` (`app/Domain/Journals/Actions/ClockInAction.php`)
  - `ClockOutAction` (`app/Domain/Journals/Actions/ClockOutAction.php`)
  - `CreateAttendanceAction` (`app/Domain/Journals/Actions/CreateAttendanceAction.php`)
  - `UpdateAttendanceAction` (`app/Domain/Journals/Actions/UpdateAttendanceAction.php`)
  - `DeleteAttendanceAction` (`app/Domain/Journals/Actions/DeleteAttendanceAction.php`)
  - `VerifyAttendanceAction` (`app/Domain/Journals/Actions/VerifyAttendanceAction.php`)
- **Livewire UI Components**:
  - `StudentClockIn` (`app/Domain/Journals/Livewire/StudentClockIn.php`)
  - `AttendanceManager` (`app/Domain/Journals/Livewire/AttendanceManager.php`)
- **Form Requests / Validations**:
  - `ClockInRequest` (`app/Domain/Journals/Http/Requests/ClockInRequest.php`)
  - `ClockOutRequest` (`app/Domain/Journals/Http/Requests/ClockOutRequest.php`)
- **Entities (Domain Rules)**:
  - `AttendanceStatus` (`app/Domain/Journals/Entities/AttendanceStatus.php`)
- **Enums**:
  - `AttendanceStatus` (`app/Domain/Journals/Enums/AttendanceStatus.php`)

---

## 2. Absence Aggregate
Manages leave requests, sick note document uploads, and leaves approvals.

- **Eloquent Models**:
  - `AbsenceRequest` (`app/Domain/Journals/Models/AbsenceRequest.php`)
- **Command Actions**:
  - `SubmitAbsenceAction` (`app/Domain/Journals/Actions/SubmitAbsenceAction.php`)
  - `ProcessAbsenceAction` (`app/Domain/Journals/Actions/ProcessAbsenceAction.php`)
- **Livewire UI Components**:
  - `AbsenceRequestForm` (`app/Domain/Journals/Livewire/AbsenceRequestForm.php`)
- **Form Requests / Validations**:
  - `SubmitAbsenceRequest` (`app/Domain/Journals/Http/Requests/SubmitAbsenceRequest.php`)
- **Entities (Domain Rules)**:
  - `AbsenceRequestStatus` (`app/Domain/Journals/Entities/AbsenceRequestStatus.php`)
- **Enums**:
  - `AbsenceRequestStatus` (`app/Domain/Journals/Enums/AbsenceRequestStatus.php`)
  - `AbsenceReasonType` (`app/Domain/Journals/Enums/AbsenceReasonType.php`)

---

## 3. Logbook Aggregate
Handles compilation of work logs, image attachments for proof of work, and mentor return-for-revision workflows.

- **Eloquent Models**:
  - `Logbook` (`app/Domain/Journals/Models/Logbook.php`)
- **Policies**:
  - `LogbookPolicy` (`app/Domain/Journals/Policies/LogbookPolicy.php`)
- **Command Actions**:
  - `CreateLogbookAction` (`app/Domain/Journals/Actions/CreateLogbookAction.php`)
  - `UpdateLogbookAction` (`app/Domain/Journals/Actions/UpdateLogbookAction.php`)
  - `SubmitLogbookAction` (`app/Domain/Journals/Actions/SubmitLogbookAction.php`)
  - `DeleteLogbookAction` (`app/Domain/Journals/Actions/DeleteLogbookAction.php`)
- **Livewire UI Components**:
  - `LogbookEntry` (`app/Domain/Journals/Livewire/LogbookEntry.php`)
  - `LogbookManager` (`app/Domain/Journals/Livewire/LogbookManager.php`)
- **Form Requests / Validations**:
  - `CreateLogbookRequest` (`app/Domain/Journals/Http/Requests/CreateLogbookRequest.php`)
- **Entities (Domain Rules)**:
  - `LogbookState` (`app/Domain/Journals/Entities/LogbookState.php`)
- **Enums**:
  - `LogbookStatus` (`app/Domain/Journals/Enums/LogbookStatus.php`)

---

## 4. Schedule Aggregate
Configures calendar schedules and shift parameters.

- **Eloquent Models**:
  - `Schedule` (`app/Domain/Journals/Models/Schedule.php`)
- **Policies**:
  - `SchedulePolicy` (`app/Domain/Journals/Policies/SchedulePolicy.php`)
- **Command Actions**:
  - `CreateScheduleAction` (`app/Domain/Journals/Actions/CreateScheduleAction.php`)
  - `UpdateScheduleAction` (`app/Domain/Journals/Actions/UpdateScheduleAction.php`)
  - `DeleteScheduleAction` (`app/Domain/Journals/Actions/DeleteScheduleAction.php`)
- **Livewire UI Components**:
  - `ScheduleIndex` (`app/Domain/Journals/Livewire/ScheduleIndex.php`)
- **Entities (Domain Rules)**:
  - `ScheduleStatus` (`app/Domain/Journals/Entities/ScheduleStatus.php`)
