# Attendance Tracking

**Event:** Recording and managing student daily attendance during the internship.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Events:** [Logbook Workflow](logbook-workflow.md), [Assignment Workflow](assignment-workflow.md)

---

## Overview

Attendance records track whether students are physically present at their internship placement each day. Teachers record attendance and can mark various statuses depending on the student's situation.

## Trigger

- Daily check-in/check-out (student self-service, if enabled)
- Teacher's daily or periodic attendance round

## Pre-conditions

- Student has an **active** registration
- Student is within the internship period
- Internship status is **Active**
- User is logged in with role TEACHER, ADMIN, or SUPER_ADMIN (to record)

## Actors

| Actor | Role | Can record | Can verify | Can manage absences |
|---|---|---|---|---|
| School Teacher | TEACHER | Yes (own students) | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | Yes | Yes | Yes |
| Student | STUDENT | Clock-in/out (if configured) | No | Submit absence request |

---

## Attendance Statuses

Attendance uses a fixed set of values defined in the `AttendanceStatus` enum, with `isOnTime()` and `isExcused()` query methods. See the [System Lifecycle](system-lifecycle.md#attendance-value-set) for the status definitions.

---

## Event A: Teacher Records Attendance

### Flow

```
Teacher → Attendance → Select Date → Mark Students → Save
```

Navigate to the attendance manager for the teacher's supervised students.

1. Teacher selects a date (defaults to today)
2. Views list of students under supervision with active registrations
3. For each student, selects an attendance status from the dropdown
4. Submits — `CreateAttendanceAction` or `UpdateAttendanceAction` saves each record

### Clock-In / Clock-Out (Optional)

If the school configures student self-service attendance:

1. Student clocks in at the start of the day — records time and status (PRESENT or LATE)
2. Student clocks out at the end of the day — records departure time

`ClockInAction` and `ClockOutAction` handle the time capture. These are gated by:

- Active registration
- Within the internship period (`MenteeState::canClockIn()`)
- Not before the configured check-in start time (from operational settings)

---

## Event B: Absence Requests

Students can submit absence requests in advance:

### Flow

```
Student → Submit Absence → Select Type → Provide Reason → Submit
```

| Field | Description |
|---|---|
| **Date** | Date of absence |
| **Type** | Permission, Sick |
| **Reason** | Explanation |
| **Attachment** | Supporting document (medical note, etc.) |

The absence request is submitted for teacher approval. Teachers can approve or reject.

### Attendance Verification

Teachers can verify attendance records, confirming their accuracy:

1. Teacher reviews the attendance record
2. If correct, marks as verified
3. `VerifyAttendanceAction` sets the verified flag

---

## Event C: Attendance Reconciliation

At the end of a period (weekly, monthly, or at internship completion):

1. Teacher reviews all attendance records
2. Reconciles any discrepancies
3. Ensures all absences have been addressed
4. Verifies outstanding attendance entries

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **Active registration required** | Cannot record attendance without active registration |
| **One record per day** | Single attendance entry per student per day |
| **Threshold enforcement** | Late threshold from operational settings |
| **Check-in window** | Earliest check-in time from settings |
| **Teacher scope** | Teachers only see students under their supervision |

## Seamless Connection

Attendance data is used in:

- **[Assessment & Scoring](assessment-scoring.md)** — attendance rate may factor into final grade
- **[Period Closing](period-closing.md)** — attendance records are reconciled before closure
