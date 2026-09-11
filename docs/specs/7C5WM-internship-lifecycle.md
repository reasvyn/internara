# 7C5WM — Internship Lifecycle

> **Spec ID:** 7C5WM
> **Status:** Full
> **Owner:** Program
> **Depends on:** [XW6F5](XW6F5-academic-year-management.md), [NTHQA](NTHQA-partnership-management.md)

## Description

Defines the internship program lifecycle in the Program module: program definition, a strict
status state machine, registration window governance, pre-close readiness verification, formal
closure with immutable archival, and single-format CSV exchange. Group and cohort management
lives in [IT0OE](IT0OE-internship-groups.md); enrollment intake lives in
[MBB5R](MBB5R-registration.md).

---

## 1. Problem Statements

### PS-1 — Internship Status Lifecycle Without Guardrails

Internships progress through DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED, and finally
ARCHIVED, but without enforced transition rules an admin could move an ACTIVE internship back
to DRAFT or jump from DRAFT straight to COMPLETED. Invalid transitions corrupt downstream
data — registrations scoped to the internship, attendance records, grade calculations — and
produce inconsistent reports.
**→ Requirement:** FR-LIFE-013–FR-LIFE-020 (state machine), NFR-LIFE-002 (rejection contract).

### PS-2 — Registration Windows Require Date and Status Coordination

Students may register only inside the window bounded by registration start and end dates, and
only while the internship status accepts registrations. Date range alone is insufficient: a
DRAFT internship inside its date window must still refuse registrations, as must a PUBLISHED
one outside the window. One entity must own this compound check so every consumer answers it
identically.
**→ Requirement:** FR-LIFE-021–FR-LIFE-027 (InternshipPeriod governance).

### PS-3 — Premature Internship Closure Produces Incomplete Data

Closing an internship while assessments are unfinalized, submissions ungraded, supervision
logs or attendance unverified, or certificates unissued yields incomplete grade cards and a
broken certification chain. A comprehensive readiness verification across five domains must
gate closure, and closure itself must lock grades, issue remaining certificates, snapshot the
record, and archive it — never silently mutate history afterward.
**→ Requirement:** FR-LIFE-028–FR-LIFE-041 (readiness, CloseProgramProcess, snapshot, alumni).

### PS-4 — Internship Deletion Must Protect Referential Integrity

Internships are referenced by placements, registrations, and downstream records. Deleting an
internship with live placements or registrations orphans those records and breaks the
relational guarantees the rest of the system relies on.
**→ Requirement:** FR-LIFE-009 (deletion guard), NFR-LIFE-005 (in-transaction verification).

---

## 2. Goals & Non-Goals

### Goals

- **Strict status state machine** — every transition validated at the Action level. *Why:* illegal moves corrupt registrations, attendance, and grades downstream.
- **Registration windows governed by one entity** — `InternshipPeriod` owns the compound status-plus-date check. *Why:* a single owner prevents divergent eligibility answers.
- **Pre-close readiness verification across five domains** — assessments, submissions, supervision logs, attendance, certificates. *Why:* closure on incomplete data produces broken grade cards.
- **Coordinated closure with immutable archival** — seven-step process ending in a versioned snapshot, locked records, and alumni read-only access. *Why:* regulation demands multi-year immutable retention.
- **Deletion blocked when related records exist** — guard evaluated inside the deleting transaction. *Why:* orphaned placements and registrations break referential integrity.
- **Single-format CSV exchange** — one import shape, one export shape. *Why:* CSV covers the government-system handoff the MVP needs without a format matrix.

### Non-Goals

- **Group and cohort management**. *Why:* owned by [IT0OE](IT0OE-internship-groups.md).
- **Separate timeline or phases submodule**. *Why:* phases are JSON configuration on the program row, always read and written as a set.
- **Automatic status transitions**. *Why:* every transition is a deliberate admin act, auditable with an actor attached.
- **Multi-tenant internship isolation**. *Why:* single-tenant by product definition.
- **Real-time status-change notifications**. *Why:* status events are fire-and-forget; fanout matrices are post-MVP depth.

---

## 3. User Stories / Use Cases

Four operator journeys anchor this spec. The first two cover the everyday setup flow; the last
two cover the high-stakes closure flow that the archival regulation exists to protect.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-LIFE-001 | Admin creates an internship as DRAFT and publishes it to PUBLISHED | P0 | F | Full |
| UC-LIFE-002 | Student registration attempt is accepted or rejected by the compound status-plus-date check | P0 | F | Full |
| UC-LIFE-003 | Admin runs the five-domain pre-close readiness check and sees per-domain blockers | P0 | F | Full |
| UC-LIFE-004 | Admin closes a ready program through the coordinated process ending in ARCHIVED with alumni read-only access | P0 | F | Full |

### 3.1 Program Setup

#### UC-LIFE-001 — Admin Creates and Publishes an Internship

Picture the start of the school year in the admin office: the coordinator opens the
internship list, clicks create, and fills in a name with start and end dates while the
active academic year fills itself in. The new row lands as DRAFT, invisible to students,
and only a deliberate publish step moves it to PUBLISHED. That two-beat rhythm — draft
first, publish explicitly — is what keeps half-configured programs from leaking into the
registration window before their dates and documents are actually ready.

#### UC-LIFE-002 — Student Registers During the Open Window

When a student hits register, the system resolves the internship into an `InternshipPeriod`
and asks one question: accepting registrations right now, considering both status and
date. A PUBLISHED program inside its window says yes; a DRAFT program or a closed window
says no with a message that names the actual reason. Because every entry point asks the
same entity, the website, the validation rule, and the admin preview can never disagree
about whether registration is open.

