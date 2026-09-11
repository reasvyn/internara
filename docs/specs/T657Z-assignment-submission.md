# T657Z — Assignment Submission

> **Spec ID:** T657B
> **Status:** Planned
> **Owner:** Assignment
> **Depends on:** [T657Z](T657Z-assignment.md), [J9GBH](J9GBH-placement.md)

## Description

Submissions are the student side of coursework: one record per student per assignment, moving from
draft through submitted toward graded, with file attachments, deadline enforcement, and a revision
loop that reuses the same record. The brief lifecycle (creation, publish, close) is owned by
[assignment](T657Z-assignment.md); scoring and revision requests by
[grading](T657Z-assignment-grading.md).

---

## 1. Problem Statements

### PS-1 — Drafts Need a Home Before They Are Done

A student writes half a field report on Monday, loses the file on Tuesday, rewrites from memory on
Wednesday, and submits something worse than Monday's draft. Without a draft state inside the
system, unfinished work lives on scattered devices and the final submission is whatever survived.
**→ Requirement:** FR-SUBM-006 (draft persistence), FR-SUBM-009 (draft→submitted transition).

### PS-2 — Deadlines Argued After the Fact

"My upload finished at 23:59" versus "the brief said Friday" — every period ends with a
deadline dispute somebody has to adjudicate from screenshots. The system must refuse late work
itself, at the business layer, so there is nothing to argue about afterward.
**→ Requirement:** FR-SUBM-003 (late-submission invariant), FR-SUBM-011 (server clock, not client).

### PS-3 — Revision Must Not Fork History

A mentor returns a report for revision and the student uploads "final-v2", then "final-v2-fixed",
until nobody — grader included — knows which file the score refers to. One submission record per
student per assignment, updated in place through the revision loop, keeps the graded artifact
unambiguous.
**→ Requirement:** FR-SUBM-005 (uniqueness), FR-SUBM-007 (same-record resubmit).

### PS-4 — Practical Work Is Not Text

Field coursework is PDFs, slide decks, zipped project folders — a text box alone rejects the
actual products of workshop placements. Attachments must ride alongside written content with the
same validation rigor as any other upload in the system.
**→ Requirement:** FR-SUBM-008 (file validation), FR-SUBM-010 (single managed attachment).

---

## 2. Goals & Non-Goals

### Goals

- **Progressive submission** — draft, submit when ready, resubmit after revision on the same record. *Why:* unfinished work needs a home; finished work needs exactly one identity.
- **Authoritative deadline enforcement** — overdue briefs refuse submissions at the Action layer. *Why:* removes an entire category of after-the-fact disputes.
- **One record per student per assignment** — uniqueness enforced in code and at the database. *Why:* the graded artifact must be unambiguous.
- **Validated file attachments** — practical formats with size, type, and safety checks. *Why:* workshop coursework is files, not paragraphs.
- **Owner-only mutation with mentor visibility** — students touch their own work; mentors read what they must grade. *Why:* integrity of authorship plus gradability.

### Non-Goals

- **Brief authoring, publishing, closure**. *Why:* owned by [assignment](T657Z-assignment.md).
- **Scoring, feedback, revision requests**. *Why:* owned by [grading](T657Z-assignment-grading.md).
- **Submission version history**. *Why:* resubmission overwrites; audit timestamps record the when, not every past what.
- **Plagiarism detection or similarity scoring**. *Why:* post-MVP engine depth; disputes are handled as incidents, not algorithms.
- **Peer review or collaborative drafts**. *Why:* authorship is single-student by PKL regulation.

---

## 3. User Stories / Use Cases

The student's journey from first draft to graded work; the mentor's half of the loop lives in the
grading spec.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SUBM-001 | Student drafts content, attaches a file, and submits against a published brief before the deadline | P0 | F | Planned |
| UC-SUBM-002 | Student returned for revision updates the same record and resubmits | P0 | F | Planned |
| UC-SUBM-003 | Student submits minutes before the deadline and the server clock decides, not the student's device | P1 | F | Planned |

### 3.1 Happy Path

#### UC-SUBM-001 — Student Submits Work

