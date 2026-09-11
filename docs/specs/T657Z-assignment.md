# T657Z — Assignment

> **Spec ID:** T657Z
> **Status:** Full
> **Owner:** Assignment
> **Depends on:** [J9GBH](J9GBH-placement.md)

## Description

Assignments are the coursework backbone of the PKL period: a teacher or supervisor defines a task
(project, report, essay) scoped to an internship, publishes it to enrolled students, and closes it
when the window ends. This spec owns definition, lifecycle, and publishing only. How students submit
work is owned by [assignment-submission](T657Z-assignment-submission.md); how mentors score it and
request revision is owned by [assignment-grading](T657Z-assignment-grading.md).

---

## 1. Problem Statements

### PS-1 — Coursework Dies in Chat Threads

A supervising teacher drops a report brief into a WhatsApp group of forty students, then spends the
next three weeks re-sending it to everyone who "didn't see it", arguing about which Friday was the
real deadline, and discovering at grading time that half the class worked from an outdated draft of
the brief. Nothing records what was asked, of whom, and by when.
**→ Requirement:** FR-ASG-001 (canonical assignment record), FR-ASG-007/008 (published, notified).

### PS-2 — Drafts Leak Before They Are Ready

A teacher composing a multi-part project brief saves halfway, and students who happen to open the
page see an unfinished task with a placeholder deadline and start asking questions the teacher
cannot answer yet. Publishing must be an explicit, guarded act — never a side effect of saving.
**→ Requirement:** FR-ASG-006 (DRAFT default), FR-ASG-007 (publish guard).

### PS-3 — Finished Windows Stay Open

An internship period ends, but its assignments linger in PUBLISHED state; months later a student
stumbles onto the page and submits work nobody will ever grade, while the grade card already went
to print. Closure must be a first-class transition, distinct from deletion, so history survives
while the window shuts.
**→ Requirement:** FR-ASG-010 (close), FR-ASG-005 (delete guard).

---

## 2. Goals & Non-Goals

### Goals

- **Canonical assignment record per internship** — type, title, brief, deadline, mandatory flag, status. *Why:* one source of truth replaces the chat-thread brief.
- **Explicit draft-to-published lifecycle** — new work starts private; students see it only after publish. *Why:* half-written briefs must never leak.
- **Publish-time notification to every enrolled student** — nobody discovers the task by accident. *Why:* PS-1 shows discovery is where coursework fails first.
- **Closure distinct from deletion** — finished assignments shut their window but keep history. *Why:* grade cards and audits need the record after the period ends.
- **Ownership guard on deletion** — an assignment with submissions cannot be deleted. *Why:* deleting graded work would orphan scores students already saw.

### Non-Goals

- **Student submission mechanics**. *Why:* owned by [assignment-submission](T657Z-assignment-submission.md).
- **Scoring, feedback, and revision**. *Why:* owned by [assignment-grading](T657Z-assignment-grading.md).
- **Rubric-based grading**. *Why:* rubrics belong to the Assessment module ([ARDA6](ARDA6-assessment.md)).
- **Plagiarism detection or auto-grading**. *Why:* post-MVP operational depth; no engine is integrated.
- **Assignment templates or cloning**. *Why:* copy-across-periods is convenience, not MVP coursework flow.

---

## 3. User Stories / Use Cases

Every authoring journey in this spec ends at publish or close; grading and submission journeys live
in the sibling specs.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ASG-001 | Teacher composes an assignment as a draft, publishes it, and every enrolled student is notified | P0 | F | Full |
| UC-ASG-002 | Admin closes a finished assignment so its window shuts while its history survives | P1 | F | Full |
| UC-ASG-003 | Student discovers only published assignments for their own internship | P1 | — | — |

### 3.1 Authoring

#### UC-ASG-001 — Teacher Composes and Publishes

Late on a Sunday a mentor finishes the field-report brief for the grade-11 cohort: objectives,
structure, a Friday deadline, mandatory flag on. She saves it mid-evening and returns Monday to
tighten the wording — through all of that, students see nothing, because the record sits in DRAFT.
When she presses publish, one transaction flips the status, fans a notification out to every
student registered on that internship, and writes the audit entry; by the time her toast confirms,
the cohort's assignment list already shows the brief. Had she published too early, there is no
un-publish — the correction path is an edit to the published brief plus a follow-up announcement,
which is exactly why the publish button asks for confirmation.

