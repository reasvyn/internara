# Daily Activity — Logbook, Attendance & Absence Requests

> **Spec ID:** 1KSWL
> **Status:** Full
> **Owner:** Journals
> **Depends on:** J9GBH

## Description

The student-facing daily record of the internship: a reflective logbook entry every working day,
a clock-in/clock-out attendance trail with actor identity, and a formal absence request flow for
days the student cannot attend. Together they form the canonical evidence that the placement
happened — the artifact accreditation auditors and industry sign-off both read first.

Mentor-side oversight of this record (monitoring visits, supervision logs, proxy verification,
compliance checks) lives in [supervision](2EHSE-supervision.md). Placement scope comes from
[placement](J9GBH-placement.md).

---

## 1. Problem Statements

### PS-1 — Fabricated Logbooks With No Time Boundary

A school in Sintuk Toboh Gadang once received a week's worth of logbook entries the night before
a supervision visit — every row timestamped 08:00 sharp, written from memory days later. Paper
books and chat-reported journals cannot prove *when* an entry was written, so back-filled fiction
passes as daily reflection.
**→ Requirement:** FR-DAILY-001 (one entry per day), FR-DAILY-005 (same-day edit window).

### PS-2 — Attendance Without an Author

WhatsApp-reported attendance ("hadir pak 🙏") carries no timestamp the school controls and no
proof of who pressed send. Disputes over presence collapse into competing chat screenshots.
**→ Requirement:** FR-DAILY-007/008 (timestamped clock-in/out with actor), FR-DAILY-011
(sign-off immutability).

### PS-3 — Absences That Vanish From the Record

A sick student tells the site supervisor verbally, the message never reaches the school, and the
blank day later reads as truancy during grade aggregation. Informal absence notice is absence
without evidence.
**→ Requirement:** FR-DAILY-012 (formal request), FR-DAILY-013 (idempotent processing).

---

## 2. Goals & Non-Goals

### Goals

- **One truthful logbook entry per student per day** — draft, submit, verify, revise, never silently rewrite. *Why:* the logbook is accreditation evidence; its timestamps must survive hostile reading.
- **Attendance that names its author and moment** — clock-in/out timestamps plus actor identity on every row. *Why:* ends chat-screenshot disputes over who was present when.
- **Absences as records, not rumors** — every missed day is a request with a reason, evidence where it matters, and a named processor. *Why:* protects sick students from truancy marks and the school from invented excuses.
- **Mentor verification that survives supervisor absence** — a teacher can verify in the supervisor's stead with a full audit trail. *Why:* industry supervisors go dark for days; student workflows cannot wait.

### Non-Goals

- **GPS geofencing enforcement**. *Why:* coordinates are stored as optional metadata, never as an entry gate — rural sites and device variance make enforcement a false-rejection machine.
- **Real-time location tracking during the workday**. *Why:* two timestamps per day prove presence; continuous tracking is surveillance the curriculum never asked for.
- **Multi-format export matrices**. *Why:* one PDF logbook report satisfies accreditation; CSV/XLSX variants are post-MVP depth.
- **Automated absence escalation to school administration**. *Why:* mentor notification covers MVP; escalation policy engines belong to a later phase.

---

## 3. User Stories / Use Cases

A student's day passes through three small rituals — write the logbook, clock the hours, explain
any absence — and a mentor closes the loop by verifying or processing each one.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DAILY-001 | Student writes the day's logbook entry, submitting it for mentor review | P0 | F | Full |
| UC-DAILY-002 | Mentor reviews and verifies a submitted logbook entry, by proxy when the supervisor is unreachable | P0 | F | Full |
| UC-DAILY-003 | Student clocks in at arrival and out at departure, with duration derived by the system | P0 | F | Full |
| UC-DAILY-004 | Student files an absence request with reason and evidence for a missed day | P0 | F | Full |
| UC-DAILY-005 | Mentor processes a pending absence request, approving or rejecting it exactly once | P0 | F | Full |

### 3.1 Student Daily Record

#### UC-DAILY-001 — Student Writes the Day's Logbook