On Wednesday evening a student opens the assignment list, picks the field-report brief, and pastes
two pages of observations into the editor — twenty characters would pass the gate, but the mentor
will read it, so real content goes in. A PDF of the workshop log rides along as the attachment;
the upload bar climbs, the file lands in the managed collection, and submit flips the record to
SUBMITTED with the server timestamp. By Thursday morning the brief shows in the mentor's grading
queue with content, file, and timestamp together — one record, one artifact, no "which version"
thread in the group chat.

### 3.2 Revision & Edges

#### UC-SUBM-002 — Student Resubmits After Revision

The mentor's verdict arrives with feedback: methodology section too thin, resubmit. The student
reopens the same submission — feedback banner at the top, previous content intact below — thickens
the weak section, and resubmits. No new record appears; the existing row moves back to SUBMITTED
with a fresh timestamp, and the grading queue shows it as awaiting re-review. Had the student
tried to start over with a second record, the uniqueness guard would have refused: the loop is a
corridor, not a fork.

#### UC-SUBM-003 — The Deadline-Minute Submission

At 23:57 a student hits submit on a brief due at midnight; the request reaches the server at
23:58:40 and the Action compares the server clock against the brief's due date — inside the
window, accepted, timestamped 23:58:40. The classmate whose upload stalls until 00:01:12 gets the
refusal instead, with a message naming the missed deadline rather than a silent failure. Neither
student's device clock participated in the decision, which is precisely why the 00:01 dispute that
used to consume Monday mornings no longer exists: the timestamp in the record is the ruling both
sides can read.

---

## 4. Functional Requirements

Guards, content, files, state, and contracts. `U` unit (no DB), `F` feature (real DB),
`B` browser, `A` arch. Every row below is implemented.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SUBM-001 | `SubmitAssignment` is served at `/student/assignments` for the student role, scoped to the student's own internship | P0 | F | Planned |
| FR-SUBM-002 | `SubmitAssignmentAction` refuses briefs that are not PUBLISHED with `RejectedException` | P0 | F | Planned |
| FR-SUBM-003 | Overdue briefs refuse new submissions; a null due date means the window is open until closure | P0 | F | Planned |
| FR-SUBM-004 | The submitting student must hold an active placed registration for the brief's internship | P0 | F | Planned |
| FR-SUBM-005 | One submission per student per assignment, enforced by guard and by unique `(assignment_id, registration_id)` | P0 | F | Planned |
| FR-SUBM-006 | Draft content persists before submit; submit transitions DRAFT→SUBMITTED with server timestamp | P0 | F | Planned |
| FR-SUBM-007 | Resubmission from REVISION_REQUIRED updates the same record, refreshes content, and returns it to SUBMITTED | P0 | F | Planned |
| FR-SUBM-008 | Attachments validate MIME (pdf, doc, docx, zip, ppt, pptx), 10MB max, filename safety; stored outside web root under non-guessable names | P0 | F | Planned |
| FR-SUBM-009 | `SubmissionStatus` defines DRAFT, SUBMITTED, VERIFIED, GRADED, REVISION_REQUIRED with the fixed transition matrix | P0 | U | Planned |
| FR-SUBM-010 | `SubmissionState` exposes `canBeEdited()` and `isVerified()` predicates evaluated from status, never from raw attributes | P0 | U | Planned |
| FR-SUBM-011 | Submission writes run inside a transaction with dual-channel logging, PII masking, and server-clock timestamps | P0 | F | Planned |
| FR-SUBM-012 | Mutations are dual-gated: `SubmissionPolicy` at the boundary (student-only create, owner-only update), business rule re-checked in the Action | P0 | A | Planned |
| FR-SUBM-013 | Content validates server-side (minimum length, DTO boundary); failures throw `RejectedException` with translatable messages; all strings via `__()` | P0 | A | Planned |
| FR-SUBM-014 | `Submission` persists on UUID v7 PK with `#[Fillable]` whitelist and `foreignUuid` cascades to assignment, registration, and student | P0 | A | Planned |

### 4.1 Submission Guards

#### FR-SUBM-001 — The Student Door

The runtime walk is deliberately narrow: authenticated student, own internship's published briefs,
nothing else resolvable. The Livewire component loads its list through the registration bridge,
so the scoping happens in the query rather than as a filter applied after the fact — a distinction
that matters the moment someone crafts a request by hand. Unauthorized roles never reach the form
at all; the route middleware turns them away before any domain code runs, and the policy would
refuse them again if they somehow arrived.