#### UC-ASG-002 — Admin Closes a Finished Window

The placement period ended last week and the coordinator is tidying up: forty assignments still
show as published, and a straggler student keeps uploading reports nobody asked for anymore. From
the assignment manager she closes each finished brief — submissions already graded stay exactly as
they were, the brief stays readable for the archive, but the submit button disappears for everyone.
Deletion is refused where submissions exist, so closing becomes the routine end-of-period gesture
rather than a destructive cleanup.

### 3.2 Discovery

#### UC-ASG-003 — Student Sees Only Their Own Published Work

A student opening the assignment list mid-period sees three published briefs for their workshop
placement — and nothing from the parallel automotive cohort, nothing still in draft, nothing closed
last semester. That filtering is not a UI convenience; the underlying read path scopes by the
student's registration bridge, so a crafted request for another internship's draft brief resolves
to nothing. Verifiable behavior of this journey (scoping, guards) is decomposed into FR-ASG-011
and the submission spec's entry guards; the journey itself spans specs and stays unlayered here.

---

## 4. Functional Requirements

Authoring, lifecycle, and publishing. `Layer`/`Status` follow the project legend: `U` unit (no
DB), `F` feature (real DB), `B` browser, `A` arch. Every row below is implemented.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ASG-001 | `CreateAssignmentAction` accepts a validated DTO and creates the assignment in DRAFT inside a transaction with an audit entry | P0 | F | Full |
| FR-ASG-002 | `assignment_type` supports project, report, essay with project default; `is_mandatory` defaults false; `due_date` is nullable | P1 | F | Full |
| FR-ASG-003 | `Assignment` persists on UUID v7 PK with `#[Fillable]` whitelist, `foreignUuid` internship FK cascading on delete, and nullable document FK nulling on delete | P0 | A | Full |
| FR-ASG-004 | `UpdateAssignmentAction` applies partial updates, ignoring null fields, and re-validates the resulting record | P1 | F | Full |
| FR-ASG-005 | `DeleteAssignmentAction` refuses when submissions exist and otherwise deletes with DB-cascaded submissions | P0 | F | Full |
| FR-ASG-006 | `AssignmentStatus` defines DRAFT, PUBLISHED, CLOSED with transitions DRAFT→PUBLISHED/CLOSED, PUBLISHED→CLOSED, CLOSED→terminal | P0 | U | Full |
| FR-ASG-007 | `PublishAssignmentAction` rejects non-DRAFT records with `RejectedException` and flips DRAFT→PUBLISHED atomically | P0 | F | Full |
| FR-ASG-008 | Publishing dispatches `AssignmentPublished` and notifies every enrolled student plus the creator without blocking the request on mail delivery | P0 | F | Full |
| FR-ASG-009 | `AssignmentManager` lists assignments with title/type/internship search and status/type/mandatory filters | P1 | F | Full |
| FR-ASG-010 | Closing transitions PUBLISHED→CLOSED, shuts the submission window, and preserves all submissions and scores | P1 | F | Full |
| FR-ASG-011 | Every mutation is dual-gated: Policy at the boundary, business rule re-checked in Action/Entity via `RejectedException` | P0 | A | Full |
| FR-ASG-012 | All input is validated server-side through Form Requests at HTTP and DTOs at the Action boundary | P0 | A | Full |
| FR-ASG-013 | Every user-facing string passes through `__()` with mirrored `en`/`id` keys | P0 | A | Full |
| FR-ASG-014 | Mutations write dual-channel SmartLogger entries with PII masking; business violations surface as translatable `RejectedException` messages | P0 | F | Full |

### 4.1 Creation & Persistence

#### FR-ASG-001 — Creation Lands in Draft

The runtime walk starts in the Livewire manager: the form validates locally, maps to
`CreateAssignmentData`, and calls `CreateAssignmentAction::execute()`. Inside, the Action opens a
transaction, persists the row with DRAFT status regardless of what the caller suggested, writes the
activity entry, and commits — or rolls everything back on failure, so a notification never fires
for an assignment that was never stored. Because the DTO is the only input shape, a future field
(say, a weight for grade aggregation) extends the DTO and its validation in one place instead of
rippling through every caller.

