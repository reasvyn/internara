# 1KSWL — Daily Activity Journal & Student Attendance

**Document Version:** 1.0.0  
**Status:** Approved Specification  
**Module:** `Journal`  
**Phase:** `Phase 8 — Daily Operations`  
**Governing Spec:** #485 (`[1KSWL]` Daily Activity), with references to #480 (`[J9GBH]` Placement) and #486 (`[2EHSE]` Supervision)  

---

## Description

This specification governs the student-facing daily operational recording and tracking subsystem of the Internara platform. It defines the daily fieldwork lifecycle: GPS-geofenced attendance check-in and check-out, structured absence and medical leave requests, and daily reflective logbook entries with photographic media verification. Together, these records form the canonical legal and academic evidence proving that an industrial internship placement occurred, satisfying Ministry of Education accreditation standards and corporate partnership agreements.

---

## 1. Problem Statements

- **PS-1 — Fabricated Logbooks and Retroactive End-of-Semester Cramming**: In manual paper-based operations, students frequently fail to write daily reflections, instead cramming 60 to 90 days of fabricated entries during a single weekend immediately prior to final book collection. Paper logbooks carry no verifiable submission timestamp, enabling fiction to pass as authentic daily learning.
- **PS-2 — Attendance Disputes Without an Authoritative Audit Trail**: Informal attendance reporting (e.g., WhatsApp messages like "hadir pak 🙏" or paper signature sheets on industrial reception desks) lacks independent geolocation, tamper-proof device timestamps, and cryptographic author binding. Disputes between schools, students, and companies over unexcused absences frequently collapse into untracked arguments.
- **PS-3 — Unrecorded Illness and Truancy False Positives**: When a student falls ill and verbally notifies an on-site co-worker, the information rarely reaches the school supervising teacher. The unrecorded absence appears as an unexplained truancy during end-of-semester grade card compilation, unfairly penalizing the student's disciplinary score.
- **PS-4 — Supervisor Disengagement Blocking Student Records**: Industry supervisors with heavy daily workloads frequently delay physical signature sign-offs for weeks. Without a verified attendance record or approved logbook, the student cannot be cleared for final grading or certification.

---

## 2. Goals & Non-Goals

### Goals

- **Tamper-Proof Geofenced Attendance** — Record daily arrival (clock-in) and departure (clock-out) with latitude, longitude, network IP, and server timestamps, validating distance against partner company coordinates (configurable geofence, default 100 meters). *Why:* Guarantees physical presence at the workplace and eliminates attendance proxy fraud.
- **One-Entry-Per-Day Invariant** — Enforce exactly one attendance record and exactly one logbook entry per student per calendar day via database-level compound unique constraints `(user_id, date)` and application upserts. *Why:* Prevents duplicate submissions and guarantees chronological consistency.
- **Strict Same-Academic-Day Logbook Window** — Allow students to create and edit logbook drafts exclusively within the current academic day (closing at school-timezone midnight). Subsequent edits must be submitted as supervisor-annotated revisions. *Why:* Eliminates retrospective journal fabrication.
- **Formal Medical & Leave Request Pipeline** — Provide a digital absence workflow capturing absence reasons (`sick`, `permission`, `emergency`), textual justifications, and mandatory doctor's medical note uploads. *Why:* Replaces informal chat notes with an accountable, auditable record.
- **Audited Mentor Verification & Cross-Role Proxy** — Support dual-mentor verification (School Teacher + Industry Supervisor) with a 48-hour SLA review target and an audited cross-role proxy mechanism allowing teachers to sign off if an external supervisor is unreachable. *Why:* Prevents student evaluation paralysis caused by corporate mentor disengagement.
- **Comprehensive Logbook PDF Generation** — Compile all verified daily logbooks, learning outcomes, and photographic attachments into a formal, printable PDF dossier via Dompdf. *Why:* Satisfies physical accreditation archive and portfolio presentation requirements.

### Non-Goals

