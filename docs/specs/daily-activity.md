# Daily Activity — Logbook, Attendance & Absence Requests

> **Last updated:** 2026-07-22 **Changes:** feat — split from journals.md; student daily operations:
> logbook with one-per-day enforcement, attendance clock-in/out with GPS, absence request workflow

## Description

Specification of the student-facing daily activity features: logbook entries (daily journal with
mentor verification), attendance tracking (clock-in/out with GPS and auto-computed duration), and
absence requests (planned/unplanned with approval workflow). These features share the `attendances`
table and enforce one-record-per-student-per-day constraints.

See also: [supervision.md](supervision.md) — teacher/supervisor oversight features (supervision logs,
monitoring visits, cross-role proxy, compliance monitoring).

---

## 1. Problem Statements

### PS-1 — Daily Activity Documentation for PKL Compliance

Indonesian PKL regulations require students to maintain daily activity records. Without structured
logbook entries, schools cannot demonstrate compliance during accreditation audits. The system
must enforce one entry per student per calendar day and track submission/verification status.

### PS-2 — Attendance Tracking With GPS and Duration

Manual attendance sheets are easily falsified and lose temporal precision. The system must capture
clock-in/out times with optional GPS metadata, compute duration automatically, and enforce
immutability after a grace period.

### PS-3 — Absence Request and Approval Workflow

Students may need planned or unplanned absences. Without a formal request system, absences go
unrecorded, affecting compliance calculations. Single-day absences need mentor approval; extended
absences need coordinator approval.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Enforce one logbook entry per student per calendar day with draft/submitted/verified workflow |
| G2  | Support attendance clock-in/out with GPS, auto-duration, and immutability after grace period |
| G3  | Manage absence requests with single-day (mentor) and extended (coordinator) approval |
| G4  | Generate PDF logbook reports for a registration |
| G5  | Support file attachments (photos) on logbook entries via MediaLibrary |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | GPS-based geofencing enforcement (GPS is metadata, not enforced) |
| NG2  | Real-time location tracking during attendance |
| NG3  | Automated absence escalation to school administration |
| NG4  | Export of attendance data to external HR systems |

---

## 3. User Stories / Use Cases

### UC-1 — Student Submits Daily Logbook Entry

**Actor:** Student
**Preconditions:** Student has active registration; no entry exists for today
**Flow:**
1. Student navigates to `/student/logbook`
2. `LogbookEntry` shows existing entries and today's form
3. Student fills content (activities, learnings, challenges, future plans), uploads photos
4. Calls `SubmitLogbookAction::execute(user, data)` which uses `updateOrCreate` keyed on (user_id, date)
5. Attaches photos via MediaLibrary `photos` collection
**Postconditions:** Logbook entry created/submitted with SUBMITTED status; one-per-day enforced

### UC-2 — Supervisor Verifies Logbook Entry

**Actor:** Industry Supervisor
**Preconditions:** SUBMITTED logbook entry exists; supervisor is assigned as mentor
**Flow:**
1. Supervisor navigates to logbook manager (admin/teacher/supervisor view)
2. Reviews entry content and photos
3. Adds mentor feedback, updates status to VERIFIED via `UpdateLogbookAction`
**Postconditions:** Entry verified; supervisor feedback stored

### UC-3 — Student Clocks In and Out

**Actor:** Student
**Preconditions:** No existing attendance record for today
**Flow:**
1. Student navigates to `/student/attendance`
2. `StudentClockIn` shows today's status
3. Student clicks "Clock In" — `ClockInAction::execute(user, data, ip)` creates record with status PRESENT
4. Event `AttendanceClockIn` dispatched
5. Later, student clicks "Clock Out" — `ClockOutAction::execute(user, data, ip)` updates clock_out and GPS
6. Event `AttendanceClockOut` dispatched
**Postconditions:** Attendance record with clock_in, clock_out, auto-computed duration

### UC-4 — Student Requests Absence

**Actor:** Student
**Preconditions:** Student has active registration
**Flow:**
1. Student navigates to `/student/attendance/absence`
2. `AbsenceRequestForm` shows existing requests and form
3. Student selects date, reason type (sick/permission/emergency/other), description, optional attachment
4. Calls `SubmitAbsenceAction::execute(user, registrationId, data)`
5. Record created with `absence_status = PENDING`
**Postconditions:** Absence request pending approval