### 3.2 Closure

#### UC-LIFE-003 — Admin Runs the Pre-Close Readiness Check

Closing week is the most anxious moment of the cycle, so the readiness check reads like a
report card, not a boolean. The admin opens an ACTIVE internship, asks for readiness, and
gets five domain verdicts — assessments finalized, submissions graded, supervision logs
verified, attendance verified, certificates issued — each with totals and pending counts.
Anything pending names its blockers plainly, and closure stays unavailable until every
domain passes, which is exactly the protection PS-3 demands.

#### UC-LIFE-004 — Admin Closes a Ready Program

Once readiness is green, closure runs as a single coordinated process rather than seven
disconnected admin chores: re-verify readiness, trigger the program quality evaluation,
freeze the grades, issue the remaining certificates, snapshot and lock everything, move
student accounts to alumni status, and write the archive report. The program lands in
ARCHIVED, graduates keep read-only access to their certificates and grades, and only a
super admin can ever reverse the terminal step — with a full audit trail explaining why.

---

## 4. Functional Requirements

The table below is the complete normative list. Groups in §4.1–§4.6 collect the detail
narratives; the table itself is the single source of requirement rows.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-LIFE-001 | `Internship` model uses `#[Fillable]` with academic_year_id, name, start_date, end_date, description, status, phases, required_document_ids, grading_weights | P0 | A | Full |
| FR-LIFE-002 | `Internship` casts status to `InternshipStatus`, dates to date, JSON columns to json | P0 | A | Full |
| FR-LIFE-003 | `Internship` relates belongsTo AcademicYear (nullable, set null), hasMany Placements, hasMany Registrations | P0 | A | Full |
| FR-LIFE-004 | `Internship` exposes `asInternshipPeriod()` and `asInternshipState()` entity bridges | P0 | U | Full |
| FR-LIFE-005 | `InternshipData` DTO requires name, academicYearId, startDate, endDate and accepts optional description, status, registrationStartDate, registrationEndDate | P0 | U | Full |
| FR-LIFE-006 | `CreateInternshipAction` auto-fills the active academic year and creates with DRAFT status | P0 | F | Full |
| FR-LIFE-007 | `UpdateInternshipAction` validates status transitions via `InternshipStatus::canTransitionTo()` | P0 | F | Full |
| FR-LIFE-008 | `UpdateInternshipAction` rejects illegal transitions with `RejectedException` | P0 | F | Full |
| FR-LIFE-009 | `DeleteInternshipAction` blocks deletion when placementCount or registrationCount is above zero | P0 | F | Full |
| FR-LIFE-010 | `InternshipManager` lists name, academic year, date range, status, and action buttons | P1 | F | Full |
| FR-LIFE-011 | `InternshipManager` searches by name and filters by status, academic year, and date range | P1 | F | Full |
| FR-LIFE-012 | `InternshipPolicy` grants view to all five roles and create/update/delete to admin only, with deletion checking placements and registrations | P0 | U | Full |
| FR-LIFE-013 | `InternshipStatus` defines DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED, ARCHIVED | P0 | U | Full |
| FR-LIFE-014 | Valid transitions are DRAFT to PUBLISHED or CANCELLED, PUBLISHED to ACTIVE or CANCELLED, ACTIVE to COMPLETED or CANCELLED, COMPLETED to ARCHIVED | P0 | U | Full |
| FR-LIFE-015 | COMPLETED and CANCELLED admit no onward transition except the single COMPLETED to ARCHIVED step | P0 | U | Full |
| FR-LIFE-016 | `isAcceptingRegistrations()` is true for PUBLISHED and ACTIVE only | P0 | U | Full |
| FR-LIFE-017 | `isTerminal()` is true for COMPLETED, CANCELLED, and ARCHIVED | P0 | U | Full |
| FR-LIFE-018 | `UpdateInternshipAction` enforces the state machine on single-record changes | P0 | F | Full |
| FR-LIFE-019 | `BatchUpdateInternshipStatusAction` runs only behind the readiness gate owned by the manager selection flow | P0 | F | Full |
| FR-LIFE-020 | `InternshipCreated` fires after creation and `InternshipStatusBatchUpdated` after batch update | P1 | F | Full |
| FR-LIFE-021 | `InternshipPeriod` encapsulates status, registration start and end, and academic year bounds | P0 | U | Full |
| FR-LIFE-022 | `isAcceptingRegistrations()` combines status and date window in one verdict | P0 | U | Full |
| FR-LIFE-023 | `isRegistrationWindowOpen()` evaluates the date range alone, ignoring status | P1 | U | Full |
| FR-LIFE-024 | `isBeforeRegistrationWindow()` and `isAfterRegistrationWindow()` distinguish early from late attempts for messaging | P1 | U | Full |
| FR-LIFE-025 | `isWithinAcademicYear()` and `datesSpanOutsideAcademicYear()` validate program dates against the academic year | P0 | U | Full |
| FR-LIFE-026 | The `OpenForRegistration` validation rule gates registration attempts through `InternshipPeriod` | P0 | F | Full |
| FR-LIFE-027 | `InternshipForm` carries registration start and end fields | P1 | F | Full |
| FR-LIFE-028 | `ReadCloseReadinessAction` accepts an internship and returns all five readiness domains | P0 | F | Full |
| FR-LIFE-029 | The assessments domain requires `finalized_at` on every active registration | P0 | F | Full |
| FR-LIFE-030 | The submissions domain requires zero submissions left in DRAFT, SUBMITTED, or REVISION_REQUIRED | P0 | F | Full |
| FR-LIFE-031 | The supervision domain requires every log verified | P0 | F | Full |
| FR-LIFE-032 | The attendance domain requires every record verified | P0 | F | Full |
| FR-LIFE-033 | The certificates domain requires all certificates ISSUED with at least one present | P0 | F | Full |
| FR-LIFE-034 | Each domain reports passed, total, pending, and a human-readable message | P0 | F | Full |
| FR-LIFE-035 | `InternshipManager` renders readiness results with per-domain pass and fail indicators | P1 | B | Full |
| FR-LIFE-036 | `CloseProgramProcess` coordinates seven steps: readiness check, quality evaluation trigger, assessment finalization, certificate issuance, program archival, student account archival, archive report | P0 | F | Full |
| FR-LIFE-037 | Closure writes a versioned JSON snapshot capturing roster, grade composites, attendance summary, logbook statistics, assignment and rubric scores, evaluation results, and certificate serials | P0 | F | Full |
| FR-LIFE-038 | Closure locks source records so archived programs are read-only at model, policy, and UI layers | P0 | F | Full |
| FR-LIFE-039 | Archived student accounts move to `AccountStatus::ARCHIVED` with a read-only dashboard for certificates and grades and no registration, logbook, or attendance writes | P0 | F | Full |
| FR-LIFE-040 | Exceptional un-archive from ARCHIVED to COMPLETED is restricted to super_admin and writes a full audit entry | P0 | F | Full |
| FR-LIFE-041 | Archived data is retained indefinitely with no automatic deletion; post-expiry removal is manual and database-level | P1 | — | Full |
| FR-LIFE-042 | `InternshipManager` imports one CSV shape with name and description columns | P2 | F | Full |
| FR-LIFE-043 | CSV import creates DRAFT internships dated to the active academic year | P2 | F | Full |
| FR-LIFE-044 | `InternshipManager` exports the filtered internship list to CSV | P2 | F | Full |
| FR-LIFE-045 | CSV export includes name, description, status, dates, and academic year | P2 | F | Full |