Evening at the partner workshop, the student opens the logbook page and finds today's form
waiting — yesterday's entries read-only beneath it. Activities, learnings, challenges, and
tomorrow's plan go into the form, a photo of the morning's wiring job attaches from the phone,
and one submit call persists the entry as submitted. When the same student opens the page again
after midnight, today is a fresh blank form; last night's words are already history.

#### UC-DAILY-002 — Mentor Verifies a Submitted Entry

The verification path bends around whoever is actually available. On an ordinary week the
industry supervisor opens the entry, reads the content and photos, leaves feedback, and marks it
verified. On the week the supervisor's phone stays off, the assigned teacher opens the same
entry and verifies in the supervisor's stead — the record then carries both names, the actor
and the proxied role, so nobody later mistakes cover for authorship.

#### UC-DAILY-003 — Student Clocks In and Out

Arrival means one tap: the page shows "not clocked in", the student taps, and the record opens
with the server's timestamp, the student's identity, and whatever coordinates the device
offered. Departure means a second tap that closes the record and lets the system derive the
duration — the student never types a time or a duration, so neither can be negotiated upward.
A student who taps twice in the morning gets the first tap kept and the second refused.

### 3.2 Absence Handling

#### UC-DAILY-004 — Student Files an Absence Request

Fever at dawn means the student reaches for the absence form instead of the attendance page,
picks the date and the sick reason, describes the symptoms, and attaches the clinic note the
form demands for sickness. The request lands as pending, visible to the mentor within the hour,
and the day is accounted for — explained rather than blank. A permission request for a family
matter travels the same path minus the mandatory attachment.

#### UC-DAILY-005 — Mentor Processes the Request

Two mentors opening the same pending request at once must not produce two decisions. The first
tap — approve with a note, or reject with a reason — transitions the request out of pending;
the second tap meets a record that is no longer pending and is refused with a clear message.
The student sees exactly one outcome, and the attendance picture for that date resolves to a
single documented state instead of a contested one.

---

## 4. Functional Requirements

Command Actions own every write here; Read Actions serve the lists. Entities hold the rules,
Models hold the rows, and no Livewire component touches a Model directly.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DAILY-001 | One logbook entry per student per calendar day, enforced by atomic upsert on `(user_id, date)` | P0 | F | Full |
| FR-DAILY-002 | Logbook model contract: UUIDv7 PK, `#[Fillable]` whitelist, typed casts, registration/user relations, Entity bridge | P0 | A | Full |
| FR-DAILY-003 | LogbookStatus enum (DRAFT, SUBMITTED, VERIFIED, REVISION_REQUIRED) with a closed transition map | P0 | U | Full |
| FR-DAILY-004 | Photo attachments on logbook entries via the MediaLibrary `photos` collection with validated MIME/size | P1 | F | Full |
| FR-DAILY-005 | Same-academic-day edit window: only the owning student edits within the day; later correction is a supervisor-annotated revision, never a silent rewrite | P0 | F | Full |
| FR-DAILY-006 | Logbook verification records verifier identity and timestamp; teacher verification for an absent supervisor delegates through MentorEntity with proxy audit | P0 | F | Full |
| FR-DAILY-007 | Clock-in rejects without an active registration or on a duplicate day; creates a PRESENT record with timestamp, actor, IP, and optional GPS metadata | P0 | F | Full |
| FR-DAILY-008 | Clock-out rejects without an open clock-in or on double close; duration is derived by the system, never submitted | P0 | F | Full |
| FR-DAILY-009 | AttendanceStatus, AbsenceReasonType, and AbsenceRequestStatus enums with fixed case lists | P0 | U | Full |
| FR-DAILY-010 | One attendance row per student per day via unique `(user_id, date)` with atomic insert | P0 | F | Full |
| FR-DAILY-011 | Attendance immutability after admin sign-off with pre-sign-off edits audit-logged; VerifyAttendanceAction is the sole sign-off point | P0 | F | Full |
| FR-DAILY-012 | Absence requests start PENDING with reason, description, and mandatory attachment for SICK and EMERGENCY | P0 | F | Full |
| FR-DAILY-013 | Absence processing is idempotent: only PENDING requests transition, recording processor, timestamp, and notes atomically | P0 | F | Full |
| FR-DAILY-014 | Mentor attendance reads are scoped to supervised registrations; no unscoped cross-registration reads | P0 | F | Full |
| FR-DAILY-015 | Every mutation validates server-side through DTOs/Form Requests; raw request payloads never reach create/update | P0 | A | Full |
| FR-DAILY-016 | Dual-layer authorization: Policy gate plus Action/Entity rejection via RejectedException; proxy path resolves through MentorEntity | P0 | A | Full |
| FR-DAILY-017 | All user-facing strings use `__()` with mirrored `lang/en` and `lang/id` keys | P0 | A | Full |
| FR-DAILY-018 | Command Actions wrap writes in transactions with SmartLogger audit (PII masked); business violations throw RejectedException with translatable messages | P0 | F | Full |
| FR-DAILY-019 | Logbook PDF report compiles verified entries with media for a registration via DomPDF | P1 | F | Full |

