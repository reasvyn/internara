# Attendance Domain
> Last updated: 2026-05-25
> Changes: docs: audit and fix all 20+ domain + reference docs for accuracy


## Purpose

Attendance answers a simple but critical question: was the student present? It records clock-in 
and clock-out times for each session, computes daily attendance status (present, late, absent, 
excused), and manages absence requests. Attendance is a foundational requirement because you 
cannot evaluate a student's performance or grant a completion certificate without first 
confirming they were physically present for the required duration. This domain provides the raw 
temporal data that feeds into completion calculations, mentor oversight dashboards, and 
compliance reporting.

## Boundary

**In scope:** Clock-in and clock-out recording with precise timestamps, automatic attendance 
status computation based on program-specific policy rules, absence request submission by 
students, absence approval workflow (mentor-level for short absences, additional supervisor 
approval for extended absences), attendance policy configuration per program (grace periods, 
minimum daily hours, minimum overall percentage, maximum consecutive absences), attendance 
reports at individual, cohort, and program levels, attendance exception flagging and automated 
notification triggers.

**Out of scope:** Session time definitions and schedule management (Schedule domain defines when 
sessions occur — Attendance reads but does not define session times), calendar management 
(Schedule domain), assignment submission status (Assignment domain), logbook entries (Logbook 
domain), certificate eligibility decisions (Certificate domain consumes attendance data but owns 
the eligibility decision), mentor verification of attendance records (Mentor domain provides a 
view-only dashboard of attendance data).

## Key Concepts

**Clock-in/Clock-out.** The core attendance transaction. Students record their arrival by 
clocking in at their internship site and record their departure by clocking out. Each transaction 
captures: the student's identity, the precise timestamp, and optional metadata (IP address of the 
device used, GPS coordinates if mobile clock-in is enabled and permitted). The system calculates 
the duration between clock-in and clock-out automatically. Clock-in is permitted only during or 
near scheduled session times — clocking in far outside session boundaries is allowed but 
flagged for mentor review. The raw timestamp data is preserved regardless of whether it produces 
a "good" attendance status; the system never discards or rounds timestamps.

**Attendance Status.** Based on clock-in/clock-out data, absence requests, and program policy 
configuration, the system computes a daily attendance status for each student. PRESENT: clock-in 
occurred within the grace period after session start and the minimum hours were logged. LATE: 
clock-in occurred after the grace period expired but is otherwise valid. ABSENT: no clock-in 
recorded and no approved absence covers the day. EXCUSED: an approved absence request covers the 
day, making attendance non-mandatory. HOLIDAY: the day is a scheduled non-working day per the 
program calendar. The status computation is fully automated and deterministic — given the same 
inputs, it always produces the same result.

**Absence Requests.** Students can submit absence requests for days they cannot attend. PLANNED 
absences are submitted in advance (minimum 24 hours notice by default) and include the reason and 
dates. UNPLANNED absences are submitted retroactively (e.g., due to illness) and require a reason 
and optionally supporting documentation (medical certificate, emergency proof, etc.). Each 
request follows an approval workflow: mentor reviews and can approve single-day absences; 
extended absences (configurable threshold, default 3+ consecutive days) require additional 
approval from a supervisor or admin. Approved absences change the daily status to EXCUSED. 
Rejected or pending absences leave the daily status as ABSENT until resolved.

**Attendance Policies.** Each internship program configures its own attendance rules through the 
Attendance domain. Configurable parameters include: grace period duration (minutes after session 
start before late is recorded), minimum daily hours required for a full day of attendance, 
minimum overall attendance percentage required for completion, maximum consecutive unexcused 
absences before automatic escalation, and whether location recording (GPS/IP) is required for 
clock-in. These policies are read and enforced by the attendance status computation engine and 
are also consumed by the Registration domain's completion check logic.

**Compliance Monitoring.** The system proactively monitors attendance against program policies. 
When a student falls below the minimum attendance percentage threshold, their assigned mentor 
receives a notification. When consecutive unexcused absences exceed the program's limit, 
escalation occurs: first notification to the mentor, then if unresolved, notification to the 
program coordinator or admin. Compliance reports are available at the individual level (this 
student's attendance record), cohort level (how is this group doing), and program level (overall 
attendance health). Reports can be filtered by date range, status, and demographic attributes.