### 4.1 Program Definition and Persistence

#### FR-LIFE-001 — Fillable contract on Internship

Some years ago a mass-assignment slip wrote a status value straight from a form payload, and
the program went live before its dates existed. The `#[Fillable]` allow-list is the scar
tissue from that class of bug: exactly nine named attributes may be bulk-filled, everything
else takes the explicit path. Reviewers check this list the way accountants check a safe —
anything added here must justify why it deserves the fast lane.

#### FR-LIFE-002 — Casts keep types honest

Dates that behave like strings corrupt every comparison they touch, and a status that
behaves like a string defeats the enum's transition guards. The casts row exists so the
model hands the rest of the system real dates, a real status enum, and real arrays from
the JSON columns. When a date comparison misbehaves, this is the first place to look, and
it is usually innocent — which itself speeds up debugging.

#### FR-LIFE-003 — Relations with explicit delete behavior

An internship never stands alone: it belongs to an academic year and owns placements and
registrations. The academic-year link is deliberately nullable with set-null semantics, so
retiring a year never cascades into deleting programs. Placements and registrations point
back the other way, and their very existence is what arms the deletion guard in FR-LIFE-009.

#### FR-LIFE-004 — Entity bridges off the model

Nobody queries business questions against raw attributes anymore. The two bridge methods
hand callers purpose-built entities — one for registration-window questions, one for
deletion and lifecycle questions — so rules live in one testable place. A column rename
ripples to exactly one bridge method instead of a dozen scattered comparisons.

#### FR-LIFE-005 — InternshipData shape

Four required fields and four optional ones: that is the entire creation and update
vocabulary. The DTO draws a hard line so Livewire forms cannot smuggle extra attributes
into the Action layer. Optional registration dates default to unset rather than to
invented values, which keeps "no window configured" distinguishable from "window set to
something."

#### FR-LIFE-006 — Creation defaults that prevent orphans

Left to themselves, admins forget the academic-year dropdown, and programs end up dateless
or yearless. Creation auto-fills the active year and pins the status to DRAFT, so the
worst a distracted admin can produce is a draft sitting safely inside the current year.
Publishing stays a separate conscious act, never a side effect of creating.

#### FR-LIFE-007 — Transition validation on update

Every status change on a single record passes through the enum's transition map before
anything is written. Think of it as a bouncer with a guest list: DRAFT may proceed to
PUBLISHED, anything else is turned away at the door. The Action does not invent policy;
it asks the enum, which owns the map in exactly one place.

#### FR-LIFE-008 — Illegal transitions fail loudly

A rejected transition is a business-rule violation, not a validation typo, so it throws
`RejectedException` with a message the admin can actually act on. Silent coercion — say,
quietly ignoring a bad target status — would leave the admin believing the program moved
when it did not. Loud failure with a translatable reason is the kinder behavior.

#### FR-LIFE-009 — Deletion guard

Imagine deleting a program mid-cycle and watching hundreds of registrations dangle. The
guard counts placements and registrations inside the same transaction as the delete, and
any nonzero count aborts the whole operation. Admins remove or reassign the dependents
first; there is no fast path around the arithmetic, which is precisely the point.

#### FR-LIFE-010 — Manager list columns

The admin list shows exactly what a coordinator scans for: which program, which year,
which dates, which status, and what can be done about it. Columns were chosen by watching
coordinators work — they sort by status first, then squint at date ranges. Anything more
would push the actions off-screen on the school's aging office monitors.