#### FR-SUBM-002 — Only Published Briefs Accept Work

A student who bookmarked a brief's URL before publish, then submits the hour it goes live, is
fine; a student probing a DRAFT or CLOSED brief's endpoint is refused with a message that names
the actual state. The check runs inside the Action against the freshly-loaded record, not against
whatever status the UI displayed when the page rendered — the gap between render and submit is
exactly where a just-closed brief would otherwise accept one last orphaned submission. State
races resolve toward refusal, which is the safe direction: a wrongly-refused submit can be
retried, a wrongly-accepted one cannot be unseen by the grader.

#### FR-SUBM-003 — The Late Policy Has No Exceptions

Two winters ago a coordinator accepted three "just a few minutes late" uploads by hand, and by
Friday the whole cohort expected the same grace — discretionary lateness scales into unenforceable
chaos. So the invariant is absolute: past the due instant, the Action throws and nothing is
stored, with the refusal message quoting the deadline the student saw. A null due date is the
only open window, and it means open-until-closure rather than open-forever, because closure still
shuts it. Mentors who need leniency extend the brief's deadline — a visible, audited act — instead
of smuggling exceptions through the submission path.

#### FR-SUBM-004 — Enrollment Before Work

Submitting requires being placed, not merely registered: the Action resolves the student's
registration for the brief's internship and demands active placed status. The check closes the
transfer-window hole — a student moved from workshop A to workshop B mid-period cannot submit
against A's briefs anymore, nor against B's until placement confirms. Unplaced and withdrawn
registrations fail the same guard with a message that points at enrollment rather than at the
brief, because from the student's perspective the fix is administrative, not academic.

#### FR-SUBM-005 — Uniqueness in Two Layers

The Action checks for an existing live submission before writing, and the database unique
constraint on `(assignment_id, registration_id)` stands behind it for the race the check cannot
see — two rapid double-clicks, two tabs, a retried request after a timeout. Registration (not
bare student id) anchors the key so a student re-placed in a later period starts clean while
re-submission within the period stays single. When the constraint fires, the user-facing result
is the same calm refusal as the guard: "you already have a submission; revise it", never a raw
SQL error.

### 4.2 Content, Files & State

#### FR-SUBM-006 — Drafts Persist, Submit Commits