## Requirements

### User Stories & Rules

**Daily Attendance**
- **Student:** As a student, I want to clock in and clock out so that my attendance is recorded
- **Student:** As a student, I want to view my attendance record so that I can track my compliance
- Clock-in is personal — only the authenticated user can clock in/out for themselves; proxy clocking is prohibited and detectable
- Attendance status is computed automatically from raw data; manual overrides are logged and preserve the original computed status
- Attendance cannot be recorded without a scheduled session context; clock-in outside any session is recorded but flagged as unscheduled

**Absence Management**
- **Student:** As a student, I want to submit an absence request so that I am excused for planned or unplanned absences
- **Mentor:** As a mentor, I want to approve or reject absence requests so that absences are properly managed
- **Mentor:** As a mentor, I want to view my mentees' attendance so that I can monitor their participation
- Planned absence requests require minimum advance notice (default 24 hours); unplanned requests require a documented reason
- Consecutive unexcused absences beyond the program's threshold trigger automatic notification escalation (mentor → admin)

**Policy & Reporting**
- **Admin:** As an admin, I want to configure attendance policies per program (grace period, minimum hours) so that rules fit each program's needs
- **Admin:** As an admin, I want to generate attendance reports so that I can assess program compliance
- Attendance records are immutable after a configurable time window (default 24 hours) — corrections require admin override with a logged reason
- Each program defines its own minimum attendance percentage required for completion; the percentage is a policy, not a hardcoded constant

**System & Notifications**
- **System:** As the system, I want to compute attendance status automatically from clock-in/out data so that results are deterministic and auditable
- **System:** As the system, I want to notify mentors when attendance drops below thresholds so that issues are addressed early

### Process Flow

```
Daily Attendance Status Computation:

Clock-in/out data ──→ Status Engine ──→ PRESENT
                          │                LATE
                          │                EARLY_OUT
                          │                ABSENT
                          │                PERMISSION (approved absence)
                          │                SICK (approved sick leave)

Absence Request:

PENDING ──→ APPROVED
     │
     ↓
  REJECTED
```

- **PRESENT**: Clock-in within grace period, minimum hours met
- **LATE**: Clock-in after grace period, otherwise valid
- **EARLY_OUT**: Clocked in but clocked out before minimum hours
- **ABSENT**: No clock-in and no approved absence
- **PERMISSION/SICK**: Approved absence covers the day
- Absence requests flow: PENDING → APPROVED or REJECTED
- Attendance records are immutable after a configurable window (default 24 hours)

### Key Operations

| Action | Description |
|--------|-------------|
| `ClockInAction` | Records a student's clock-in with timestamp and optional location data |
| `ClockOutAction` | Records a student's clock-out and computes attendance duration |
| `CreateAttendanceAction` | Manually creates an attendance record (admin override) |
| `UpdateAttendanceAction` | Updates an attendance record within the editable window |
| `DeleteAttendanceAction` | Removes an incorrect attendance record |
| `VerifyAttendanceAction` | Verifies attendance records for accuracy |
| `SubmitAbsenceAction` | Submits an absence request (planned or unplanned) |
| `ProcessAbsenceAction` | Approves or rejects a pending absence request |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Attendance`, `AbsenceRequest` |
| **Entities** | `AttendanceStatus` (clock-out checks, excused status); `AbsenceRequestStatus` (pending/processed state) |
| **Enums** | `AttendanceStatus` — `PRESENT`, `LATE`, `EARLY_OUT`, `ABSENT`, `PERMISSION`, `SICK`; `AbsenceRequestStatus` — `PENDING`, `APPROVED`, `REJECTED`; `AbsenceReasonType` — `SICK`, `PERMISSION`, `EMERGENCY`, `OTHER` |
| **Livewire** | `AttendanceManager`, `StudentClockIn`, `AbsenceRequestForm` |
| **Policy** | `AttendancePolicy` |
| **Http/Requests** | `ClockInRequest`, `ClockOutRequest`, `SubmitAbsenceRequest` |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | Links students to internships so attendance data has program context and correct 
policy application |
| Schedule | Defines session start and end times that determine on-time vs. late status |
| User | Student identity for clock-in; mentor identity for absence approval |
| Core | BaseAction for attendance operations, BaseModel for persistence, SmartLogger for audit 
trail |