#### FR-LIFE-011 — Search and filters

With dozens of programs across years, scrolling is not a strategy. Name search plus
status, year, and date-range filters narrow the list to the handful under discussion.
The date filters exist because "which programs ran last semester" is one of the most
asked questions in the office, and answering it should take seconds.

#### FR-LIFE-012 — Policy split between seeing and changing

Everyone with a login may look at programs — students need to find theirs, mentors need
context — but only admins may create, change, or delete. Deletion additionally re-checks
the dependent counts, so even an admin token cannot force-delete through the HTTP layer
what the Action layer would refuse. Two layers, same answer.

### 4.2 Status State Machine

#### FR-LIFE-013 — Six cases, no more

The enum names every lifecycle phase the domain recognizes, ARCHIVED included. Six cases
feel like a lot until you watch a coordinator explain "completed but not yet archived"
versus "archived and locked" — those are genuinely different states with different write
rules. Adding a seventh would require a spec amendment, which is intentional friction.

#### FR-LIFE-014 — The allowed moves

DRAFT can be published or cancelled; PUBLISHED can go active or be cancelled; ACTIVE can
complete or be cancelled; COMPLETED can only be archived. Read it as the program's life
story: prepare, open, run, finish, preserve. Cancellation is reachable until completion
because real schools cancel programs mid-stream, but completion is a one-way door into
the archival track.

#### FR-LIFE-015 — Terminal states hold the line

COMPLETED and CANCELLED admit no onward move except the single archival step out of
COMPLETED. This is what stops the nightmare edit — an ACTIVE program quietly dragged
back to DRAFT to "fix" its dates, invalidating a semester of records. Terminal means
terminal; history does not get rewritten through the status column.

#### FR-LIFE-016 — Who may accept registrations

Only PUBLISHED and ACTIVE programs accept registrations, which mirrors how schools
actually work: announced programs take students, running ones still take latecomers,
drafts and finished ones do not. Every eligibility check in the system funnels through
this predicate, so changing enrollment policy means changing one method.

#### FR-LIFE-017 — Terminal predicate

COMPLETED, CANCELLED, and ARCHIVED count as terminal — the program's active life is over
regardless of which ending it reached. Reports use this to separate the living programs
from the historical ones. ARCHIVED joins the set because nothing about an archived
program should ever appear in an "active" filter again.

#### FR-LIFE-018 — Single-record enforcement point

The update Action is where the state machine bites: one record, one transition, fully
validated. Concentrating enforcement here rather than spreading it across forms means a
new UI surface cannot accidentally invent its own transition rules. If it writes status,
it goes through this gate or it does not write at all.

#### FR-LIFE-019 — Batch updates stay behind the readiness gate

Batch status changes skip per-record transition checks by design — looping a hundred
records through individual guards would be theater — so the safety moves up a level: the
manager only offers batch close after readiness passes for the selection. An unfiltered
query reaching this Action is a caller bug, and the manager's selection flow is the
mitigation. Speed for the common case, with the gate before it rather than inside it.

#### FR-LIFE-020 — Lifecycle events

Creation and batch updates announce themselves as events so side effects — notifications,
cache invalidation, activity entries — stay decoupled from the write. The originating
Action finishes its transaction cleanly and lets listeners handle the fan-out. Anyone
wondering "what reacts to a program being published" starts from the event, not from
grep.

### 4.3 Registration Windows

#### FR-LIFE-021 — One entity for the compound question

Status, registration bounds, and academic-year bounds live together in `InternshipPeriod`
because the eligibility question needs all three at once. Splitting them across helpers
is how you get the classic bug where the date check passes but nobody asked about
status. Cohesion here is correctness, not aesthetics.

#### FR-LIFE-022 — The combined verdict

This predicate is the front door: status accepting AND window open, otherwise closed.
Callers never combine the sub-checks themselves, which means the definition of "open for
registration" exists in exactly one method. When policy changes — say ACTIVE stops
accepting late registrations — one method changes and every surface follows.

#### FR-LIFE-023 — Date-only view for diagnostics

Sometimes the question is narrower: ignoring status entirely, are we inside the dates.
Support tooling and admin previews use this to tell "wrong status" apart from "wrong
time," which produces far better error messages than a flat rejection. It is a
diagnostic lens, never an eligibility verdict on its own.

#### FR-LIFE-024 — Early versus late

"Not yet open" and "already closed" demand different guidance — one says come back, the
other says appeal. These two predicates let the UI speak precisely instead of shrugging
with a generic closed message. Students who know why they were refused complain less
and retry more appropriately.

#### FR-LIFE-025 — Academic-year containment

Programs must live inside their academic year; a window sprawling past the year boundary
breaks reporting periods and confuses the year-scoped queries. These checks catch the
sprawl at validation time, when fixing it costs a keystroke, rather than at report time,
when fixing it costs a migration.

#### FR-LIFE-026 — Validation rule as gatekeeper

The `OpenForRegistration` rule brings the entity's verdict into the validation layer so
registration attempts fail with field-level errors before any write begins. Without it,
eligibility would be enforced only inside the enrollment Action, and other entry points
would each need to remember to ask. The rule remembers for everyone.

#### FR-LIFE-027 — Window fields on the form

Admins set the registration window where they set everything else about the program —
on the same form, not in a settings page they never visit. Colocation is adoption: a
window field buried in configuration stays empty, and an empty window means either
always-closed or always-open depending on defaults. Neither is intended.