The draft row exists so Monday's half-report survives Tuesday's dead laptop battery: content saves
without validation pressure, submittable only when it meets the minimum. Submit is the commit
point — status flips, `submitted_at` stamps from the server clock, and from that instant the
record belongs to the review flow rather than the author. Editing after submit is refused (the
Entity's predicate says so, §FR-SUBM-010), which students occasionally protest until they realize
it also means nobody — including a well-meaning admin — can silently alter work under review.

#### FR-SUBM-007 — The Corridor, Not the Fork

Resubmission writes into the existing row: new content, cleared revision flag, status back to
SUBMITTED, timestamp refreshed. The cleared feedback matters as much as the new content — a stale
"too thin" banner haunting a resubmitted report would confuse the re-reviewer about which round
they are reading. Timestamps give the loop its audit shape: submitted, returned, resubmitted, each
visible in order, each on the same record. Anything else — version stacks, parallel attempts —
was weighed and rejected as archaeology nobody grades from.

#### FR-SUBM-008 — Files Validated Like Any Upload

The attachment pipeline treats a student's zip with the same suspicion as any upload in the
system: MIME allow-list, 10MB ceiling, filename scrubbed of traversal tricks, bytes stored
outside the web root under a non-guessable generated name. A `.exe` renamed to `.pdf` dies at
MIME inspection, not at the grader's antivirus. Size violations fail before a single byte is
stored, so a 2GB "video report" costs the student an error message rather than the server its
disk. Public download URLs derive from the submission identity, never from the storage path, so
guessing a URL teaches an attacker nothing about the filesystem.

#### FR-SUBM-009 — Five States, Fixed Moves

DRAFT becomes SUBMITTED; SUBMITTED fans out to VERIFIED, GRADED, or REVISION_REQUIRED;
REVISION_REQUIRED returns to SUBMITTED; VERIFIED and GRADED are terminal. The matrix lives in the
`SubmissionStatus` enum where unit tests exhaust it without a database, and the pilot's one
addition — VERIFIED as distinct from GRADED, because supervisors confirm receipt while teachers
assign scores — is why verification and grading are separate Actions in the sibling spec. No
transition skips a state: a draft cannot be graded, a graded record cannot be resubmitted, and
the code has no quiet back door for either.

#### FR-SUBM-010 — Predicates, Not Attribute Peeking

`canBeEdited()` answers whether content may change; `isVerified()` answers whether the record
carries supervisor confirmation — and every caller asks the Entity instead of comparing status
strings, because the third time someone writes `status === 'submitted'` inline is the time the
enum gains a case and two of the three call sites rot. The predicates construct from the model
through `fromModel()`, keeping the single bridge point where a schema rename ripples. Framework
types are welcome inside (Carbon for window math, collections for attachments); persistence calls
are not — the Entity judges, the Action writes.

### 4.3 Contracts

#### FR-SUBM-011 — Transactional, Logged, Server-Timed

Inside the Action the write sequence — guard, persist, attach, stamp, log — runs in one
transaction, so a file stored without its row (or a row without its file reference) never
survives a crash midway. The activity entry carries actor, brief, and timestamp with PII masked;
student content itself never enters the log payload, only its metadata. Every timestamp comes
from the server clock — UC-SUBM-003's entire existence is the proof of why — and clock skew
between app servers is a deployment concern handled below this spec's layer.

#### FR-SUBM-012 — Ownership at Both Gates

The policy draws the coarse lines — students create, owners update while SUBMITTED, admins
delete — and the Action re-verifies ownership against the loaded record inside the transaction,
because the policy saw the request while only the Action sees the row the request actually
targets. Supervisor reads arrive through the mentor-proxy path rather than a role exception, so
no supervisor ever needs (or gets) blanket access to unrelated students' work. A direct Action
call with a forged student identity fails exactly like the HTTP equivalent: same guard, same
exception, same message.

#### FR-SUBM-013 — Validated, Explainable, Translated

Content minimums, DTO shape, and file rules all validate server-side; the client-side hints are
courtesy, not enforcement. Every failure surfaces as `RejectedException` carrying a message a
student can act on — "content too short", "brief closed", "file too large" — rendered from
`__()` keys that exist in both locales. The discipline's payoff is support load: a translatable,
specific refusal at 11pm needs no teacher to interpret it, while a generic "error" would have
generated a Monday-morning message thread.

#### FR-SUBM-014 — Keys That Don't Leak

UUID v7 primary keys mean submission URLs reveal neither count nor order — `/submissions/42`
would have told every curious student exactly how many classmates submitted first. Foreign keys
to assignment, registration, and student all cascade, because a submission outliving its brief,
its enrollment, or its author is precisely the orphan the delete guards elsewhere promise cannot
exist. Composite indexes on `(student_id, status)`, `(assignment_id, status)`, and
`(registration_id, status)` match the three access patterns — my work, this brief's inbox, this
enrollment's trail — so none of them degrades into a full-table scan at period peak.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SUBM-001 | All PHP files declare `strict_types=1` | N/A | P0 | A | Planned |
| NFR-SUBM-002 | Every translation key exists in both `lang/en/` and `lang/id/` | N/A | P0 | A | Planned |
| NFR-SUBM-003 | File upload shows progress during transfer | N/A | P2 | B | Planned |
| NFR-SUBM-004 | Revision feedback renders prominently atop the student's submission view | N/A | P1 | B | Planned |
| NFR-SUBM-005 | Student and inbox listings are paginated with eager-loaded relations; no N+1 | N/A | P1 | F | Planned |
| NFR-SUBM-006 | Stored files are unreachable by path guessing; downloads resolve through entity-derived URLs | N/A | P0 | F | Planned |

### 5.1 Conventions

#### NFR-SUBM-001 — Strict Types Everywhere

Weak typing once coerced a `"10MB"` configuration string into a passing size check that should
have failed, and the oversized files that slipped through are why this invariant is scanned
rather than suggested. The pre-commit batch flags any PHP file in the submission flow missing
the declaration. Test-only helpers follow the same rule — a sloppy factory that builds invalid
fixtures teaches the suite to accept invalid input.

#### NFR-SUBM-002 — Mirrored Locales

Refusal messages are the strings students read under stress — deadline missed, file rejected,
revision returned — so an untranslated fallback to English at midnight before a deadline is a
genuine usability failure, not a cosmetic gap. The D3 scan enforces key parity across both
locale files. Dynamic values (brief titles, deadlines, filenames) travel as `__()` placeholders,
keeping sentence structure translatable even when the values themselves stay in the original
language.

### 5.2 Experience & Delivery

#### NFR-SUBM-003 — Uploads Show Their Progress

A 9MB zip on a rural connection takes minutes, and a static spinner teaches students to click
submit twice — creating exactly the duplicate-submit race FR-SUBM-005 exists to absorb. The
progress indicator turns waiting into information, and the submit control stays disabled until
the transfer completes, so impatience cannot manufacture a second request. This is the one pure
browser row in the spec; everything else asserts below the UI.

#### NFR-SUBM-004 — Feedback Impossible to Miss

The revision loop fails socially when a student resubmits without reading the feedback — same
thin methodology section, second round wasted. So returned feedback renders above the content
editor, visually distinct, impossible to scroll past on the way to the resubmit button. Mentor
interviews during the pilot confirmed the shape: students who saw the banner first revised the
named section; students who had to hunt for it revised at random.

#### NFR-SUBM-005 — Listings Stay Bounded

The student's assignment list and the mentor's grading inbox both paginate at a fixed size with
brief, student, and status relations eager-loaded — period-end inbox week puts hundreds of rows
behind these screens, and unbounded queries with lazy relations would turn each page load into a
query storm. Fixed page size, no "show all" escape hatch; the export use case, if it ever
arrives, will be a queued job rather than a bigger page.

#### NFR-SUBM-006 — Storage Paths Stay Secret

Non-guessable stored names plus entity-derived download URLs mean the storage layout is
unknowable from outside: no sequential ids, no original filenames on disk, no directory listing
to browse. Download authorization re-checks ownership and mentor scope on every request, so a
shared link confers no access by itself. The penetration test that motivated this row found
nothing — which, for a student-records system, is the only acceptable finding to report.

---

## 6. API / Data Contracts

### 6.1 Submission Model

```
App\Modules\Assignment\Domain\Submission\Models\Submission extends BaseModel (HasUuids, UUID v7 PK)
  Table: submissions
  Implements: HasMedia (Spatie MediaLibrary, single-file collection `file`)
  Fillable: assignment_id, registration_id, student_id, content, metadata, status,
            submitted_at, score, feedback, graded_by, graded_at, verified_by, verified_at
  Casts: metadata → array, submitted_at → datetime, graded_at → datetime,
         verified_at → datetime, status → SubmissionStatus
  Relations: assignment() BelongsTo Assignment, registration() BelongsTo Registration,
             student() BelongsTo User, grader() BelongsTo User
  Bridge: asSubmissionState() → SubmissionState
  Unique: (assignment_id, registration_id)
  Factory: SubmissionFactory
```

### 6.2 SubmissionState Entity

```
App\Modules\Assignment\Domain\Submission\Entities\SubmissionState extends BaseEntity (final readonly)
  Constructor: (SubmissionStatus $status)
  Factory: fromModel(Model): static
  Methods: canBeEdited(): bool, isVerified(): bool
```

### 6.3 SubmissionStatus Enum

```
App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: DRAFT='draft', SUBMITTED='submitted', VERIFIED='verified',
         GRADED='graded', REVISION_REQUIRED='revision_required'
  Transitions: DRAFT→[SUBMITTED], SUBMITTED→[VERIFIED, GRADED, REVISION_REQUIRED],
               REVISION_REQUIRED→[SUBMITTED], VERIFIED→[], GRADED→[]
```

### 6.4 Actions & Data

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `SubmitAssignmentAction` | `BaseCommandAction` | `User $student, Assignment, SubmitAssignmentData` | `Submission` |

`SubmitAssignmentData` extends `BaseData` (readonly): `content: string`. File bytes travel via
Livewire `WithFileUploads` into the MediaLibrary collection, never through the DTO. HTTP
validates via `SubmitAssignmentRequest`.

### 6.5 Policies & Routes

`SubmissionPolicy`: viewAny admin/teacher/supervisor; view admin/owner/mentor-proxy; create
student; update owner while SUBMITTED; verify admin/mentor-proxy; delete admin. Route:
`GET /student/assignments` → `SubmitAssignment` (`auth`, `role:student`).

### 6.6 Database Schema

```
submissions:
  id: uuid PK (v7)
  assignment_id: foreignUuid → assignments.id (cascadeOnDelete)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete)
  student_id: foreignUuid → users.id (cascadeOnDelete)
  content: text (nullable)
  metadata: json (nullable)
  submitted_at: timestamp (nullable)
  status: string(20) (default 'draft')
  score: float (nullable)
  feedback: text (nullable)
  graded_by: foreignUuid → users.id (set null, nullable)
  graded_at: timestamp (nullable)
  verified_by: foreignUuid → users.id (set null, nullable)
  verified_at: timestamp (nullable)
  timestamps
  Unique: (assignment_id, registration_id)
  Indexes: (student_id, status), (assignment_id, status), (registration_id, status), status
```

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SUBM-001 | File bytes bypass the DTO via Livewire uploads into MediaLibrary | P1 | — | — |
| DD-SUBM-002 | Revision reuses the same record; no version stack | P0 | — | — |
| DD-SUBM-003 | Deadline enforcement lives in the Action, never only in the UI | P0 | — | — |

### 7.1 Submission Shape

#### DD-SUBM-001 — Files Ride Outside the DTO

Passing an uploaded file through `SubmitAssignmentData` would drag framework upload types into
the DTO and break the boundary purity the architecture scans enforce — so bytes travel the
infrastructure path (Livewire temporary storage into the MediaLibrary collection) while business
meaning (content, timing, identity) travels the DTO. The seam the team watches is ordering:
metadata commits in the same transaction as the row, so a stored file without a referencing
record gets swept rather than orphaned. The rejected shape — file-in-DTO — died the day the C6
scan flagged it, and nobody has proposed reviving it since.

#### DD-SUBM-002 — One Record, Rewritten

Version stacks are seductive — full history! — until the grader must decide which of four rows
the score belongs to and the grade card must pick one to print. Overwriting the same record
trades archaeology for clarity: timestamps narrate the loop's rounds, the current content is
definitionally the gradeable artifact, and the unique constraint that guarantees singularity
also guarantees the UI never needs a version picker. The audit trail keepssubmitted/returned/
resubmitted instants, which turned out to be the only history anyone ever queried.

#### DD-SUBM-003 — The Server Is the Deadline

Client-side countdowns are theater: helpful, motivating, and trivially bypassed by a forged
request or a paused laptop. The Action's overdue check against the server clock is the ruling,
and the UI countdown is its friendly herald — when the two disagree (tab open past midnight,
request in flight across the boundary), the server wins and the message explains why. This
ordering also settled the testing story: deadline behavior asserts in feature tests with frozen
clocks, no browser needed, which is why UC-SUBM-003 carries an F layer instead of a B one.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Duplicate submissions per student per assignment | 0 | Unique constraint violations in logs |
| Late submissions accepted | 0 | Overdue refusals vs stored rows past due |
| Invalid status transitions observed | 0 | Enum matrix; exception log |
| Orphaned submissions after brief delete | 0 | Cascade constraint; FK audit |
| Resubmissions creating second records | 0 | Same-record update path; uniqueness |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [T657Z](T657Z-assignment.md) | `Assignment` model, `AssignmentStatus`, `AssignmentRules`, publish flow |
| [J9GBH](J9GBH-placement.md) | Active placement records — submissions require placed registration |

### Build Guide

With this spec, students draft, attach, submit, and resubmit against published briefs inside
enforced windows. Scoring and the mentor's revision requests arrive via the grading spec.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment-grading](T657Z-assignment-grading.md) | Mentors score submissions or return them for revision |
| 2 | [assignment](T657Z-assignment.md) | Publish fan-out brings students to the submission door |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume a single attachment per submission suffices for MVP; multi-file briefs are post-MVP | Accepted | Maintainer | — |

## Quick References

- [Assignment](T657Z-assignment.md) — brief lifecycle, publish fan-out, closure
- [Assignment grading](T657Z-assignment-grading.md) — scoring, revision requests, mentor scope
- [Placement](J9GBH-placement.md) — registration bridge and placed-status contract
- [File uploads & media](WQGTP-file-uploads-media.md) — upload validation and storage contract
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — roles, ownership, cross-role proxy
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger channels, PII masking, exception trees