### 4.1 Logbook

#### FR-DAILY-001 — One Entry Per Student Per Day

Open the submit handler and the first thing it does is refuse to ask whether an entry exists —
it upserts keyed on the student and the date, so two taps racing from a flaky connection
collapse into one row instead of twins. The database unique constraint stands behind the
upsert as a second wall. A student with an entry for Monday who submits again on Monday edits
Monday; Tuesday is untouched until Tuesday.

#### FR-DAILY-002 — Logbook Model Contract

The model file reads like a manifest: UUIDv7 primary key, an explicit fillable whitelist, date
and status casts, belongs-to links for the student, the registration, the verifier, and the
supervisor, plus the bridge method that freezes a row into its Entity. Nothing about approval
or windows lives here — persistence answers "what is stored", and the Entity answers "what it
means".

#### FR-DAILY-003 — Logbook Status Transitions

Four states, five legal moves: drafts submit, submitted entries verify or return for revision,
revisions reopen as drafts, and verified is the end of the line. The map lives on the enum
itself, so an Action that attempts verified-to-draft does not get a quiet no-op — it gets a
rejection naming the transition as illegal. Any future state would extend this map, never a
second ad-hoc check elsewhere.

#### FR-DAILY-004 — Logbook Photo Attachments

A photo of the morning's wiring job rides into the entry through the `photos` media
collection, and the gate at the door checks format and size before anything is stored. The
collection accepts common phone formats including HEIC, because students upload from whatever
device they own. Entries without photos remain perfectly valid — evidence enriches the record
but never blocks the day's reflection.

#### FR-DAILY-005 — Same-Day Edit Window

The window exists because of the back-filled week from Sintuk Toboh Gadang: entries timestamped
08:00 sharp, written days late from fading memory. Midnight in the school's timezone closes the
day — Asia/Jakarta by default, so a night-shift student at a partner workshop stays inside the
same academic day until local midnight, not UTC midnight. Ownership is re-resolved inside the
Action from the registration bridge, so a crafted direct call from a non-owner dies with a
rejection even where the policy was bypassed. After the window, correction arrives as a
supervisor-annotated revision entry sitting beside the original, and the audit trail keeps both
— what was claimed, and what replaced it.

#### FR-DAILY-006 — Verification With Proxy Delegation

A verified entry names its verifier and the moment of verification, always. The ordinary case
is the assigned supervisor verifying their own mentee. The extraordinary-but-routine case is
the teacher verifying because the supervisor has been silent for days: the policy asks the
registration's MentorEntity whether this teacher may stand in, and the activity entry records
`proxy_role` alongside the actor so the audit reads "teacher X, acting as supervisor" rather
than a mysterious supervisor signature.

### 4.2 Attendance & Sign-Off

#### FR-DAILY-007 — Clock-In Guards and Record Shape

Morning tap, server timestamp, student identity, request IP, and whatever coordinates the
device volunteered — that is the whole record, and the student supplies none of it by hand.
Without an active registration the tap is refused, because hours outside a placement are not
PKL hours. A second tap on the same day is refused as well, leaving the first record exactly
as the morning wrote it.

#### FR-DAILY-008 — Clock-Out and Derived Duration

The evening tap only works against an open morning record: no clock-in, no clock-out, and a
record already closed stays closed. Duration emerges from subtracting the two server
timestamps, which removes an entire class of fraud — nobody can type "8 hours" after a
three-hour day. GPS and IP from the evening tap are stored beside the morning's, giving each
day two independent position samples without ever gating entry on them.