### 4.4 Pre-Close Readiness

#### FR-LIFE-028 — One read for all five domains

Readiness is a query, not a mutation, so it lives in a Read Action with no transaction
and no logging ceremony. One call returns all five domains together because closure
decisions need the whole picture — checking assessments on Monday and certificates on
Friday invites the gap where Friday's data changed. Atomic visibility, one snapshot.

#### FR-LIFE-029 — Finalized assessments only

An unfinalized assessment is a grade that can still move, and closing over moving grades
freezes the wrong numbers. This domain insists every active registration carries a
finalization timestamp. The coordinators who lived through a premature close describe
reopening grade cards as the worst week of their year; this check exists so nobody
relives it.

#### FR-LIFE-030 — No submission left behind

Drafts, unreviewed submissions, and revision requests all signal unfinished student work.
Closing while they linger strands students who did their part but await grading. The
domain counts them down to zero, and the pending count doubles as the grader's to-do
list in the final push before closure.

#### FR-LIFE-031 — Verified supervision logs

Unverified supervision logs mean claimed mentoring nobody confirmed. At closure the
system demands every log verified, because the archive asserts the supervision actually
happened. A school defending its program quality to an assessor points at this domain's
green verdict; anything less is an awkward conversation.

#### FR-LIFE-032 — Verified attendance

Attendance is the evidentiary backbone of the whole PKL record, and unverified rows at
closure time are holes in that backbone. The domain treats verification as binary — all
or nothing — since a "mostly verified" archive is not something any coordinator wants
to sign. The pending count tells supervisors exactly how many rows stand between them
and closure.

#### FR-LIFE-033 — Issued certificates, at least one

Certificates are the student's takeaway, and closure without them defeats the program's
purpose. The domain requires every certificate issued and at least one in existence —
the latter catching the misconfiguration where the issuance step never ran at all.
Zero certificates at closure is always a process failure, never a valid end state.

#### FR-LIFE-034 — Uniform domain report shape

Every domain answers in the same four fields — passed, total, pending, message — so the
UI renders five identical cards and admins learn one visual language. Uniformity also
keeps the batch-close flow honest: aggregating five bespoke shapes would invite
shortcuts, while five identical shapes aggregate trivially and completely.

#### FR-LIFE-035 — Readiness rendered for action

The manager turns the report into pass and fail indicators with pending counts that read
as work orders. A failing domain is not a dead end; it is a pointer to exactly what
remains. This rendering is what makes readiness a workflow tool rather than a compliance
ritual — admins leave the screen knowing who to chase, not just that something failed.

### 4.5 Closure, Snapshot, and Archival

#### FR-LIFE-036 — Seven steps, one coordinator

Closure touches readiness, evaluation, grades, certificates, the program record, student
accounts, and reporting — seven steps that must run in order with shared context. The
Process Action owns the sequence so no Livewire component ever orchestrates it; the
component asks for closure and gets back a result. Partial failure handling lives here
too, where the full sequence is visible, instead of smeared across callers.

#### FR-LIFE-037 — Snapshot everything worth keeping

At closure the system writes a versioned JSON document — roster, grade composites,
attendance summary, logbook statistics, assignment and rubric scores, evaluation
results, certificate serials — into the archives table. Years later, when the live
tables have evolved through migrations, that frozen document still answers "what was
true at closure." Versioning matters because the snapshot schema itself will change;
each document carries the version that explains its own shape.

#### FR-LIFE-038 — Locks at every layer

An archive that can be edited is a rumor, not a record. Model guards, policy denials,
and UI removal conspire so archived programs accept no writes through any path.
Defense in depth is deliberate: each layer assumes the others might be bypassed, and
together they make silent mutation of history structurally impossible rather than
merely discouraged.

#### FR-LIFE-039 — Alumni keep their proof

Graduation must not mean losing access to one's own certificate. Archived student
accounts keep their login but land in a read-only dashboard — certificates and grades
visible, registration, logbook, and attendance writes refused. The boundary follows the
student's real need: prove what you did, without being able to rewrite it or enroll
anew under an alumni identity.

#### FR-LIFE-040 — The exceptional way back

ARCHIVED is terminal with exactly one escape hatch: a super admin may move the program
back to COMPLETED, and every use of that hatch writes a full audit entry naming the
actor and the reason. Real emergencies — a closure run against the wrong program, a
certificate batch with wrong serials — need a way back that does not involve database
surgery. Rarity plus auditability is the design: possible, painful-adjacent, and always
explained.

#### FR-LIFE-041 — Retention without automation

Archived data stays indefinitely; nothing deletes it on a schedule. When regulation
eventually permits disposal, removal is a manual database-level operation, documented
but never wired to a timer. Automatic deletion is the failure mode this rule exists to
prevent — a misconfigured scheduler must never be able to vaporize the evidentiary
record the school is legally required to keep.

### 4.6 CSV Exchange

#### FR-LIFE-042 — One import shape

The import accepts a single CSV shape — name and description — because that is the
handoff schools actually receive from government spreadsheets. One shape means one
parser, one error vocabulary, and documentation that fits on a page. Multi-format
matrices were considered and rejected as post-MVP weight.

#### FR-LIFE-043 — Imports land as drafts

Imported rows become DRAFT programs dated to the active academic year, never live ones.
Bulk input is exactly where bad dates and half-named rows enter the system, and drafts
quarantine that mess where an admin reviews it. The active-year dating keeps imports
from scattering across historical years by default.

#### FR-LIFE-044 — Export what you filtered

