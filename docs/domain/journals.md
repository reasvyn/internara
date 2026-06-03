# Journals Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Student Daily Logbooks, Attendance Logs, and Schedule Calendars

## Purpose

The **Journals** domain manages the student's daily activity logging and attendance reporting during their internship program. This includes daily logbook submittals, geolocation or network clock-in/outs, absence requests, and schedule mappings.

It tracks student activity on a day-to-day basis. Logs and attendance entries are audited by mentors to track participation hours and confirm program compliance.

---

## Design Principles

### 1. Clock-in/out Validation
Attendance tracking records student presence at the placement location:
- Geolocation coordinates or IP networks are logged on clock-in to verify that students are on-site.
- Students must clock-in and clock-out daily. Clock-ins are validated against the student's active `Schedule`.

### 2. Work-focused Daily Logbooks
Students maintain a daily record of tasks completed:
- Each logbook entry records tasks, lessons learned, and challenges faced.
- **Photo Attachments**: Students can upload photos or documents as proof of work, managed via Spatie Media Library under the `photos` collection.
- Logbooks follow a strict status cycle:
  `DRAFT` ➔ `SUBMITTED` ➔ `ACKNOWLEDGED` or `RETURNED`
- Returned logbooks allow edits and resubmission, while acknowledged logs are locked.

### 3. Absence Request Workflow
If a student cannot attend the placement location:
- They submit an `AbsenceRequest` specifying the date, reason category (sick, personal, etc.), and explanatory text.
- Submitting an absence request automatically suspends the requirement to clock-in for that specific date.
- Upon admin or mentor approval, the date status updates to `ABSENT` (excused), preventing attendance penalties.

### 4. Interactive Calendars
The student workspace presents calendar views color-coding days:
- **Green**: Clocked-in / verified presence.
- **Yellow**: Absence pending review / logbook submitted.
- **Blue**: Draft entries.
- **Gray**: Unscheduled/no-activity days.

---

## Domain Boundary

### Technical Ownership
- **Attendance Logging**: Geolocation capture, IP network matching, clock-in and out timestamps.
- **Absence Requests**: Absence requests, reason validation, and review flows.
- **Daily Logbooks**: Task entries, photo proof uploads, and mentor revision loops.
- **Schedules**: Calendar schedules, shift allocations, and active days.

### Dependencies
- **Core**: Uses `BaseModel`, `BaseAction`, `BasePolicy`, and `SmartLogger` for auditing.
- **User**: Scopes records to student and mentor users.
- **Enrollment**: Placements provide location context (host company addresses) to check clock-in coordinates.
- **Guidance**: Mentors supervise journals, submitting logs acknowledgments.

---

## Domain Rules & Invariants

- **R1 — Daily Limit**: A student can submit at most one logbook entry and one attendance log per calendar day.
- **R2 — Clock-in Schedule Validation**: A student cannot clock-in if the current date is not a scheduled day in their active `Schedule` or if an approved absence exists.
- **R3 — Geofence Verification**: Geolocation coordinates submitted on clock-in must lie within 100 meters of the host company coordinates (if geofencing is enabled).
- **R4 — Unmodifiable Acknowledged Logs**: Once a daily logbook status transitions to `ACKNOWLEDGED`, it is locked and cannot be edited by the student.
- **R5 — Absence Priority**: An approved absence request bypasses the daily clock-in requirement and marks the day's attendance status as `EXCUSED_ABSENCE` automatically.

---

## Key Features

- **Mobile Clock-in Portal**: Student mobile UI showing a map, geolocation verification, and instant clock-in/out buttons.
- **Daily Logbook Composer**: Form allowing rich descriptions of tasks and lessons, plus camera photo uploading.
- **Verification Calendar**: Dashboard widget displaying the student's monthly calendar colored by attendance and log status.
- **Absence Manager**: Allows students to upload sick notes and request leave, which mentors can review and approve.
- **Shift Scheduler**: Admin dashboard mapping student work shifts (days, start/end hours) across departments.
- **Bulk Verify Attendance**: Admin/teacher workspace to review and bulk-verify pending attendance records.