### UC-5 — Teacher Approves/Rejects Absence

**Actor:** Teacher (Mentor)
**Preconditions:** PENDING absence request exists
**Flow:**
1. Teacher views pending absences in `AttendanceManager`
2. Reviews request details and attachment
3. Approves or rejects via `ProcessAbsenceAction::execute(absence, processor, status, notes)`
**Postconditions:** Absence status updated to APPROVED or REJECTED

---

## 4. Functional Requirements

### Logbook

| ID   | Requirement |
| ---- | ----------- |
| FR-LB1 | `SubmitLogbookAction` must enforce one entry per student per calendar day via `updateOrCreate` on `(user_id, date)` |
| FR-LB2 | `Logbook` model must use `#[Fillable]` with `user_id`, `registration_id`, `date`, `content`, `learning_outcomes`, `status`, `is_verified`, `verified_by`, `verified_at`, `mentor_feedback`, `supervisor_note`, `supervisor_reviewed_at`, `supervisor_id` |
| FR-LB3 | `LogbookStatus` must define: DRAFT, SUBMITTED, VERIFIED, REVISION_REQUIRED |
| FR-LB4 | Valid transitions: DRAFT→[SUBMITTED]; SUBMITTED→[VERIFIED, REVISION_REQUIRED]; REVISION_REQUIRED→[DRAFT]; VERIFIED→[] |
| FR-LB5 | `Logbook` must support photo attachments via MediaLibrary `photos` collection (jpeg, png, webp, heic, heif) |
| FR-LB6 | `LogbookPolicy` must allow: create (student), update (admin OR owner when not SUBMITTED), view (admin/owner/mentorProxy) |
| FR-LB7 | `CreateLogbookAction` (admin/teacher) must resolve active registration and reject if none exists |
| FR-LB8 | `CompileLogbookReportAction` must generate a PDF from verified entries with media, using DomPDF |

### Attendance

| ID   | Requirement |
| ---- | ----------- |
| FR-AT1 | `ClockInAction` must reject if no active registration or if already clocked in today |
| FR-AT2 | `ClockInAction` must create record with status PRESENT, store IP and optional GPS |
| FR-AT3 | `ClockOutAction` must reject if no clock-in or already clocked out |
| FR-AT4 | `ClockOutAction` must update clock_out time, IP, and GPS coordinates |
| FR-AT5 | `AttendanceStatus` must define: PRESENT, LATE, EARLY_OUT, ABSENT, PERMISSION, SICK (all terminal) |
| FR-AT6 | Unique constraint on `(user_id, date)` — one attendance per student per day |
| FR-AT7 | `AttendancePolicy` must allow: create (student), verify (admin/mentorProxy), update (admin), delete (admin) |
| FR-AT8 | `VerifyAttendanceAction` must set `is_verified=true`, `verified_by`, `verified_at` |
| FR-AT9 | `AttendanceManager` must display pending absence requests for teacher/admin approval |

### AbsenceRequest

| ID   | Requirement |
| ---- | ----------- |
| FR-AR1 | `AbsenceRequest` model shares `attendances` table with Attendance, filtered by global scope `whereNotNull('absence_type')` |
| FR-AR2 | `AbsenceReasonType` must define: SICK, PERMISSION, EMERGENCY, OTHER |
| FR-AR3 | `AbsenceRequestStatus` must define: PENDING, APPROVED, REJECTED |
| FR-AR4 | `requiresAttachment()` must return true for SICK and EMERGENCY reason types |
| FR-AR5 | `SubmitAbsenceAction` must create record with `absence_status = PENDING` |
| FR-AR6 | `ProcessAbsenceAction` must reject if already processed (not PENDING) |
| FR-AR7 | `ProcessAbsenceAction` must set `absence_status`, `absence_processed_by`, `absence_processed_at`, `absence_admin_notes` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All mutations must be authorized via submodule-specific Policies |
| NFR-S2 | Attendance records must become immutable after configurable grace period (default 24h from clock-out) |
| NFR-S3 | Absence request processing must be idempotent — double-approval must be rejected |
| NFR-S4 | GPS coordinates must be optional (not all devices provide location) |
| NFR-P1 | Logbook entry list must load in < 500ms for 90 entries (one per day for 3-month PKL) |
| NFR-P2 | Attendance clock-in/out must complete in < 1s |
| NFR-P3 | PDF logbook report generation must complete in < 10s for 90 entries |
| NFR-R1 | Logbook submission must be wrapped in a database transaction |
| NFR-R2 | Clock-in/out must be atomic — no race condition on daily unique constraint |
| NFR-U1 | Student clock-in UI must show current status (not clocked in / clocked in at HH:MM) |
| NFR-U2 | Pending absence requests must be prominently displayed on teacher dashboard |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Logbook Model