Export dumps the currently filtered list, not the whole table — the coordinator who
filtered to last year's ACTIVE programs gets exactly those rows. What-you-see-is-what-
you-export turns the list view into a query builder for the handoff file, which is how
the office already thinks about preparing reports for the district.

#### FR-LIFE-045 — Export columns cover the handoff

Name, description, status, dates, and academic year: the receiving spreadsheet needs
identity, state, timing, and scope, and nothing else the program row holds is
meaningful outside the system. Fixed columns keep the downstream templates stable
across exports, so last semester's import template still works this semester.

---

## 5. Non-Functional Requirements

Constraints on how the lifecycle behaves rather than what it does. `N/A` marks
requirements enforced structurally and verified via scans or tests rather than measured
at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-LIFE-001 | Authorization enforced at Policy gatekeeping and Action business-rule layers on every CRUD operation | N/A | P0 | A | Full |
| NFR-LIFE-002 | Illegal status transitions rejected with `RejectedException` carrying a translatable message | N/A | P0 | A | Full |
| NFR-LIFE-003 | Program creation wrapped in a database transaction | N/A | P0 | F | Full |
| NFR-LIFE-004 | Batch status updates wrapped in a database transaction | N/A | P0 | F | Full |
| NFR-LIFE-005 | Deletion verifies related records inside the same transaction as the delete | N/A | P0 | F | Full |
| NFR-LIFE-006 | All Program module classes declare strict types | N/A | P1 | A | Full |
| NFR-LIFE-007 | All models use the `#[Fillable]` attribute | N/A | P1 | A | Full |
| NFR-LIFE-008 | All user-facing strings pass through the `__()` helper | N/A | P0 | A | Full |
| NFR-LIFE-009 | Translation keys exist in both English and Indonesian locale files | N/A | P0 | A | Full |
| NFR-LIFE-010 | Status badges pair distinct colors with text labels from `InternshipStatus::label()` | N/A | P1 | B | Full |
| NFR-LIFE-011 | Readiness UI shows per-domain pass and fail with actionable pending counts | N/A | P1 | B | Full |
| NFR-LIFE-012 | Management UI keeps inputs labeled, keyboard-navigable, and blocked-deletion messages explanatory | N/A | P1 | B | Full |

### 5.1 Authorization and Integrity

#### NFR-LIFE-001 — Two layers, same verdict

A policy that gates the route but an Action that trusts the caller is a locked front door
with the back door open. Every CRUD operation here is checked twice: the policy turns
away the unauthorized at the boundary, and the Action re-validates the business rule so
direct calls cannot bypass it. Either layer alone would be a promise; together they are
a guarantee an auditor can test from both sides.

#### NFR-LIFE-002 — Rejection speaks the user's language

When a transition is refused, the exception carries a message written for the admin, not
the log — translatable, specific, and free of stack traces. Operators see the generic
failure path only for genuinely unexpected errors. The distinction matters because a
confused admin retries the illegal move, while an informed one fixes the actual problem.

#### NFR-LIFE-003 — Creation is atomic

Program creation touches the program row and its side effects together, so the
transaction wraps them all: either the program exists completely or not at all. A
half-created program — row present, year link missing — would poison every year-scoped
query it appears in. Atomicity is cheap insurance against the most confusing class of
support ticket.

#### NFR-LIFE-004 — Batch updates are atomic

A batch close that lands on ninety programs and fails on ten leaves the cohort split
across statuses with no record of intent. Wrapping the batch in one transaction means
the selection moves together or stays put together. Admins retry the whole batch after
fixing the blocker instead of reconciling a half-moved set by hand.

#### NFR-LIFE-005 — Guard and delete share a transaction

Counting dependents and then deleting in a separate transaction opens a race: a
registration created in between orphans itself. Evaluating the guard inside the delete
transaction closes that window. Correctness under concurrency is not paranoia here;
enrollment week is exactly when these races stop being theoretical.

### 5.2 Structural Hygiene

#### NFR-LIFE-006 — Strict types everywhere

Every Program class declares strict types so scalar mismatches surface at the call
boundary instead of coercing silently downstream. Date strings and counts flow through
enough layers here that one quiet coercion could shift a window boundary by a day.
Strictness turns that into an immediate, local, fixable error.

#### NFR-LIFE-007 — Fillable as architecture

The attribute is not decoration; it is the mass-assignment boundary the scanners verify.
Any model missing it fails the convention gate, which is how the team stopped debating
the `$fillable` property era and moved on. New models inherit the habit from the
scaffolding, and review rarely has to mention it twice.

### 5.3 Localization and Presentation

#### NFR-LIFE-008 — No hardcoded user strings

Every string a human reads passes through the translation helper, without exception for
"temporary" admin labels that somehow survive for years. Hardcoded strings are how a
bilingual product quietly becomes monolingual, one rushed commit at a time. The scan
catches what discipline misses.

#### NFR-LIFE-009 — Both locales, always

A key present in English but missing in Indonesian renders as a key-shaped hole in the
primary staff UI — the worst possible place. Mirrored locale files are verified
together, so adding a string means adding it twice or the gate says no. Translators
work from a complete list instead of discovering gaps in production.

#### NFR-LIFE-010 — Status readable without color

Color-blind coordinators and monochrome printouts both defeat color-only badges, so
every status pairs its color with a text label from the enum. The label method owns the
wording, which keeps the badge, the filter dropdown, and the export consistent. This
row absorbed the old separate contrast and labeling rows: one readable-badge rule
instead of three overlapping ones.

#### NFR-LIFE-011 — Readiness that directs work