#### FR-ASG-002 — Types and Defaults

Three brief shapes survived the pilot — build something, write something, argue something — and
everything else turned out to be one of those with different wording, so the type column stays a
three-case enum with project as the default rather than a free-text field that would fracture
filtering. The mandatory flag defaults to off because most coursework is formative; flipping it on
is the explicit statement that a missing submission blocks completion. A null due date means the
window stays open until closure, which the submission spec's late policy treats as never-overdue
rather than immediately-overdue.

#### FR-ASG-003 — Persistence Contract

UUID v7 keys keep assignment URLs unguessable — a student cannot decrement a UUID to find another
cohort's draft brief — while preserving insertion locality during bulk setup at period start. The
internship foreign key cascades because an assignment without its internship is meaningless; the
document link nulls instead, since a template file may be retired while briefs that referenced it
must keep their text. Composite indexes on `(internship_id, status)` exist because the two
hottest queries — "my internship's published briefs" and the manager's filtered list — both lead
with exactly those columns.

#### FR-ASG-004 — Partial Updates Ignore Nulls

An edit form that only touched the deadline must not blank the description, so null fields are
filtered before fill rather than written through — the classic patch-with-PUT bug that once wiped a
cohort's briefs during a rushed deadline extension. The re-validation after filtering matters
because dropping nulls can expose a newly inconsistent combination (a mandatory flag with the
description removed still passes, but an emptied title must fail). Whatever survives the filter is
what gets audited, so the activity diff shows the teacher's actual intent instead of a wall of
null overwrites.

#### FR-ASG-005 — Deletion Refuses When Work Exists

Nobody deletes an assignment the way they delete a chat message: the Action first asks whether any
submission rows reference it, and if so throws `RejectedException` with a message explaining that
closure is the correct gesture. Only a childless record proceeds, and then the database cascade —
not application code — removes the dependent rows, so a crash mid-delete cannot leave orphaned
submissions pointing at a vanished brief. The policy mirrors the guard at the boundary, which
means the delete button never even renders for unauthorized roles or referenced records.

### 4.2 Lifecycle & Publishing

#### FR-ASG-006 — Three States, No Shortcuts