```
App\Journals\Logbook\Models\Logbook
  Table: logbooks (UUID PK)
  Implements: HasMedia
  Fillable: user_id, registration_id, date, content, learning_outcomes, status, is_verified,
            verified_by, verified_at, mentor_feedback, supervisor_note, supervisor_reviewed_at, supervisor_id
  Casts: date → date, status → LogbookStatus, is_verified → boolean
  Relations: user() BelongsTo User, registration() BelongsTo Registration,
             verifier() BelongsTo User, supervisor() BelongsTo User
  Media: photos (jpeg, png, webp, heic, heif)
  Unique: (user_id, date), (registration_id, date)
  Bridge: asLogbookState() → LogbookState
```

### Attendance Model

```
App\Journals\Attendance\Models\Attendance
  Table: attendances (UUID PK)
  Fillable: user_id, registration_id, date, clock_in, clock_out, clock_in_ip, clock_out_ip,
            clock_in_latitude, clock_in_longitude, clock_out_latitude, clock_out_longitude,
            status, absence_type, absence_reason, absence_attachment, absence_status,
            absence_processed_by, absence_processed_at, absence_admin_notes,
            is_verified, verified_by, verified_at, notes
  Casts: date → date, status → AttendanceStatus, absence_type → AbsenceReasonType,
         absence_status → AbsenceRequestStatus, is_verified → boolean
  Relations: user() BelongsTo User, registration() BelongsTo Registration, verifier() BelongsTo User
  Unique: (user_id, date)
  Bridge: asAttendanceState() → AttendanceState
```

### AbsenceRequest Model

```
App\Journals\AbsenceRequest\Models\AbsenceRequest
  Table: attendances (shared with Attendance, global scope: whereNotNull('absence_type'))
  Fillable: user_id, registration_id, date, absence_type, absence_reason, absence_attachment,
            absence_status, absence_processed_by, absence_processed_at, absence_admin_notes
  Casts: date → date, absence_type → AbsenceReasonType, absence_status → AbsenceRequestStatus
  Relations: user() BelongsTo User, processor() BelongsTo User, registration() BelongsTo Registration
```

### Enums

| Enum | Cases |
| ---- | ----- |
| `LogbookStatus` | DRAFT, SUBMITTED, VERIFIED, REVISION_REQUIRED |
| `AttendanceStatus` | PRESENT, LATE, EARLY_OUT, ABSENT, PERMISSION, SICK |
| `AbsenceReasonType` | SICK, PERMISSION, EMERGENCY, OTHER |
| `AbsenceRequestStatus` | PENDING, APPROVED, REJECTED |

### Actions (13 total)

**Logbook (5):** `CreateLogbookAction`, `UpdateLogbookAction`, `DeleteLogbookAction`, `SubmitLogbookAction`, `CompileLogbookReportAction` (Read)
**Attendance (6):** `ClockInAction`, `ClockOutAction`, `CreateAttendanceAction`, `UpdateAttendanceAction`, `DeleteAttendanceAction`, `VerifyAttendanceAction`
**AbsenceRequest (2):** `SubmitAbsenceAction`, `ProcessAbsenceAction`

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `AttendanceClockIn` | `ClockInAction` |
| `AttendanceClockOut` | `ClockOutAction` |

### Policies

| Policy | Key Rules |
| ------ | --------- |
| `LogbookPolicy` | create: student; update: admin/owner(not SUBMITTED); view: admin/owner/mentorProxy |
| `AttendancePolicy` | create: student; verify: admin/mentorProxy; update/delete: admin |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /student/logbook` | `LogbookEntry` | `student.logbook` | auth, student |
| `GET /student/attendance` | `StudentClockIn` | `student.attendance` | auth, student |
| `GET /student/attendance/absence` | `AbsenceRequestForm` | `student.attendance.absence` | auth, student |
| `GET /admin/attendance` | `AttendanceManager` | `sysadmin.attendance` | auth, admin |
| `GET /admin/logbook` | `LogbookManager` | `sysadmin.logbook` | auth, admin/teacher/supervisor |
| `GET /admin/logbook/report/{registration}` | `LogbookReportController` | `sysadmin.logbook.report` | auth, admin/teacher/supervisor |

### Database Schema

```
attendances:
  id: uuid (PK), user_id: FK→users (cascade), registration_id: FK→registrations (cascade)
  date, clock_in, clock_out (time), GPS lat/lng (in/out), IP (in/out)
  status (indexed), absence_type, absence_reason, absence_attachment, absence_status
  absence_processed_by: FK→users (set null), absence_processed_at, absence_admin_notes
  is_verified, verified_by: FK→users (set null), verified_at, notes
  Unique: (user_id, date)