- **Real-Time Continuous Background GPS Tracking**. *Why:* Violates student privacy, rapidly drains mobile battery, and is legally prohibited under student data privacy guidelines. Location is captured only at discrete clock-in and clock-out moments.
- **Facial Recognition / Biometric Scanners on Mobile**. *Why:* Creates heavy biometric data storage liabilities and fails on budget mobile devices. Presence is verified via geolocation coordinates, IP logging, and work-activity photographic proof.
- **Ticketing System for Daily Operational Grievances**. *Why:* Urgent field disputes or workplace incidents are handled directly via phone/messaging apps, keeping journal records focused on educational reflection.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| UC-DAILY-001 | Student clocks in upon workplace arrival, recording geolocation, IP, and timestamp | P0 | F | Full |
| UC-DAILY-002 | Student clocks out upon departure; system derives exact working duration automatically | P0 | F | Full |
| UC-DAILY-003 | Student submits daily reflective logbook entry with skills practiced and photo proof | P0 | F | Full |
| UC-DAILY-004 | Student submits absence request with medical note attachment for missed day | P0 | F | Full |
| UC-DAILY-005 | Mentor reviews and verifies student logbook entry within 48-hour SLA window | P0 | F | Full |
| UC-DAILY-006 | Teacher verifies attendance via cross-role proxy when industry supervisor is absent | P1 | F | Full |
| UC-DAILY-007 | Coordinator compiles and downloads verified logbook PDF report for student portfolio | P1 | F | Full |

### 3.1 Student Daily Workflows

#### UC-DAILY-001 — Student Daily Clock-In
- **Preconditions**: Student has an active placement registration; has not yet clocked in today.
- **Flow**:
  1. Student accesses mobile dashboard on web browser and taps "Clock In".
  2. Browser prompts for GPS geolocation; student permits access.
  3. System receives latitude, longitude, and device timestamp.
  4. `ClockInAction` verifies student has active registration and verifies no existing record exists for `(user_id, date)`.
  5. System validates distance against company GPS coordinates; sets status to `present` (or `late` if past company start time).
  6. Dispatches `AttendanceClockIn` event and records audit log.
- **Postconditions**: Attendance record created; "Clock Out" button activated.

#### UC-DAILY-002 — Student Daily Clock-Out
- **Preconditions**: Student clocked in earlier today; has not yet clocked out.
- **Flow**:
  1. Student taps "Clock Out" at end of workday.
  2. Browser captures departure GPS coordinates and timestamp.
  3. `ClockOutAction` updates existing attendance row with `clock_out` time, IP, and coordinates.
  4. System computes exact duration worked (hours and minutes).
  5. Dispatches `AttendanceClockOut` event.
- **Postconditions**: Attendance row sealed for the day.

#### UC-DAILY-003 — Daily Reflective Logbook Submission
- **Preconditions**: Student attended placement today or within active grace period.
- **Flow**:
  1. Student navigates to `/journals/logbook`.
  2. Inputs structured tasks performed, vocational competencies practiced, and learning reflections.
  3. Uploads up to 3 photographic attachments verifying work activities.
  4. Saves as `draft` or clicks "Submit for Review".
  5. Status updates to `submitted`; assigned mentors receive in-app notification.
- **Postconditions**: Logbook entry queued for mentor review.

#### UC-DAILY-004 — Absence & Medical Leave Request
- **Preconditions**: Student is unable to attend placement on a given date.
- **Flow**:
  1. Student navigates to `/journals/absence`.
  2. Selects date, reason type (`sick`, `permission`, `emergency`), and writes description.
  3. If reason is `sick` or `emergency`, system mandates upload of doctor's note or parental letter.
  4. `SubmitAbsenceAction` creates attendance record with `status = absent` and `absence_status = pending`.
- **Postconditions**: Absence request staged for mentor approval.

### 3.2 Mentor Oversight & Verification

#### UC-DAILY-005 — Mentor Review of Daily Logbook
- **Preconditions**: Logbook status is `submitted`.
- **Flow**:
  1. Mentor opens teacher or supervisor dashboard and selects pending logbook entry.
  2. Inspects task narrative, competencies, and photographic evidence.
  3. Mentor selects either:
     - "Approve & Verify": Status transitions to `verified`, stamping `verified_by` and `verified_at`.
     - "Request Revision": Inputs specific feedback notes; status transitions to `revision_required`.
  4. If revision requested, student is notified and edit window re-opens for correction.
- **Postconditions**: Logbook reviewed and status persisted.

#### UC-DAILY-006 — Teacher Proxy Verification
- **Preconditions**: Industry supervisor is unreachable; student records require verification.
- **Flow**:
  1. Supervising Teacher opens student attendance or logbook list.
  2. Invokes `VerifyAttendanceAction` or `ReviewLogAction`.
  3. System detects actor is teacher acting for company supervisor; resolves proxy metadata via `MentorEntity`.
  4. Stores `is_verified = true`, `verified_by = teacher_id`, and `is_proxy = true` with audit reason.