A readiness screen that only says "failed" sends the admin hunting; one that shows
per-domain verdicts with pending counts sends them to the right person. The counts are
the point — "14 attendance rows unverified" is a task, "attendance: fail" is a mood.
This screen gets used weekly during closing season, so its clarity pays off fast.

#### NFR-LIFE-012 — Forms everyone can operate

Labeled inputs, full keyboard paths, and deletion messages that name the blocking
records: the unglamorous trio that decides whether the school office can actually run
this software. A blocked deletion that says "cannot delete" without saying why
generates a support ticket; one that names the twelve registrations generates a task
list. The former costs the team time forever.

---

## 6. API / Data Contracts

### 6.1 InternshipStatus Enum

```php
// app/Modules/Program/Internship/Enums/InternshipStatus.php
enum InternshipStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ARCHIVED = 'archived';

    public function isAcceptingRegistrations(): bool;  // PUBLISHED, ACTIVE
    public function isTerminal(): bool;                 // COMPLETED, CANCELLED, ARCHIVED
    public function validTransitions(): array;
    public function canTransitionTo(StatusEnum $target): bool;
    public function label(): string;
}
```

### 6.2 Internship Model

```php
// app/Modules/Program/Internship/Models/Internship.php
class Internship extends BaseModel
{
    // #[Fillable]: academic_year_id, name, start_date, end_date, description,
    //              status, phases, required_document_ids, grading_weights
    // Casts: start_date → date, end_date → date, status → InternshipStatus,
    //        phases → json, required_document_ids → json, grading_weights → json
    // Relations: belongsTo AcademicYear (nullable, set null),
    //            hasMany Placements, hasMany Registrations
    // Bridges: asInternshipPeriod() → InternshipPeriod, asInternshipState() → InternshipState
}
```

### 6.3 InternshipState Entity

```php
// app/Modules/Program/Internship/Entities/InternshipState.php
final readonly class InternshipState extends BaseEntity
{
    public int $placementCount;
    public int $registrationCount;

    public static function fromModel(Model $model): static;
    public function canBeDeleted(): bool;  // placementCount === 0 && registrationCount === 0
}
```

### 6.4 InternshipPeriod Entity

```php
// app/Modules/Program/Internship/Entities/InternshipPeriod.php
final readonly class InternshipPeriod extends BaseEntity
{
    public ?InternshipStatus $status;
    public ?Carbon $registrationStartDate;
    public ?Carbon $registrationEndDate;
    public ?Carbon $academicYearStart;
    public ?Carbon $academicYearEnd;

    public static function fromModel(Model $model): static;
    public function isAcceptingRegistrations(?Carbon $now = null): bool;
    public function isRegistrationWindowOpen(?Carbon $now = null): bool;
    public function isBeforeRegistrationWindow(?Carbon $now = null): bool;
    public function isAfterRegistrationWindow(?Carbon $now = null): bool;
    public function hasAcademicYear(): bool;
    public function isWithinAcademicYear(?Carbon $date = null): bool;
    public function datesSpanOutsideAcademicYear(?Carbon $start = null, ?Carbon $end = null): bool;
}
```

### 6.5 InternshipData DTO

```php
// app/Modules/Program/Internship/Data/InternshipData.php
final readonly class InternshipData extends BaseData
{
    public function __construct(
        public string $name,
        public string $academicYearId,
        public string $startDate,
        public string $endDate,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $registrationStartDate = null,
        public ?string $registrationEndDate = null,
    ) {}
}
```

### 6.6 Actions

```php
// app/Modules/Program/Internship/Actions/CreateInternshipAction.php
final class CreateInternshipAction extends BaseCommandAction
{
    public function execute(InternshipData $data): Internship;
}

// app/Modules/Program/Internship/Actions/UpdateInternshipAction.php
final class UpdateInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship, InternshipData $data): Internship;
}

// app/Modules/Program/Internship/Actions/DeleteInternshipAction.php
final class DeleteInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship): void;
}

// app/Modules/Program/Internship/Actions/BatchUpdateInternshipStatusAction.php
final class BatchUpdateInternshipStatusAction extends BaseCommandAction
{
    public function execute(Builder $query, InternshipStatus $status): int;
}

// app/Modules/Program/Internship/Actions/ReadCloseReadinessAction.php
final class ReadCloseReadinessAction extends BaseReadAction
{
    public function execute(Internship $internship): array;
}

// app/Modules/Program/Internship/Actions/CloseProgramProcess.php
final class CloseProgramProcess extends BaseProcessAction
{
    public function execute(Internship $internship): ActionResponse;
}
```

### 6.7 Validation Rule

```php
// app/Modules/Program/Internship/Rules/OpenForRegistration.php
final class OpenForRegistration implements ValidationRule
{
    // Uses InternshipPeriod entity to validate registration eligibility
}
```

### 6.8 Events and Listeners

```php
// app/Modules/Program/Internship/Events/InternshipCreated.php
// app/Modules/Program/Internship/Events/InternshipStatusBatchUpdated.php
// app/Modules/Program/Internship/Events/ProgramArchived.php
// app/Modules/Program/Internship/Listeners/NotifyAdminsInternshipCreated.php
```

### 6.9 Routes

```php
// routes/web/program.php
Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
    });
```

### 6.10 Livewire Components

```php
// app/Modules/Program/Internship/Livewire/InternshipManager.php
// Features: CRUD, search, filter, CSV import/export, batch close, pre-close readiness check UI

// app/Modules/Program/Internship/Livewire/Forms/InternshipForm.php
```