#### FR-DAILY-009 — Attendance and Absence Enums

Present, late, early-out, absent, permission, sick — one vocabulary for the day's outcome, so
reports never reconcile two competing status columns. Absence reasons stay to four: sick,
permission, emergency, other. Request states stay to three: pending, approved, rejected. Each
enum carries its own human label in both languages, and the database stores the stable backing
value rather than display text.

#### FR-DAILY-010 — One Attendance Row Per Day

The unique constraint on student-plus-date is the final arbiter that no application check can
soften. Concurrent taps from a double-submitted form race toward the same key, one wins, the
other is rejected — never merged, never duplicated. Reporting code can therefore assume one
row per student per day as a structural fact instead of defending against twins at every query
site.

#### FR-DAILY-011 — Sign-Off Immutability and Its Enforcement Points

An admin's sign-off is the moment an attendance row turns from working paper into record.
VerifyAttendanceAction is the only door: it stamps verifier identity and timestamp, flips the
verified flag, and writes the audit entry in the same transaction. After that stamp, every
mutation path — update, delete, verify-again — is rejected, and the rejection names the
sign-off as the reason. Before the stamp, edits are allowed but never silent: each one appends
an audit entry with actor and before/after diff. The older grace-period idea (auto-lock after
24 hours) is superseded — a fixed clock would freeze rows nobody reviewed while leaving
reviewed rows conceptually open, exactly backwards.

### 4.3 Absence Requests

#### FR-DAILY-012 — Filing an Absence Request

Sickness and emergency demand proof, so the form refuses those reasons without an attachment —
a clinic note, a family letter, something a mentor can weigh. Permission and other reasons
travel lighter, with description alone. Every request is born pending; nothing about filing
implies approval, and the student's day shows "requested" rather than "absent" until a mentor
decides.

#### FR-DAILY-013 — Idempotent Absence Processing

Approving an absence twice must be impossible, not merely unlikely. The processor re-reads the
request inside the transaction, and anything not pending is refused outright. Approval or
rejection then stamps processor identity, timestamp, and notes in the same atomic write that
moves the status — the four fields land together or not at all, so a crash can never leave a
request approved with nobody named.

#### FR-DAILY-014 — Mentor-Scoped Attendance Reads

A mentor opening the attendance view for a date sees their own supervised students and nobody
else's. The query starts from the mentor's supervised registrations and fans out, rather than
pulling the day's rows for the whole school and filtering in memory. This scoping is a privacy
property as much as a performance one: cross-cohort browsing is structurally unavailable, not
merely discouraged.

### 4.4 Cross-Cutting Integrity

#### FR-DAILY-015 — Server-Side Validation Boundary

Every write crosses a validation boundary before touching persistence: HTTP payloads through
Form Requests, Action inputs through validated DTOs. The forbidden shape is the bulk pass of
raw request data into create or update — one unguarded field there, and a student could set
their own verified flag. Invalid payloads die before the transaction opens, so failed
validation never leaves partial rows or audit noise.

#### FR-DAILY-016 — Dual-Layer Authorization

Two gates stand in series. The policy answers "may this actor touch this record" at the door,
and the Action or Entity re-asks the business question inside — direct Action calls therefore
gain no privilege over the UI path. Teacher-as-supervisor coverage resolves through the
registration's MentorEntity rather than a role shortcut, so proxy authority is always bounded
by actual mentorship. A violation at either layer surfaces as a rejection with a message the
student or mentor can read, never a stack trace.

#### FR-DAILY-017 — Localized Strings

Indonesian first, English second, both shipped: every string a student or mentor reads passes
through the translation helper, and every key exists in both locale files. Dynamic values —
names, dates, company titles — travel as placeholders inside the translated sentence rather
than concatenation around it, because word order differs between the two languages and
concatenated bilingual sentences read as broken in at least one of them.

#### FR-DAILY-018 — Transactional Writes With Masked Audit

