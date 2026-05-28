# Attendance Domain

## Purpose

Attendance tracks student presence during placement — clock-in/out, absence requests, and
compliance monitoring.

---

## Design Principles

### 1. Immutable Records

Attendance records are immutable after configurable window (default 24h). Corrections
require admin override with audit trail.

### 2. Dual Verification

Attendance is verified by both school mentor and company supervisor before finalization.

---

## Models

| Model | Key Fields |
|---|---|
| `Attendance` | user_id, registration_id, date, clock_in, clock_out, status, is_verified |
| `AbsenceRequest` | user_id, registration_id, start_date, end_date, reason_type, status |

## Actions

| Action | Type |
|---|---|
| `ClockInAction` | Command |
| `ClockOutAction` | Command |
| `CreateAttendanceAction` | Command |
| `UpdateAttendanceAction` | Command |
| `DeleteAttendanceAction` | Command |
| `VerifyAttendanceAction` | Command |
| `SubmitAbsenceAction` | Command |
| `ProcessAbsenceAction` | Command |

## Where to Find It

- `app/Domain/Attendance/Models/`
- `app/Domain/Attendance/Actions/`