- **Postconditions**: Record verified; proxy metadata permanently audited.

---

## 4. Functional Requirements

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| FR-JRN-001 | Single attendance record per student per calendar day enforced via unique `(user_id, date)` constraint | P0 | F | Full |
| FR-JRN-002 | Clock-in verification capturing latitude, longitude, IP, and timestamp, setting status `present` or `late` | P0 | F | Full |
| FR-JRN-003 | Clock-out calculation deriving duration automatically; rejects duplicate clock-outs | P0 | F | Full |
| FR-JRN-004 | Attendance status enumeration (`present`, `late`, `excused`, `absent`) with closed state machine | P0 | U | Full |
| FR-JRN-005 | Absence request workflow capturing reason, description, and mandatory proof attachment for illness | P0 | F | Full |
| FR-JRN-006 | Idempotent absence request processing (approve/reject) updating attendance status atomically | P0 | F | Full |
| FR-JRN-007 | Single logbook entry per student per day enforced via atomic upsert on `(user_id, date)` | P0 | F | Full |
| FR-JRN-008 | Logbook status lifecycle (`draft`, `submitted`, `verified`, `revision_required`) with transition guards | P0 | U | Full |
| FR-JRN-009 | Photo media pipeline supporting up to 3 image attachments per entry via Spatie MediaLibrary | P1 | F | Full |
| FR-JRN-010 | Same-academic-day edit window closing at midnight; retroactive alterations require mentor revision request | P0 | F | Full |
| FR-JRN-011 | Attendance immutability locking records after coordinator sign-off | P0 | F | Full |
| FR-JRN-012 | Dual mentor review authorization allowing school teacher or industry supervisor to evaluate entries | P0 | F | Full |
| FR-JRN-013 | Cross-role proxy logging teacher sign-offs on behalf of external supervisors | P1 | F | Full |
| FR-JRN-014 | Unaccounted date detection identifying missing days between placement start and current date | P1 | F | Full |
| FR-JRN-015 | Printable PDF logbook dossier generation compiling verified entries and photos via Dompdf | P1 | F | Full |

### 4.1 Attendance Tracking & Clock-In/Out

#### FR-JRN-001 & FR-JRN-002 — Clock-In Processing (`ClockInAction`)
- Validates active placement registration via `$user->getActiveRegistration()`. If absent, rejects with `RejectedException(__('journal.no_active_registration'))`.
- Verifies that no attendance row exists for `(user_id, date)`. If duplicate clock-in attempted, rejects with `RejectedException(__('journal.attendance.already_clocked_in'))`.
- Stores `clock_in` time, `clock_in_ip`, `clock_in_latitude`, and `clock_in_longitude`.
- Compares clock-in time against company start time threshold. If late, sets status to `late`; otherwise `present`.
- Dispatches `AttendanceClockIn` event and records audit log.

#### FR-JRN-003 — Clock-Out Processing (`ClockOutAction`)
- Finds attendance record for today. If absent, rejects with `RejectedException(__('journal.attendance.must_clock_in_first'))`.
- If `clock_out` is already populated, rejects with `RejectedException(__('journal.attendance.already_clocked_out'))`.
- Stores `clock_out` time, `clock_out_ip`, `clock_out_latitude`, and `clock_out_longitude`.
- Derives total duration worked automatically via Carbon difference.
- Dispatches `AttendanceClockOut` event.

### 4.2 Absence Request & Medical Leave

#### FR-JRN-005 — Absence Request Submission (`SubmitAbsenceAction`)
- Ensures the requested date is not already recorded as attended or absent.
- Stores absence record in `attendances` table with `status = absent`, `absence_status = pending`, and `absence_type` (`sick`, `permission`, `emergency`).
- If `absence_type === 'sick'`, mandates an uploaded attachment (JPG, PNG, PDF max 2MB).
- Dispatches notification to assigned mentors.

#### FR-JRN-006 — Idempotent Absence Processing (`ProcessAbsenceAction`)
- Evaluated by assigned mentor teacher or school administrator.
- If approved: `absence_status` updates to `approved` and attendance `status` transitions to `excused`.
- If rejected: `absence_status` updates to `rejected` and attendance `status` remains unexcused `absent`.
- Transition is idempotent: only requests in `pending` state can be processed.