Each Command opens a transaction, persists, writes its SmartLogger audit entry, and only then
commits — a failed write leaves neither a half-row nor a phantom audit trail. Payloads pass
through PII masking before reaching any sink, so a password or token accidentally included in
context arrives masked. Business failures speak through RejectedException carrying a
translatable sentence; unexpected failures are logged with full context while the user sees a
generic, calm failure notice.

#### FR-DAILY-019 — Logbook PDF Report

Accreditation still runs on paper folders, so a registration's verified entries compile into a
single PDF with embedded photos, ordered by date. Drafts and unreviewed entries are excluded —
the report is a certificate of what mentors actually endorsed, not a dump of everything
typed. Generation runs as a Read, lock-free, because a coordinator exporting during the
morning attendance rush must never contend with student writes.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DAILY-001 | Signed-off attendance rows reject all mutation attempts, including concurrent ones | 0 post-sign-off writes accepted | P0 | F | Full |
| NFR-DAILY-002 | Logbook edit window closes at the school-timezone midnight with no silent next-day edits | 0 silent next-day edits | P0 | F | Full |
| NFR-DAILY-003 | Concurrent clock-in attempts for one student-day yield exactly one row | 0 duplicate day rows | P0 | F | Full |
| NFR-DAILY-004 | Absence requests transition out of PENDING exactly once under concurrent processing | 0 double transitions | P0 | F | Full |
| NFR-DAILY-005 | No hardcoded user-facing strings in daily-activity views, components, or notifications | 0 hardcoded strings (D3 scan clean) | P0 | A | Full |
| NFR-DAILY-006 | Every daily-activity mutation writes a PII-masked activity entry | 100% logged, 0 PII leaks | P0 | F | Full |

### 5.1 Integrity Under Concurrency

#### NFR-DAILY-001 — Sign-Off Holds Under Pressure

Two admins opening the same unsigned row, one signing while the other edits, is the scenario
this constraint exists for. The sign-off transaction serializes the outcome: the loser's write
meets the fresh stamp and is refused. Test doubles of this race — parallel update attempts
against a just-signed row — must show zero accepted writes, because a single post-sign-off
mutation silently voids the accreditation value of the whole table.

#### NFR-DAILY-002 — Midnight Means Midnight

The window boundary is only as honest as its clock. Using the school's configured timezone
keeps the boundary aligned with the students' lived day; a UTC boundary would steal or grant
hours around midnight for night-shift placements. The measurable claim is behavioral: after
the boundary, no edit path — UI, Action, or crafted call — modifies the original entry
without leaving a revision artifact beside it.

#### NFR-DAILY-003 — Single Row From a Double Tap

Flaky connections retry, impatient students double-tap, and both taps arrive within
milliseconds. The unique key plus atomic insert turns the race into a deterministic outcome:
first writer wins, second is rejected cleanly. Load-testing this needs no dedicated rig — a
pair of concurrent requests in a feature test proves the property, and the constraint holds it
forever after.

#### NFR-DAILY-004 — Exactly-Once Absence Decisions

The cost of a double transition is a student approved and rejected simultaneously, with two
mentors each believing their decision stands. Re-reading state inside the transaction closes
the window between check and write. Concurrent processing attempts in tests must converge on
one decision and one refusal, every time.

### 5.2 Localization and Audit

#### NFR-DAILY-005 — Zero Hardcoded Strings

A hardcoded "Clock In" in a Blade file is a string Indonesian students cannot read and
translators cannot find. The D3 scan walks every view, component, and notification in this
domain and reports any user-facing literal outside the translation helper. Clean scan output
is the target; there is no acceptable quota of stragglers.

#### NFR-DAILY-006 — Complete Masked Audit

Every mutation in this domain — logbook submit, clock-in, clock-out, verify, absence file,
absence process — leaves exactly one activity entry naming actor and change summary. Masking
runs before the write, so even a developer who logs an entire payload cannot leak an email or
token into the audit table. Reviewing this means sampling mutations and matching entries
one-to-one, then grepping the sinks for unmasked personal data and finding none.

---

## 6. API / Data Contracts

### 6.1 Logbook Model