DRAFT means invisible to students, PUBLISHED means submittable, CLOSED means readable but shut —
and the only legal moves are forward, because every backward move the team ever allowed ("just
unpublish it for a minute") produced students submitting against two different versions of the
brief. The terminal state is genuinely terminal: CLOSED accepts no transition at all, so the only
way to "reopen" is a deliberate new assignment, which keeps the audit story honest about what was
asked when. Transition checks live in the status enum and the Entity together, giving unit tests a
database-free surface for the whole matrix.

#### FR-ASG-007 — Publish Is Guarded and Atomic

Publishing an already-published brief is the double-click bug: the teacher hits the button twice,
the second call finds PUBLISHED instead of DRAFT, and `RejectedException` turns the duplicate into
a calm "already published" message rather than a second fan-out of notifications. The flip itself
happens inside the same transaction as the event dispatch staging, so observers never see a
PUBLISHED row whose notifications failed to queue. Direct Action calls get no softer treatment
than the UI — the guard runs before any authorization shortcut, which is what makes the
double-submit safe from any entry point.

#### FR-ASG-008 — Students Hear About It Immediately

Two audiences need the news for different reasons: enrolled students must learn their workload
changed, and the creator needs confirmation the fan-out happened. Students are notified inline
from the publish path while the creator's confirmation rides the `AssignmentPublished` event to a
queued listener — the split exists because a slow mail queue must never hold the publish request
open, yet the creator's receipt must never be lost if the request process dies right after commit.
Both notifications implement `ShouldQueue`, so at 6pm when thirty mentors publish at once, the
web workers stay responsive while the queue drains the fan-out.

#### FR-ASG-009 — The Manager Finds Anything

Three hundred briefs across a dozen internships is the steady state at a large school, and
scrolling is not a retrieval strategy. The manager searches across title, type, and internship
name with a single query string, then narrows by status, type, and mandatory flag — the four
filters coordinators actually asked for during the pilot, nothing more. Counts come from the same
scoped query rather than a second unfiltered one, so the "12 published" badge never disagrees with
the twelve rows on screen.

#### FR-ASG-010 — Closure Shuts the Window, Keeps the Record

Closing is the quiet counterpart to publishing: status moves to CLOSED, the submission guards in
the sibling spec start rejecting new work against it, and everything already submitted — content,
files, scores, feedback — stays exactly where it was for grade cards and accreditation folders.
Unlike deletion it needs no emptiness check, because closure destroys nothing. Coordinators run it
as batch end-of-period hygiene, which is only safe because the operation is idempotent: closing a
CLOSED brief is a no-op success, not an error.

### 4.3 Cross-Cutting Contracts

#### FR-ASG-011 — Two Gates on Every Mutation

A policy answers "may this role touch assignments at all" while the Action answers "may this
specific change happen to this specific record" — and the second check is load-bearing, because
policies see the request while only the Action sees the fully-loaded record inside a transaction.
Create, update, publish, and delete each re-validate ownership and state after the policy passes,
throwing `RejectedException` on violation. The one pairing the pilot got wrong was publish: the
policy allowed it, but nothing re-checked DRAFT inside the transaction until the double-click
incident forced the second gate in.

#### FR-ASG-012 — Validation at Both Doors

HTTP requests validate through Form Request classes so malformed payloads die before touching
domain code; Actions validate DTOs so direct callers, console commands, and tests face the same
wall. `$request->all()` never reaches `create()` or `update()` — the mass-assignment attempt that
once smuggled `status=PUBLISHED` through a crafted form is structurally impossible now, because
status is not fillable-from-request in any path. Shared rules live toward the Entity so the form
and the Action cannot drift into disagreeing about what a valid brief looks like.

#### FR-ASG-013 — Bilingual From the First String

Every label, toast, notification subject, and validation message in the assignment flow resolves
through `__()`, with Indonesian primary and English secondary. The discipline bites hardest in
notifications: student names and brief titles travel as placeholders, never concatenated into the
message, so translators reorder sentences without breaking interpolation. A missing key in either
locale file fails the D3 scan, which caught three untranslated publish confirmations before the
first bilingual pilot school ever saw them.

#### FR-ASG-014 — Audited, Masked, Explainable Failures

If a teacher's brief edit ever becomes a dispute — "I never changed the deadline" — the activity
channel holds actor, timestamp, and before/after diff, queryable without grepping log files. Every
payload passes through PII masking before reaching either sink, because a careless full-model log
once wrote student emails into plaintext files. Business failures speak `RejectedException` with a
translatable, user-safe message rendered as a toast; unexpected failures log full context to the
system channel and show the user a generic notice — students never see stack traces, operators
never lose them.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ASG-001 | All PHP files declare `strict_types=1` | N/A | P0 | A | Full |
| NFR-ASG-002 | Every translation key exists in both `lang/en/` and `lang/id/` | N/A | P0 | A | Full |
| NFR-ASG-003 | Publish fan-out never blocks the request on mail delivery; all notifications queued | N/A | P1 | F | Full |
| NFR-ASG-004 | Due dates render in the viewer's local timezone | N/A | P2 | B | Full |
| NFR-ASG-005 | Manager listing is paginated and eager-loads internship and submission counts | N/A | P1 | F | Full |
| NFR-ASG-006 | No `{!! !!}` on user-supplied brief content; all dynamic output escaped | N/A | P0 | A | Full |

### 5.1 Conventions

#### NFR-ASG-001 — Strict Types Everywhere

A silent string-to-int coercion once turned a mandatory flag into a truthy surprise during a CSV
import dry-run, and the resulting afternoon of debugging is why the strict-types declaration is a
scanned invariant rather than a style suggestion. The check runs in the pre-commit batch, so a
missing declaration fails before review rather than during it. Migrations and config files are the
only exempt paths, and the assignment flow contains neither.

#### NFR-ASG-002 — Mirrored Locales

A morale problem disguised as a technical one: the Indonesian pilot school hit English validation
messages on the publish form and concluded the feature was unfinished. Mirrored keys in both
locale files are therefore verified by scan, not by translators remembering. When a new string
lands with only one locale, the build tells the author immediately — which is kinder than letting
a teacher discover it in front of a class.

### 5.2 Delivery & Scale

#### NFR-ASG-003 — The Publish Request Stays Fast

Thirty mentors publishing on the same Sunday evening must not serialize behind a mail server:
publish commits the state change, queues every notification, and returns, so request latency never
depends on SMTP. The failure mode this prevents is the timeout-double-publish — a slow request
that tempts a second click, which FR-ASG-007 then has to absorb. Queue depth during peak is
observable through the standard queue tooling; no assignment-specific dashboard was needed.

#### NFR-ASG-004 — Deadlines Respect the Reader's Clock

A school in Jayapura and a partner workshop in Jakarta share a server but not a clock, and "due
Friday 23:59" is meaningless until it names whose Friday. Due dates persist in UTC and render in
the viewer's timezone, so the student and the mentor argue about the work instead of the hour.
The browser journey that proves it — same brief, two timezones, two correct renderings — is the
only B-layer row in this spec, because everything else asserts cleanly below the UI.

#### NFR-ASG-005 — Lists Stay Bounded

The manager query paginates at a fixed page size with internship and submission counts eager-loaded
in the same round trip; without that, period-start review week turns the listing into a thousand
lazy queries and a very long coffee break. Page size is a constant, not a user preference, because
letting coordinators request "all 400" reintroduces the exact unbounded query the pagination was
built to kill. Search and filters compose inside the paginated query so counts and rows always
agree.

#### NFR-ASG-006 — Brief Content Is Never Raw HTML

Brief bodies are teacher-supplied rich text adjacent to student sessions, which makes them the
single most attractive stored-XSS surface in the module. All dynamic output renders escaped; raw
HTML markers are forbidden on any user-generated content by scan. The one deliberate exception
proves the boundary: sanitized, allow-listed formatting in brief descriptions passes through a
named sanitizer with an inline justification, and everything else takes the escaped path.

---

## 6. API / Data Contracts

### 6.1 Assignment Model

```
App\Modules\Assignment\Models\Assignment extends BaseModel (HasUuids, UUID v7 PK)
  Table: assignments
  Fillable: internship_id, document_id, assignment_type, title, description,
            is_mandatory, due_date, status, created_by
  Casts: due_date → datetime, is_mandatory → boolean, status → AssignmentStatus
  Relations: internship() BelongsTo Internship, submissions() HasMany Submission,
             creator() BelongsTo User, document() BelongsTo Document
  Bridge: asAssignmentRules() → AssignmentRules
  Factory: AssignmentFactory
```

### 6.2 AssignmentRules Entity

```
App\Modules\Assignment\Entities\AssignmentRules extends BaseEntity (final readonly)
  Constructor: (bool $isMandatory, ?Carbon $dueDate)
  Factory: fromModel(Model): static
  Methods: isMandatory(): bool, isOverdue(Carbon $now): bool
```

### 6.3 AssignmentStatus Enum

```
App\Modules\Assignment\Enums\AssignmentStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: DRAFT='draft', PUBLISHED='published', CLOSED='closed'
  Transitions: DRAFT→[PUBLISHED, CLOSED], PUBLISHED→[CLOSED], CLOSED→[]
```

### 6.4 Actions & Data

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateAssignmentAction` | `BaseCommandAction` | `CreateAssignmentData` | `Assignment` |
| `UpdateAssignmentAction` | `BaseCommandAction` | `Assignment, UpdateAssignmentData` | `Assignment` |
| `DeleteAssignmentAction` | `BaseCommandAction` | `Assignment` | `void` |
| `PublishAssignmentAction` | `BaseCommandAction` | `Assignment` | `Assignment` |

`CreateAssignmentData` / `UpdateAssignmentData` extend `BaseData` (readonly): `assignmentType`,
`internshipId`, `title`, `?description`, `isMandatory`, `?dueDate`. HTTP validates via
`CreateAssignmentRequest`.

### 6.5 Events, Listeners, Notifications

| Class | Role |
| ----- | ---- |
| `AssignmentPublished` | Dispatched by `PublishAssignmentAction` |
| `NotifyOnAssignmentPublished` | Queued listener; notifies the creator |
| `AssignmentNotification` | To enrolled students on publish; mail, broadcast, database; `ShouldQueue` |

### 6.6 Policies & Routes

`AssignmentPolicy`: viewAny/view all roles; create/update/publish admin, teacher;
delete admin AND zero submissions. Routes: `GET /admin/assignments` → `AssignmentManager`
(`auth`, `role:super_admin|admin`).

### 6.7 Database Schema

```
assignments:
  id: uuid PK (v7)
  internship_id: foreignUuid → internships.id (cascadeOnDelete)
  document_id: foreignUuid → documents.id (nullOnDelete, nullable, indexed)
  assignment_type: string (default 'project')
  title: string
  description: text (nullable)
  is_mandatory: boolean (default false)
  due_date: dateTime (nullable)
  status: string(20) (default 'draft')
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps
  Indexes: (internship_id, status), document_id
```

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ASG-001 | New assignments start in DRAFT; publishing is always explicit | P0 | — | — |
| DD-ASG-002 | Publish notifies students inline and the creator via event listener | P0 | — | — |
| DD-ASG-003 | Closure and deletion are separate operations with different guarantees | P1 | — | — |

### 7.1 Lifecycle Shape

#### DD-ASG-001 — Draft by Default

The alternative the team rejected was "visible immediately, hide if needed" — faster for the
one-brief demo, catastrophic for the forty-brief term, where every half-saved edit would have
pinged students. Defaulting to DRAFT makes the safe state the accidental state: an interrupted
session, a forgotten tab, a browser crash all leave invisible work, never a premature
announcement. Publish confirmations and the no-unpublish rule then make the dangerous state
deliberate, which is the correct polarity for a broadcast action.

#### DD-ASG-002 — Two Paths Out of Publish

One evening during load testing the team watched a publish request hang on SMTP while thirty
students waited for a page that had already changed underneath them. That night split the fan-out:
students, whose need is immediate, are notified from the publish path itself; the creator's
confirmation rides the queued event listener, where a mail outage delays a receipt instead of
blocking a state change. Keeping both (rather than moving everything to events) preserves the
ordering guarantee students rely on — the brief is always committed before its announcement
queues.

#### DD-ASG-003 — Close Versus Delete

Deletion answers "this should never have existed" and destroys; closure answers "this is over"
and preserves. The distinction earned its place after a coordinator deleted a finished brief to
"clean up" and took sixty graded submissions with it — recoverable from backup, but the scare
rewrote the policy. Now deletion is refused wherever submissions exist, closure is idempotent and
always safe, and the archive keeps every brief its grade card ever referenced.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Briefs published without student notification | 0 | Every publish path dispatches event + inline notify |
| Students discovering draft/foreign briefs | 0 | Registration-scoped reads; policy review |
| Invalid status transitions observed | 0 | Enum + Entity transition matrix; exception log |
| Assignments deleted with submissions attached | 0 | Delete guard + cascade constraint |
| Untranslated strings in assignment flow | 0 | D3 locale scan |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [J9GBH](J9GBH-placement.md) | Active placement records — assignments scope to internships |

### Build Guide

With this spec, mentors author, publish, and close coursework briefs with notified students. The
submission window mechanics and scoring loop arrive via the siblings below.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment-submission](T657Z-assignment-submission.md) | Students submit work against published briefs |
| 2 | [assignment-grading](T657Z-assignment-grading.md) | Mentors score submissions and request revision |
| 3 | [document-templates](PKYX6-document-templates.md) | Grades feed report cards generated from templates |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume one attachment-adjacent document link (`document_id`) per brief suffices for MVP; multi-attachment briefs are post-MVP | Accepted | Maintainer | — |

## Quick References

- [Assignment submission](T657Z-assignment-submission.md) — student submission lifecycle, late policy, uploads
- [Assignment grading](T657Z-assignment-grading.md) — scoring, revision loop, mentor scope
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — the internship periods briefs scope to
- [Placement](J9GBH-placement.md) — registration bridge submissions and scoping rely on
- [Assessment](ARDA6-assessment.md) — rubric grading, outside assignment scope
- [Notification infrastructure](TXR2H-notification-infrastructure.md) — channels the publish fan-out uses
- [File uploads & media](WQGTP-file-uploads-media.md) — upload validation contract submissions reuse
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — dual-layer authorization, cross-role proxy