### 4.3 Daily Reflective Logbook & Media Proof

#### FR-JRN-007 — Single Logbook Invariant (`CreateLogbookAction`, `UpdateLogbookAction`)
- Each student is entitled to exactly one logbook entry per calendar day during their placement date range.
- Enforced at database level via unique index `logbooks_user_id_date_unique`.
- Enforces same-day edit rule: owning student can update content freely while `date === today()` and status is `draft` or `revision_required`.
- At midnight of the school's configured timezone, editing locks unless unlocked by mentor revision request.

#### FR-JRN-009 — Media Attachment Pipeline
- Attachments are stored in the `photos` Spatie MediaLibrary collection.
- Enforces strict MIME allowlist: `image/jpeg`, `image/png`, `image/webp`.
- Enforces maximum file size of 3MB per photo, automatically generating responsive web thumbnails for fast dashboard display.
- Maximum 3 photos per logbook entry.

### 4.4 Verification, Proxy Audit & PDF Compilation

#### FR-JRN-012 & FR-JRN-013 — Verification & Proxy Trail (`VerifyAttendanceAction`)
- When mentor verifies an entry:
  - Updates `is_verified = true`, `verified_by = actor_id`, and `verified_at = now()`.
  - If a school teacher verifies for an absent company mentor, resolves proxy metadata through `MentorEntity::resolveProxyMetadata()`.
  - Audit trail logs `is_proxy = true` and `proxy_role = 'teacher'`.

#### FR-JRN-015 — Logbook PDF Compilation (`CompileLogbookReportAction`)
- Renders printable Blade view `journal::pdf.logbook-report` containing:
  - School letterhead, student NISN, and company details.
  - Chronological table of verified attendance and logbook activities.
  - Embedded thumbnails of photographic proof.
  - Dual signature blocks for School Supervising Teacher and Industry Mentor.
- Compiled server-side via Dompdf with remote asset fetching disabled for security.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|---|---|---|---|---|---|
| NFR-JRN-001 | Geolocation calculation latency | < 50ms per check-in calculation | P0 | U | Full |
| NFR-JRN-002 | Clock-in concurrency safety under peak morning surge | Zero duplicate rows under 500 concurrent clock-ins | P0 | F | Full |
| NFR-JRN-003 | Mobile web responsive rendering | 100% usable at 360px width on low-end Android | P0 | B | Full |
| NFR-JRN-004 | Media compression & thumbnail conversion | Thumbnail generated in < 1.5s on upload | P1 | F | Full |
| NFR-JRN-005 | PII & Geolocation data privacy protection | Latitude/Longitude masked in system activity logs | P0 | A | Full |
| NFR-JRN-006 | Signed-off attendance immutability guarantee | 0 post-sign-off mutations accepted | P0 | F | Full |

### 5.1 Concurrency & Integrity
- **NFR-JRN-002 (Peak Morning Clock-In Surge)**: 500+ students clocking in between 07:30 AM and 08:00 AM on shared hosting or modest VPS. Handled through lightweight database transactions, eager-loaded relationship queries, and SQLite WAL mode with 5000ms busy timeout.

### 5.2 Geofencing & Security
- **NFR-JRN-001 (Distance Calculation)**: Distance between device coordinates `($lat1, $lon1)` and company coordinates `($lat2, $lon2)` is calculated using the Haversine formula on the server side, preventing client-side spoofing.
- **NFR-JRN-005 (Location Privacy)**: GPS coordinates are stored in the operational `attendances` table for verification, but are masked by `SmartLogger` in system logs to protect student physical privacy.

---

## 6. API / Data Contracts

### 6.1 State Enums

```php
namespace App\Modules\Journal\Domain\Attendance\Enums;

enum AttendanceStatus: string implements StatusEnum
{
    case PRESENT = 'present';
    case LATE    = 'late';
    case EXCUSED = 'excused';
    case ABSENT  = 'absent';
}

namespace App\Modules\Journal\Domain\Logbook\Enums;

enum LogbookStatus: string implements StatusEnum
{
    case DRAFT             = 'draft';
    case SUBMITTED         = 'submitted';
    case VERIFIED          = 'verified';
    case REVISION_REQUIRED = 'revision_required';
}

namespace App\Modules\Journal\Domain\AbsenceRequest\Enums;

enum AbsenceReasonType: string implements LabelEnum
{
    case SICK       = 'sick';
    case PERMISSION = 'permission';
    case EMERGENCY  = 'emergency';
}

enum AbsenceRequestStatus: string implements StatusEnum
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
```