---

## 7. Design Decisions

Seven recorded decisions. Each narrative below carries the reasoning inline rather than in a
fixed label triplet.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-LIFE-001 | Internship phases stored as JSON on the program row rather than a separate table | P1 | — | — |
| DD-LIFE-002 | Business-rule queries encapsulated in final readonly entity classes behind model bridges | P0 | — | — |
| DD-LIFE-003 | Transition validation enforced in the update Action, not in database constraints | P0 | — | — |
| DD-LIFE-004 | Pre-close readiness computed by a dedicated Read Action rather than inside close logic | P0 | — | — |
| DD-LIFE-005 | Batch status updates skip per-record validation and rely on the upstream readiness gate | P1 | — | — |
| DD-LIFE-006 | Closure archives through an immutable versioned snapshot instead of a soft status flag | P0 | — | — |
| DD-LIFE-007 | Un-archive from ARCHIVED stays exceptional, super_admin-only, and fully audited | P0 | — | — |

### 7.1 Modeling

#### DD-LIFE-001 — JSON Phases Over a Separate Table

Phases travel as a complete set — always read together, always written together, never
queried independently across programs. A dedicated table would buy JOIN overhead in
exchange for query power nobody uses. The day someone needs cross-program phase queries,
the JSON has earned its migration; until then it stays a column.

#### DD-LIFE-002 — Entity Bridges for Business Rules

Models hold data, Actions orchestrate, and the rules themselves needed a third home that
tests could reach without a database. The readonly entities behind `asInternshipPeriod()`
and `asInternshipState()` are that home: constructible from any array in a millisecond
test, with schema changes rippling to exactly one bridge. A few extra classes is a fair
price for rules that survive refactors.

#### DD-LIFE-003 — State Machine at the Action Level

Database constraints cannot express "DRAFT may go to PUBLISHED but never to COMPLETED"
without turning the schema into a second codebase. The update Action owns the map via
the enum, which keeps the rule beside the code it guards and inside the transaction it
protects. Direct database writes could theoretically bypass it — closed off by the
standing invariant that all mutations travel through Actions.

### 7.2 Closure Design

#### DD-LIFE-004 — Readiness as a Standalone Read

Five cross-module queries bundled into the close flow would make closure untestable and
unreusable. Extracted as its own Read Action, readiness serves the single-close flow,
the batch flow, and the UI preview from one implementation. Admin-triggered and
infrequent, its five queries cost nothing measurable against the confidence it buys.

#### DD-LIFE-005 — Batch Close Trusts the Upstream Gate

Looping a hundred records through individual transition checks would be slow theater, so
the batch path validates once — at selection time, through readiness — and then moves
the set. The residual risk is a caller bug passing an unfiltered query, contained by
keeping query construction inside the manager rather than accepting arbitrary builders
from anywhere.

#### DD-LIFE-006 — Hard Archive With Snapshot

A soft close — flip to COMPLETED and trust policies — leaves history mutable and the
retention requirement resting on good behavior. The versioned JSON snapshot plus
model, policy, and UI locks turns "please don't edit" into "cannot edit," which is the
only posture a multi-year evidentiary record tolerates. Storage duplication is
negligible at school scale against that guarantee.

#### DD-LIFE-007 — The Audited Escape Hatch

Emergencies happen — closure run against the wrong program, a certificate batch with bad
serials — and database surgery as the only remedy guarantees undocumented changes. The
super_admin un-archive with a mandatory audit entry trades silent heroics for a visible,
attributed, reversible correction. Rare, gated, and always explained: that combination
is what makes the terminal state trustworthy rather than frightening.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Terminal state enforcement | No transitions out of COMPLETED or CANCELLED except COMPLETED to ARCHIVED | `validTransitions()` returns empty for terminal cases |
| Compound check accuracy | Correct verdict for every status and date combination | `InternshipPeriod` unit tests |
| Readiness completeness | All five domains reported on every check | Response carries all five keys |
| Pending count accuracy | Counts match stored records | Seeded integration checks |
| Snapshot fidelity | Snapshot content matches source rows at closure | Closure test compares snapshot to database |
| Alumni access boundary | Alumni read certificates and grades, write nothing operational | Feature tests on archived accounts |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [academic-year-management.md](XW6F5-academic-year-management.md) | Academic year entities — programs run within year date ranges |
| [partnership-management.md](NTHQA-partnership-management.md) | Partnership entities — programs are scoped to partnerships |

### Build Guide

After this spec, the system holds program CRUD with phases, date ranges, status tracking,
readiness verification, and coordinated closure into a locked archive. Programs define the
structure students enroll in. The next step is groups, which divide students within a
program into supervised cohorts.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [internship-groups.md](IT0OE-internship-groups.md) | Groups belong to programs; members reference program phases for scheduling |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume the five readiness domains exhaustively cover closure blockers until a closing cycle proves otherwise | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Programs and Enrollment phases, all specs in build order
- [Internship groups](IT0OE-internship-groups.md) — cohort management inside programs
- [Registration](MBB5R-registration.md) — enrollment intake gated by registration windows
- [Program closure and archival ADR](../adr/adr-program-closure-archival.md) — snapshot, lock, and alumni rationale
- [MVP spec trim ADR](../adr/adr-mvp-spec-trim.md) — what was deliberately left out of MVP scope
- [Action pattern](../guides/arch/action-pattern.md) — Command, Read, and Process contracts
- [Entity pattern](../guides/arch/entity-pattern.md) — readonly entities and model bridges