logbooks:
  id: uuid (PK), user_id: FK→users (cascade), registration_id: FK→registrations (cascade)
  date, content (text), learning_outcomes (text, nullable)
  status (default 'draft'), is_verified, verified_by: FK→users (set null), verified_at
  mentor_feedback, supervisor_note, supervisor_reviewed_at, supervisor_id: FK→users (set null)
  Unique: (user_id, date), (registration_id, date)
```

---

## 7. Design Decisions

### DD-1 — Shared Table for Attendance and AbsenceRequest

**Decision:** `AbsenceRequest` and `Attendance` share the `attendances` table, differentiated by a global scope (`whereNotNull('absence_type')` for absences).
**Rationale:** Both represent a student's presence status for a given day. Sharing the table avoids duplicate schema for user_id, registration_id, and date. The global scope provides clean model separation without raw queries.
**Trade-off:** Shared table means migration changes affect both models. Rejected alternative: separate `absence_requests` table (duplicates common columns, complicates "today's status" queries).

### DD-2 — One-Per-Day Enforcement via updateOrCreate

**Decision:** `SubmitLogbookAction` uses `Logbook::updateOrCreate` keyed on `(user_id, date)` instead of a create-then-check pattern.
**Rationale:** Atomic upsert prevents race conditions where two concurrent requests could both pass the "no existing entry" check and create duplicates. The unique database constraint provides a second safety net.
**Trade-off:** `updateOrCreate` overwrites content if called twice on the same day. Rejected alternative: check-then-create (vulnerable to race condition).

### DD-3 — Global Scope for Attendance Status Separation

**Decision:** `AttendanceStatus` includes both present-state (PRESENT, LATE, EARLY_OUT) and absence-state (ABSENT, PERMISSION, SICK) values in one enum.
**Rationale:** A student's daily status is a single concept — they were either present (with variations) or absent (with reasons). Separating into two enums would require a status-type discriminator and complicate queries.
**Trade-off:** The enum has more cases than strictly needed for each context. Rejected alternative: two separate enums (creates ambiguity in the `status` column type).

---

## 8. Success Metrics

### Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate logbook entries per day | 0 | Unique constraint + updateOrCreate |
| Duplicate attendance per day | 0 | Unique constraint on (user_id, date) |
| Late absence approvals | 0 | ProcessAbsenceAction checks PENDING status |

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Logbook list (90 entries) | < 500ms | Student's entries for 3-month PKL |
| Clock-in/out | < 1s | Action execution + event dispatch |
| PDF report generation | < 10s | 90 entries with media |

### User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Clock-in status clarity | Real-time display | Shows "Not clocked in" or "Clocked in at HH:MM" |
| Pending absence visibility | Prominent on teacher view | AttendanceManager tab for pending requests |
| Photo upload on logbook | < 5s per photo | MediaLibrary storage |

### Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | All entities import no Actions |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## Quick References

- `app/Journals/Logbook/` — Logbook submodule (Actions, Entities, Enums, Livewire, Models, Policies)
- `app/Journals/Attendance/` — Attendance submodule (Actions, Entities, Enums, Events, Livewire, Models, Policies)
- `app/Journals/AbsenceRequest/` — AbsenceRequest submodule (Actions, Entities, Enums, Livewire, Models)
- `database/migrations/2026_01_04_000005_create_attendances_table.php` — Attendance schema
- `database/migrations/2026_01_04_000006_create_logbooks_table.php` — Logbook schema
- `routes/web/journals.php` — Route definitions
- `docs/modules/journals.md` — Module conceptual documentation
- **Related spec:** [supervision.md](supervision.md) — supervision logs, monitoring visits, cross-role proxy, compliance