### 6.2 Data Transfer Objects (DTO)

```php
// 1. Clock-In DTO
namespace App\Modules\Journal\Domain\Attendance\Data;

final readonly class ClockInData
{
    public function __construct(
        public string $userId,
        public array $data,          // ['latitude' => float, 'longitude' => float]
        public ?string $requestIp = null,
    ) {}
}

// 2. Clock-Out DTO
namespace App\Modules\Journal\Domain\Attendance\Data;

final readonly class ClockOutData
{
    public function __construct(
        public string $userId,
        public array $data,          // ['latitude' => float, 'longitude' => float]
        public ?string $requestIp = null,
    ) {}
}

// 3. Submit Absence DTO
namespace App\Modules\Journal\Domain\AbsenceRequest\Data;

final readonly class SubmitAbsenceData
{
    public function __construct(
        public string $userId,
        public string $registrationId,
        public array $data,          // ['start_date' => string, 'reason_type' => string, 'reason_description' => ..., 'attachment_path' => ...]
    ) {}
}
```

### 6.3 Command Action Signatures

```php
namespace App\Modules\Journal\Domain\Attendance\Actions;

final class ClockInAction extends BaseCommandAction
{
    public function execute(ClockInData $data): Attendance;
}

final class ClockOutAction extends BaseCommandAction
{
    public function execute(ClockOutData $data): Attendance;
}

final class VerifyAttendanceAction extends BaseCommandAction
{
    public function execute(Attendance $log, ?User $actor = null): Attendance;
}

namespace App\Modules\Journal\Domain\Logbook\Actions;

final class CreateLogbookAction extends BaseCommandAction
{
    public function execute(string $userId, array $data): Logbook;
}

final class SubmitLogbookAction extends BaseCommandAction
{
    public function execute(Logbook $logbook): Logbook;
}

final class CompileLogbookReportAction extends BaseReadAction
{
    public function execute(string $registrationId): \Barryvdh\DomPDF\PDF;
}
```

### 6.4 Database Table Schemas

#### `attendances` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | uuid | Primary Key | UUID v7 identifier |
| `user_id` | uuid | Foreign Key -> `users.id` | Student user |
| `registration_id` | uuid | Foreign Key -> `registrations.id` | Active placement |
| `date` | date | Indexed | Calendar date of attendance |
| `clock_in` | time | Nullable | Arrival time |
| `clock_out` | time | Nullable | Departure time |
| `clock_in_ip` | string | Nullable | Client IP at arrival |
| `clock_out_ip` | string | Nullable | Client IP at departure |
| `clock_in_latitude` | decimal(10,8) | Nullable | Geolocation latitude |
| `clock_in_longitude` | decimal(11,8) | Nullable | Geolocation longitude |
| `status` | string | Indexed | `present`, `late`, `excused`, `absent` |
| `is_verified` | boolean | Default `false` | Mentor verification flag |
| `verified_by` | uuid | Nullable -> `users.id` | Verifying mentor |
| `verified_at` | timestamp | Nullable | Verification timestamp |
| `absence_type` | string | Nullable | `sick`, `permission`, `emergency` |
| `absence_reason` | text | Nullable | Student explanation |
| `absence_attachment`| string | Nullable | Stored medical slip path |
| `absence_status` | string | Default `pending` | `pending`, `approved`, `rejected` |

*Unique Index:* `attendances_user_id_date_unique (user_id, date)`.

#### `logbooks` Table
| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | uuid | Primary Key | UUID v7 identifier |
| `user_id` | uuid | Foreign Key -> `users.id` | Student user |
| `registration_id` | uuid | Foreign Key -> `registrations.id` | Active placement |
| `date` | date | Indexed | Calendar date of activity |
| `content` | text | Not Null | Task description and reflection |
| `learning_outcomes`| text | Nullable | Competencies practiced |
| `status` | string | Default `draft` | `draft`, `submitted`, `verified`, `revision_required` |
| `is_verified` | boolean | Default `false` | Verification state |
| `verified_by` | uuid | Nullable -> `users.id` | Verifying mentor |
| `verified_at` | timestamp | Nullable | Timestamp of verification |
| `review_notes` | text | Nullable | Mentor constructive feedback |