```
App\Modules\Journals\Domain\Logbook\Models\Logbook
  Table: logbooks (UUIDv7 PK)
  Implements: HasMedia
  Fillable: user_id, registration_id, date, content, learning_outcomes, status,
            is_verified, verified_by, verified_at, mentor_feedback,
            supervisor_note, supervisor_reviewed_at, supervisor_id
  Casts: date → date, status → LogbookStatus, is_verified → boolean
  Relations: user() BelongsTo User, registration() BelongsTo Registration,
             verifier() BelongsTo User, supervisor() BelongsTo User
  Media: photos (jpeg, png, webp, heic, heif)
  Unique: (user_id, date), (registration_id, date)
  Bridge: asLogbookState() → LogbookState
```

### 6.2 Attendance and Absence Models

```
App\Modules\Journals\Domain\Attendance\Models\Attendance
  Table: attendances (UUIDv7 PK)
  Fillable: user_id, registration_id, date, clock_in, clock_out, clock_in_ip,
            clock_out_ip, clock_in_latitude, clock_in_longitude,
            clock_out_latitude, clock_out_longitude, status, notes,
            is_verified, verified_by, verified_at
  Casts: date → date, status → AttendanceStatus, is_verified → boolean
  Relations: user() BelongsTo User, registration() BelongsTo Registration,
             verifier() BelongsTo User
  Unique: (user_id, date)
  Bridge: asAttendanceState() → AttendanceState

App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest
  Table: attendances (shared; global scope whereNotNull('absence_type'))
  Fillable: user_id, registration_id, date, absence_type, absence_reason,
            absence_attachment, absence_status, absence_processed_by,
            absence_processed_at, absence_admin_notes
  Casts: date → date, absence_type → AbsenceReasonType,
         absence_status → AbsenceRequestStatus
  Relations: user() BelongsTo User, processor() BelongsTo User,
             registration() BelongsTo Registration
```

### 6.3 Enums

| Enum | Cases |
| ---- | ----- |
| `LogbookStatus` | DRAFT, SUBMITTED, VERIFIED, REVISION_REQUIRED |
| `AttendanceStatus` | PRESENT, LATE, EARLY_OUT, ABSENT, PERMISSION, SICK |
| `AbsenceReasonType` | SICK, PERMISSION, EMERGENCY, OTHER |
| `AbsenceRequestStatus` | PENDING, APPROVED, REJECTED |

### 6.4 Actions

Logbook: `CreateLogbookAction`, `UpdateLogbookAction`, `DeleteLogbookAction`,
`SubmitLogbookAction` (Command); `CompileLogbookReportAction` (Read).
Attendance: `ClockInAction`, `ClockOutAction`, `CreateAttendanceAction`,
`UpdateAttendanceAction`, `DeleteAttendanceAction`, `VerifyAttendanceAction` (Command).
AbsenceRequest: `SubmitAbsenceAction`, `ProcessAbsenceAction` (Command).

### 6.5 Events, Policies, Routes

Events: `AttendanceClockIn` (from `ClockInAction`), `AttendanceClockOut` (from
`ClockOutAction`).

Policies: `LogbookPolicy` (create: student; update: admin or owner while editable;
verify: supervisor or MentorEntity proxy); `AttendancePolicy` (create: student; verify:
admin or mentor proxy; update/delete: admin); `AbsenceRequestPolicy` (submit: student;
process: mentor proxy chain).

Routes: `GET /student/logbook` (`student.logbook`), `GET /student/attendance`
(`student.attendance`), `GET /student/attendance/absence`
(`student.attendance.absence`), `GET /admin/attendance` (`sysadmin.attendance`),
`GET /admin/logbook` (`sysadmin.logbook`), `GET /admin/logbook/report/{registration}`
(`sysadmin.logbook.report`) — all behind `auth` plus role middleware.

### 6.6 Database Schema