*Unique Index:* `logbooks_user_id_date_unique (user_id, date)`.

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| DD-JRN-001 | Unified Attendance & Absence Storage in a Single Table | P1 | A | Full |
| DD-JRN-002 | Compound Unique Constraint `(user_id, date)` on Journal Records | P0 | F | Full |
| DD-JRN-003 | Same-Academic-Day Logbook Window with Midnight Closure | P0 | F | Full |
| DD-JRN-004 | Server-Side Haversine Distance Validation over Client Geofence | P0 | U | Full |
| DD-JRN-005 | Audited Mentor Proxy Verification via MentorEntity Bridge | P1 | F | Full |

### 7.1 Key Architecture Decisions

#### DD-JRN-001 — Unified Attendance & Absence Storage
- *Decision*: Both physical attendance logs and absence requests share the single `attendances` table.
- *Rationale*: A calendar day for a student has exactly one outcome: the student was either present, late, excused, or absent. Storing them in separate tables would require complex outer joins and risk contradictory states (e.g. clocked-in yet simultaneously excused).

#### DD-JRN-002 — One-Record-Per-Day Dual Invariant
- *Decision*: Enforced by both application upsert logic and a database-level unique constraint `(user_id, date)`.
- *Rationale*: Defense-in-depth: prevents race conditions when a student taps the submit button multiple times on poor mobile cellular connections.

#### DD-JRN-003 — Same-Day Logbook Window & Midnight Closure
- *Decision*: Students can only write or edit logbooks for the current calendar day until 23:59:59.
- *Rationale*: Eliminates the historical SMK habit of "retroactive cramming" where students fabricate a semester's worth of logbook entries in one night. If an entry is missed, it must be approved as a revision by the supervisor.

#### DD-JRN-004 — Server-Side Haversine Geofence Calculation
- *Decision*: The browser submits raw latitude and longitude coordinates; the server computes distance to company coordinates using the Haversine spherical formula.
- *Rationale*: Prevents client-side script tampering or manipulated geofence booleans.

---

## 8. Success Metrics

- **Zero Fabricated Batches**: 100% of logbook entries have creation timestamps corresponding to the day of work.
- **SLA Review Compliance**: > 85% of submitted logbooks reviewed by teachers or supervisors within 48 hours.
- **Attendance Accountability**: 0 unrecorded or disputed absences during end-of-cohort grade calculation.
- **Accreditation Readiness**: Median time to compile a student's full 90-day verified logbook PDF portfolio is under 15 seconds.

---

## 9. Roadmap

- **Phase 8 (Current Baseline)**: GPS attendance clock-in/out, absence workflow with attachments, reflective logbooks with photo proof, dual mentor review, and PDF report compilation.
- **Phase 9 (Next)**: Offline browser ServiceWorker caching allowing students to draft entries without active internet and auto-sync when cellular reception returns.

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|---|---|---|---|---|
| R-1 | If a company partner is located in a high-rise office or basement with weak GPS reception. Mitigated by configurable geofence radius (e.g., 200m) and mentor override. | Resolved | Product Lead | [#485](https://github.com/reasvyn/internara/issues/485) |
| R-2 | If a student's phone battery dies before clock-out. Mitigated by mentor manual clock-out adjustment action with audit trail. | Resolved | Backend Lead | [#485](https://github.com/reasvyn/internara/issues/485) |
| A-1 | We assume each student is placed at exactly one active company location per registration period. | Accepted | Architect | — |
| A-2 | We assume standard working days follow the academic program calendar (Monday–Friday or Monday–Saturday per company schedule). | Accepted | Product Lead | — |

---

## Quick References

- [`docs/index.md`](../index.md) — Documentation index
- [`docs/prd.md`](../prd.md) — Product Requirement Document (PRD) — Pillar 1
- [`docs/templates/spec-template.md`](../templates/spec-template.md) — Spec template contract
- [`docs/refs/modules/journal.md`](../refs/modules/journal.md) — Journal module conceptual reference
- [`docs/refs/modules/journal-reference.md`](../refs/modules/journal-reference.md) — Journal module technical API reference
- [GitHub Issue #485](https://github.com/reasvyn/internara/issues/485) — Daily Activity Spec `[1KSWL]`
- [GitHub Issue #486](https://github.com/reasvyn/internara/issues/486) — Supervision Spec `[2EHSE]`