```
attendances:
  id: uuid PK; user_id → users (cascade); registration_id → registrations (cascade)
  date, clock_in, clock_out, clock_in_ip, clock_out_ip,
  clock_in_latitude/longitude, clock_out_latitude/longitude (nullable)
  status (indexed); absence_type, absence_reason, absence_attachment,
  absence_status, absence_processed_by → users (set null),
  absence_processed_at, absence_admin_notes
  is_verified, verified_by → users (set null), verified_at, notes
  Unique: (user_id, date)

logbooks:
  id: uuid PK; user_id → users (cascade); registration_id → registrations (cascade)
  date, content (text), learning_outcomes (text, nullable)
  status (default 'draft'); is_verified, verified_by → users (set null), verified_at
  mentor_feedback, supervisor_note, supervisor_reviewed_at,
  supervisor_id → users (set null)
  Unique: (user_id, date), (registration_id, date)
```

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DAILY-001 | Attendance and absence requests share one `attendances` table, separated by a global scope | P1 | — | — |
| DD-DAILY-002 | One-per-day enforced by application upsert plus database unique constraint, not either alone | P0 | — | — |
| DD-DAILY-003 | A single AttendanceStatus enum covers present and absent outcomes | P1 | — | — |
| DD-DAILY-004 | Admin sign-off, not a fixed grace period, is the immutability boundary | P0 | — | — |

### 7.1 Storage and Identity

#### DD-DAILY-001 — One Table for Presence

Attendance and absence describe the same fact — what happened to a student on a day — so they
share the `attendances` table, with absence rows distinguished by a non-null absence type
behind a global scope. Splitting them would duplicate the student, registration, and date
columns and force every "what happened Tuesday" query to union two tables. The shared
migration means schema changes touch both models at once, which is correct: they were never
independent.

#### DD-DAILY-002 — Two Walls Against Duplicates

An application-level upsert gives friendly behavior — resubmission edits rather than explodes —
while the database unique constraint gives an unconditional guarantee the application can
never soften. Either wall alone fails its own way: the upsert without the constraint races,
the constraint without the upsert turns every retry into a 500. Together, retries converge
and races serialize, and reporting code trusts one-row-per-day without defensive distinct
clauses.

#### DD-DAILY-003 — One Status Vocabulary

A day ends in exactly one state, present-with-variations or absent-with-reason, so one enum
holds the whole vocabulary. Two enums would demand a discriminator column saying which enum
applies, and every report would branch on it before branching on the value. The single enum
is longer than either half would be, and that length is honest — it mirrors the real
possibility space of a student's day.

#### DD-DAILY-004 — Sign-Off Over Grace Period

An early draft locked rows twenty-four hours after clock-out, reviewed or not. That punished
the diligent mentor who verifies on day three (frozen evidence they can no longer stamp) and
rewarded nobody: unreviewed rows froze without human judgment, while the freeze moment itself
— a clock, not a decision — carried no accountability. Sign-off inverts this: rows stay
workable until a named admin takes responsibility, and from that moment the row is record.
The audit trail gains an author for the freeze, which a timer could never supply.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Duplicate logbook entries per student-day | 0 | Unique constraint violations + upsert convergence in tests |
| Duplicate attendance rows per student-day | 0 | Unique constraint + concurrent-insert test |
| Post-sign-off mutations accepted | 0 | Rejection tests on signed rows |
| Absence double-processing | 0 | Concurrent process attempts converge on one decision |
| Pending absence visibility | Same-day mentor awareness | Pending tab on mentor dashboard |
| Architecture violations (C1–C8, D1–D6) | 0 | `scan_violations.py` on the Journals module |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [placement](J9GBH-placement.md) | Active placement records — logbook and attendance scope to a registration |

### Build Guide

With this spec built, students keep daily journals and clock attendance against live
placements, mentors verify and process, and coordinators export the verified PDF. The record
side is done; the oversight side — visits, supervision logs, compliance — comes next.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [supervision](2EHSE-supervision.md) | Mentors review these entries; visits reference this attendance data |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the school timezone in the school profile is kept current; a wrong timezone shifts the logbook window boundary | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Daily Operations phase; all specs Full
- [Supervision](2EHSE-supervision.md) — mentor oversight of this record
- [Placement](J9GBH-placement.md) — registration scope for entries and attendance
- [Project initialization](QLHDO-project-initialization.md) — FR-GLB-013/014 integrity invariants
- [Architecture](D2FT3-architecture.md) — Action Triad, Entity/DTO boundaries
- [Cross-role proxy ADR](../adr/adr-cross-role-proxy.md) — teacher-as-supervisor verification
- [Exception hierarchy ADR](../adr/adr-exception-hierarchy.md) — RejectedException contract
- [SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md) — dual-channel audit with PII masking
