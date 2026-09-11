# ARDA6 — Assessment

> **Spec ID:** ARDA6
> **Status:** Full
> **Owner:** Assessment
> **Depends on:** J9GBH

## Description

Assessment is the quantitative scoring backbone of the PKL lifecycle. Coordinators define rubric
templates with nested competencies and indicators, authorized mentors score students against
those indicators, the system folds in sub-scores computed from attendance, logbook, supervision,
and submission data, and a weighted final score is frozen at finalization. Students see their
own finalized breakdowns read-only. The grade card in [R6BMW](R6BMW-reports.md) and the
certificate decision in [J0M04](J0M04-certification.md) both consume the frozen score this spec
produces.

---

## 1. Problem Statements

### PS-1 — Configurable Evaluation Framework Without Schema Drift

Different PKL programs need different competency frameworks — three competencies here, ten
there, each with its own indicator weights. Hardcoding those structures into migrations means
every program change becomes a schema change.
**→ Requirement:** FR-ASM-001 (JSON rubric contract), FR-ASM-004 (Action-layer validation).

### PS-2 — Multi-Evaluator Scoring With Role-Based Authorization

One assessment is scored by several evaluators, each entitled to only some competencies. A
supervisor scoring outside their authority quietly corrupts the final grade.
**→ Requirement:** FR-ASM-008 (role + mentorship scoping), FR-ASM-009 (proxy path),
FR-ASM-022 (dual-layer authorization).

### PS-3 — Automated Score Aggregation From Cross-Module Data

Hand-copying attendance rates and logbook completeness into score sheets is slow and
error-prone. The raw evidence already lives in Journals, Assignment, and Supervision.
**→ Requirement:** FR-ASM-013 (auto-aggregation), FR-ASM-014 (auto namespace discipline).

### PS-4 — Weighted Final Score Calculation With Normalization

Indicators carry different maximums and different weights, so raw points cannot simply be
added. A missing supervisor evaluation must not silently zero out a student.
**→ Requirement:** FR-ASM-016 (normalization + redistribution invariant).

### PS-5 — Assessment Immutability After Finalization

A finalized grade feeds certificates and report cards. A late silent edit after the grade has
been communicated destroys trust in the whole record.
**→ Requirement:** FR-ASM-015 (finalization guards), FR-ASM-018 (freeze enforcement points).

### PS-6 — Student-Facing Assessment Transparency

Students who cannot see their own breakdowns queue at the teacher's desk for every number.
A read-only window into finalized scores removes that friction without risking the record.
**→ Requirement:** FR-ASM-021 (student read-only view).

---

## 2. Goals & Non-Goals

### Goals

- **Rubric templates with nested competencies and indicators stored as JSON** — programs define their own frameworks without migrations. *Why:* PS-1; schema drift is the failure this module exists to prevent.
- **Scoring restricted to authorized evaluators within their competencies** — role plus mentorship assignment gates every score write. *Why:* PS-2; a grade is only as trustworthy as its least-authorized scorer.
- **Sub-scores auto-computed from cross-module evidence** — attendance, logbooks, submissions, supervision logs, and monitoring visits flow in automatically. *Why:* PS-3; manual transcription is where arithmetic errors enter.
- **Weighted final score with normalization and redistribution** — every indicator normalized to 0–100, weights applied at both levels, missing supervisor weight redistributed. *Why:* PS-4; raw points are not comparable across indicators.
- **Frozen record after finalization** — no score path works once finalized. *Why:* PS-5; certificates and grade cards must rest on an immutable number.
- **Read-only student view of finalized assessments** — breakdowns visible, nothing editable. *Why:* PS-6; transparency without write access.
- **Audited finalization with masked PII** — every finalize action logged through the dual-channel logger. *Why:* a grade change is exactly the event an audit trail must capture.

### Non-Goals

- **Rubric versioning or historical snapshots**. *Why:* rubric edits apply to future assessments; versioned templates are post-MVP depth.
- **Automated remediation workflows on low scores**. *Why:* follow-up study plans are a separate product decision, not MVP scoring.
- **Real-time collaborative scoring**. *Why:* evaluators score independently; presence and conflict resolution add no MVP value.
- **Student self-assessment or peer assessment matrices**. *Why:* MVP scoring is mentor-authored; self/peer instruments change the authorization model.
- **Grade-appeal or dispute workflows beyond core finalization**. *Why:* appeals are a post-MVP process layer on top of the frozen record.

---

## 3. User Stories / Use Cases

Every flow below is exercised by the grading and rubric Livewire journeys, so each row carries
a test layer.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ASM-001 | Coordinator builds a rubric template with competencies and indicators, then activates it for a program | P0 | F | Full |
| UC-ASM-002 | Mentor scores an indicator inside their authorized competencies and the score persists immediately | P0 | F | Full |
| UC-ASM-003 | Coordinator auto-imports sub-scores computed from attendance, logbook, submission, and supervision data | P0 | F | Full |
| UC-ASM-004 | Coordinator finalizes an assessment, freezing the weighted score and emitting the audit trail | P0 | F | Full |
| UC-ASM-005 | Student opens their finalized assessment breakdown in a read-only view | P1 | B | Full |

### 3.1 Authoring & Scoring

#### UC-ASM-001 — Coordinator builds a rubric template

A coordinator at a school opening a new automotive program needs six competencies the default
template never had — engine overhaul weighted double, workplace safety mandatory. She opens
the rubric manager, creates the template, nests competencies with evaluator roles, hangs
indicators with maximums and weights under each, and flips it active. From that moment every
new assessment for the program links this template, and the old template keeps serving the
mechanical program untouched. The shape of the evening matters: no developer, no migration,
no deploy — curriculum change stays a coordinator's ten-minute task.

#### UC-ASM-002 — Mentor scores an indicator inside their authority

The teacher opens the grading screen for a placed student and sees two visual groups: the
academic competencies they may score, editable, and the industry competencies greyed out as
read-only. They type a score into an indicator, and before their finger leaves the key the
value is already persisted — no save button, no lost work when the bell rings mid-session.
Behind that keystroke the Action re-checks role and mentorship, so a crafted request from
another student's page dies with a rejection instead of landing in the wrong record.

### 3.2 Aggregation, Finalization & Transparency

#### UC-ASM-003 — Coordinator auto-imports cross-module sub-scores

Two days before grades are due, the coordinator faces forty students times five evidence
sources — nobody can hand-copy that without transposition errors. One click runs the
aggregation: submission averages from Assignment, logbook completeness and attendance rates
from Journals, supervision review ratios and visit verification from Supervision. The numbers
land in a separate namespace inside the assessment, visible as reference, never overwriting
the mentor's manual judgment. The coordinator scans the imported column, spots the student
whose attendance rate collapsed in week nine, and scores accordingly — informed, not replaced.

#### UC-ASM-004 — Coordinator finalizes an assessment

Finalization day has a ritual quality: the coordinator reviews the scored competencies, opens
the confirmation dialog, and commits. Inside the transaction the system normalizes every
indicator to a 0–100 scale, folds in indicator weights, folds in competency weights with
redistribution for the unscored industry block, writes the score with timestamp and
evaluator, fires the finalized event, and logs the whole thing with personal data masked.
Afterward the grading screen for that student goes quiet — every score input locked, every
mutation path rejected. The grade that leaves this room is the grade the certificate prints.

#### UC-ASM-005 — Student opens their finalized breakdown

A student who heard rumors about failing the industry block opens the assessments page from
their phone. Only finalized records appear, only their own registrations, each with
competency rows, indicator scores, and the final number. Nothing is clickable into an edit —
there is no edit. The argument that would have consumed the teacher's lunch break never
happens, because the breakdown answers it: here is what was scored, by which role, and how
it weighted into the total.

---

## 4. Functional Requirements

Common contracts apply project-wide: every Action extends its triad base with a single
`execute()`, models carry UUID v7 keys, and authorization is always dual-layer. Rows below
are the assessment-specific behaviors.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ASM-001 | Rubric templates support full lifecycle (create, update, delete, activate) with competencies and indicators stored as a nested JSON structure | P0 | F | Full |
| FR-ASM-002 | Competencies support full lifecycle inside a rubric, keyed by UUID, carrying name, description, weight, evaluator role, and order | P0 | F | Full |
| FR-ASM-003 | Indicators support full lifecycle inside a competency, keyed by UUID, carrying name, description, maximum score, weight, and order | P0 | F | Full |
| FR-ASM-004 | Rubric structure validation runs in the Action layer, rejecting unknown competency or indicator references on every write | P0 | F | Full |
| FR-ASM-005 | Rubric model exposes the persistence contract: UUID key, fillable attributes, array-cast structure, and relations to program, creator, and assessments | P0 | A | Full |
| FR-ASM-006 | Opening the grading screen initializes exactly one assessment per registration, linked to the first active rubric when none exists | P0 | F | Full |
| FR-ASM-007 | Scoring an indicator validates the competency and indicator exist in the rubric and the value falls within zero and the indicator maximum | P0 | F | Full |
| FR-ASM-008 | Scoring authorization combines role and mentorship: admins pass, teachers and supervisors must match the competency evaluator role and mentor the registration via MentorEntity | P0 | F | Full |
| FR-ASM-009 | A teacher mentorscoring a supervisor-role competency is recorded as a cross-role proxy with proxy metadata in the audit trail | P0 | F | Full |
| FR-ASM-010 | The grading UI separates evaluable from read-only competencies per viewer and persists score keystrokes immediately without a save control | P1 | F | Full |
| FR-ASM-011 | Assessment model exposes the persistence contract: UUID key, fillable attributes, array-cast scores payload, float score, datetime finalization, and relations to registration, rubric, and evaluator | P0 | A | Full |
| FR-ASM-012 | Assessment type supports midterm, final, periodic, and industry variants with one record per registration, type, and evaluator | P0 | F | Full |
| FR-ASM-013 | Auto-aggregation computes sub-scores from submission averages, logbook completeness, attendance rate, supervision review ratio, visit verification, and approved report scores | P0 | F | Full |
| FR-ASM-014 | Auto-aggregation refuses finalized assessments and stores every computed value under the auto namespace, never touching manual competency scores | P0 | F | Full |
| FR-ASM-015 | Finalization is guarded: already-finalized records, missing rubrics, and fully-unscored assessments are rejected before any math runs | P0 | F | Full |
| FR-ASM-016 | Finalization normalizes each indicator to a 0–100 scale, applies indicator weights, then competency weights, redistributing unscored supervisor weight proportionally across scored competencies | P0 | U | Full |
| FR-ASM-017 | Finalization writes the score, timestamp, and evaluator identity, dispatches the finalized event, and audit-logs with PII masked | P0 | F | Full |
| FR-ASM-018 | Finalized assessments reject every score mutation at each enforcement point: scoring actions, auto-aggregation, rubric relinking, and deletion of scored structure | P0 | F | Full |
| FR-ASM-019 | Business-rule failures surface as RejectedException carrying a translatable user-facing message while unexpected failures log with context and render generically | P0 | A | Full |
| FR-ASM-020 | Every user-facing string in grading, rubric, and student views passes through the translation helper with mirrored Indonesian and English keys | P0 | A | Full |
| FR-ASM-021 | Students see only finalized assessments for their own registrations in a read-only view with eager-loaded rubric, competency, and program data | P0 | F | Full |
| FR-ASM-022 | Assessment authorization is dual-layer: policies gate routes and components while actions and entities re-validate the business rule independently | P0 | A | Full |

### 4.1 Rubric Templates

#### FR-ASM-001 — Rubric template lifecycle in JSON

The semester before, a curriculum change meant a developer editing migrations; now it means a
coordinator editing a form. Creating a template takes a name, an optional description, and an
active flag — the nested competency tree arrives through the competency and indicator
actions, never as one giant payload. Deleting a template that live assessments reference is
refused rather than cascaded into orphaned scores, because a rubric is a promise other
records depend on. Deactivation, not destruction, is how a template retires.

#### FR-ASM-002 — Competency lifecycle with UUID keys

Walk through what adding "Workplace Safety" actually does: the action appends a UUID-keyed
node holding the name, description, integer weight, evaluator role, and display order into
the rubric's competency map. Updates rewrite that node in place; deletes filter it out along
with its indicators. UUID keys matter more than they look — positional indexes would shift
every stored score reference the moment a coordinator reorders two competencies, while
stable keys survive reordering, renaming, and everything short of deletion.

#### FR-ASM-003 — Indicator lifecycle with UUID keys

The disputed grade at SMK DUDI last period came down to one indicator: "Tool maintenance",
maximum 20, weight 3, scored 14. That sentence is only expressible because indicators carry
exactly those five fields — name, description, maximum, weight, order — under a stable key.
When the coordinator later raises the maximum to 25, old raw scores keep their meaning
because finalization normalizes against the maximum stored at write time, not display time.
Indicators are small, but they are the unit the entire weighting math stands on.

#### FR-ASM-004 — Action-layer structure validation

A score write arrives carrying a competency id and an indicator id. Trusting those ids
because the UI rendered them is how a forged request scores a deleted competency back into
existence. So the Action resolves both ids against the live rubric structure first, and
unknown references die there — before authorization, before persistence, before any event.
The UI also validates, but the UI is a courtesy; this row is the guarantee. Corrupted JSON
can never produce a scored phantom.

#### FR-ASM-005 — Rubric persistence contract

Some rows exist so that fifty future files agree without discussion. The rubric model takes
UUID v7 keys, five fillable attributes, an array-cast structure, a boolean active flag, and
three relations — program, creator, assessments. Anything reaching past that contract, a
query in a Livewire component or a fill outside the list, is a defect by definition rather
than by taste. Conventions stay conventions only when a row like this pins them down.

### 4.2 Scoring

#### FR-ASM-006 — One assessment per registration

The first time anyone opens the grading screen for a placement, there may be no assessment
row yet — and two evaluators opening it simultaneously must not create twins. The
initializer finds or creates exactly one record, links the first active rubric when the
registration has none, and returns the pair. From then on every score, import, and finalize
has a single home. Idempotency here is what keeps the uniqueness constraint in FR-ASM-012
from ever firing in anger.

#### FR-ASM-007 — Indicator score validation

Fourteen out of twenty is a score; twenty-four out of twenty is a typo or an attack, and
the system treats both identically: rejected. The action confirms the competency and
indicator exist in the current rubric structure, then checks the value against zero and the
indicator maximum. Boundary values pass — zero is a legitimate score, the maximum is the
best day of the internship. Anything outside is refused with a message the teacher can read,
not a stack trace they cannot.

#### FR-ASM-008 — Role plus mentorship scoping via MentorEntity

Early in the pilot, a supervisor from PT Maju Jaya opened the grading URL for a student
placed at a different company and found every competency editable — role matched, nothing
else checked. That incident is why authorization has two halves: the evaluator's role must
match the competency's evaluator role, and the evaluator must mentor this specific
registration, resolved through the registration's mentor bridge. Admins pass both halves by
definition; everyone else proves both. Role alone answers "are you a supervisor", mentorship
answers "are you *their* supervisor", and only the pair authorizes a score.

#### FR-ASM-009 — Proxy scoring with audit metadata

Industry supervisors go unreachable — site shutdowns, harvest season, a phone lost in a
workshop. The student's grade cannot wait for them. A teacher assigned to that registration
may score the supervisor-role competencies, but the record must say so: the audit entry
stores who acted and in whose stead, with the reason. This is deliberately not a second
role on the teacher — roles stay single, the proxy is a runtime permission with a paper
trail. An auditor reading the log a year later sees exactly which industry scores were
mentor-authored and which came from the company floor.

#### FR-ASM-010 — Live persistence with evaluable split

Picture the grading session: forty indicators, a noisy workshop office, a browser tab that
might close at any moment. Each keystroke persists through the score action immediately, so
there is nothing to lose — and each competency renders editable or read-only based on the
viewer's own authority, visually distinct. The split is computed server-side per viewer, not
hidden with CSS, because a hidden field is a dare and a server-side split is a wall. Speed
for the teacher, safety for the record.

#### FR-ASM-011 — Assessment persistence contract

Registration, rubric, evaluator, type, score, scores payload, feedback, finalization
timestamp — eight fillables, no more. The scores payload casts to array with its two
namespaces, manual competencies and auto imports, kept structurally apart. Relations point
at registration, rubric, and evaluator; the entity bridge exposes the immutable snapshot
for rule evaluation. Like its rubric sibling, this row is the agreement every query, test,
and migration honors.

#### FR-ASM-012 — Assessment types with uniqueness

Midterm, final, periodic, industry — four types, one record per registration, type, and
evaluator triple. The triple exists because a teacher and a supervisor each hold their own
final assessment for the same student without colliding. The database enforces it with a
unique constraint, so even a race between two initializers resolves into one winner and one
finder. Defaults lean final, since that is the variant certificates read.

### 4.3 Auto-Aggregation

#### FR-ASM-013 — Sub-scores from cross-module evidence

The aggregation reads six evidence streams and reduces each to one number: average verified
submission score, logbook submitted-over-total, attendance present-plus-late over total,
supervision reviewed-plus-acknowledged over total, visits verified over total, and the
approved report score. Each formula is a plain ratio or average — deliberately boring,
because a coordinator disputing a number must be able to recompute it on paper. Cross-module
reads go through the owning modules' query surfaces, never raw reach-ins, so a schema
change in Journals breaks one adapter instead of this whole action.

#### FR-ASM-014 — Finalized refusal and namespace discipline

Two invariants share this row because they guard the same boundary from opposite sides. A
finalized assessment refuses aggregation outright — frozen means frozen, even for helpful
imports. And everything computed lands under the auto namespace, structurally separated
from hand-scored competencies, so no import can ever overwrite a mentor's judgment by key
collision. Reference data stays reference data; the day an import could silently rewrite a
score is the day the audit trail becomes fiction.

### 4.4 Finalization

#### FR-ASM-015 — Guards before math

Finalizing twice, finalizing without a rubric, finalizing nothing scored — three requests
that must all fail, and fail before a single ratio is computed. The order is load-bearing:
check finalized first so a double-click cannot double-write, check the rubric link so math
never runs against a deleted template, check for at least one scored competency so an empty
assessment cannot produce a zero that looks like a verdict. Each guard throws the business
rejection with a message naming the actual problem. Partial finalization does not exist.

#### FR-ASM-016 — Normalization, weights, and redistribution

This is the row the whole module justifies itself with, and it lives in the entity so it
runs identically in tests, actions, and any future importer — no database required. Each raw
score divides by its indicator maximum and scales to 0–100, indicator weights blend those
into competency scores, competency weights blend those into the total. When the supervisor
block went unscored — the unreachable-company case — its weight spreads proportionally over
the scored competencies instead of dragging the total toward zero. Same inputs, same
output, every run: determinism is stated outright because a grade that wobbles between
computations is indefensible in front of a parent.

#### FR-ASM-017 — Writing the frozen record and its trail

The commit writes three fields — score, timestamp, evaluator — and then speaks twice: once
to the domain as the finalized event, once to the audit log through the dual-channel
logger with personal data masked. The event lets certificates and grade cards react without
coupling; the activity entry lets an auditor reconstruct who finalized whom and when. Both
fire inside the finalization transaction's aftermath, so a rolled-back finalize leaves
neither a phantom event nor a lying log line.

#### FR-ASM-018 — Freeze enforcement at every point

A freeze with one unlocked door is decoration. So the check repeats everywhere a mutation
could enter: scoring actions refuse, auto-aggregation refuses, relinking to another rubric
refuses, and deleting scored structure out from under the record refuses. Each enforcement
reads the entity's finalized predicate rather than reimplementing the null check, which
means a future fifth mutation path inherits the freeze by calling the same predicate. The
SMK DUDI incident — a post-certificate "correction" that changed a printed grade — is the
exact shape this row makes impossible.

### 4.5 Cross-Cutting

#### FR-ASM-019 — Rejection contract for business failures

When a teacher hits a guard — finalized record, wrong competency, out-of-range value — what
they see is a plain sentence in their own language, produced by the business rejection
carrying a translatable message. When something genuinely unexpected breaks, that is a
different path: full context into the system log, generic wording to the screen, no stack
trace leaking internals. The two trees never mix in one catch, so a friendly "already
finalized" can never be swallowed by an error handler nor an infrastructure failure
misrendered as a rule verdict.

#### FR-ASM-020 — Translated strings in both locales

Every label on the grading screen, every validation message, every student-view heading
resolves through the translation helper — no hardcoded Indonesian in Blade, no hardcoded
English in components. Each key exists in both locale files, enforced by scan, because a
missing key renders as an identifier and tells the student the system is unfinished.
Dynamic values like names travel as placeholders, never concatenated, so translators see
whole sentences instead of fragments.

#### FR-ASM-021 — Student read-only view

Students arrive here with the least privilege and the most anxiety, so the query is narrow
by construction: finalized records only, own registrations only, rubric and program data
eager-loaded to avoid the N+1 that forty classmates opening results simultaneously would
otherwise cause. There is no score input on the page, no action route reachable — the
policy denies, and the absence of any write path makes the denial almost theoretical.
Transparency delivered, record untouched.

#### FR-ASM-022 — Dual-layer authorization

Route middleware turns away the wrong role at the door with a forbidden response; the
action and entity re-validate the business rule at the desk with a rejection. Either layer
alone would leave a hole — middleware cannot know mentorship, entities cannot see HTTP —
so both enforce, independently. Calling a scoring action directly, bypassing every route,
still meets the full mentorship check. Defense in depth is not a slogan here; it is the
reason the forged-request incident stayed an incident instead of becoming a breach.

---

## 5. Non-Functional Requirements

Constraints on how the behaviors above must hold. `N/A` marks requirements enforced by
structure and scans rather than runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ASM-001 | Finalization and multi-write scoring paths execute inside a database transaction with rollback on any failure | N/A | P0 | F | Full |
| NFR-ASM-002 | Weight math is deterministic: identical rubric, scores, and scored sets always yield the identical final score | N/A | P0 | U | Full |
| NFR-ASM-003 | Finalized assessments accept no score mutation through any path | N/A | P0 | F | Full |
| NFR-ASM-004 | Score keystrokes persist without an explicit save control and evaluable versus read-only competencies render distinctly | N/A | P1 | F | Full |
| NFR-ASM-005 | Finalization requires an explicit confirmation step before the transaction commits | N/A | P1 | F | Full |
| NFR-ASM-006 | Assessment audit entries mask personal data before reaching any log sink | N/A | P0 | F | Full |
| NFR-ASM-007 | Grading and student views eager-load relations with no N+1 query patterns | N/A | P1 | F | Full |
| NFR-ASM-008 | Assessment tables use UUID v7 primary keys with UUID foreign keys and explicit delete behavior | N/A | P0 | A | Full |
| NFR-ASM-009 | All PHP files declare strict types and follow the shared style gate | N/A | P1 | A | Full |

### 5.1 Reliability

#### NFR-ASM-001 — Transactional finalization

A finalize that writes the score but crashes before the event leaves a grade nobody reacts
to; one that logs but rolls back leaves an audit entry for something that never happened.
Wrapping the write, the dispatch, and the log in one transaction collapses those halves
into all-or-nothing. The morning of grade submission, with coordinators finalizing dozens
of records in sequence, a mid-batch failure must leave each record wholly done or wholly
untouched — never a score without its trail.

#### NFR-ASM-002 — Deterministic weight math

Run the same rubric and the same scores through finalization on Monday and on Friday, on
SQLite and on MySQL, and the number must match to the last decimal the display shows.
Floating-point summation order is fixed, redistribution ratios derive from stored weights
only, and no timestamp or random identifier leaks into the computation. A parent disputing
a grade gets a recomputation that reproduces the exact figure — that reproducibility is the
difference between a system and an opinion.

#### NFR-ASM-003 — Absolute post-finalization immutability

There is no "small correction" after finalization, no admin override path, no window —
immutability is a property of the state, not a permission of the role. Every enforcement
point in FR-ASM-018 exists to make this sentence true, and the test suite proves it by
attacking each path after finalizing. Certificates print from frozen numbers because frozen
is the only state a finalized record has.

### 5.2 Experience

#### NFR-ASM-004 — Live persistence with visual authority split

Nobody involved in this flow should think about saving. Keystrokes land on their own, and
the screen tells the truth about authority: what you may score looks editable, what belongs
to another role looks locked. The day a teacher mistakes a read-only industry competency
for an editable one is the day the visual language failed, so the distinction is structural
in the component data, not a stylesheet afterthought.

#### NFR-ASM-005 — Explicit confirmation before finalizing

Finalization is the one click in this module that cannot be undone, so it gets the one
dialog in this module that cannot be skipped. The confirmation names the student and the
computed score, forcing a last review of exactly what is about to freeze. Accidental
double-clicks, curious clicks, and mis-clicks all die at this dialog. Irreversible actions
without confirmation are how post-certificate incidents begin.

### 5.3 Integrity & Hygiene

#### NFR-ASM-006 — Masked personal data in audit entries

Finalization logs carry evaluator names, student identifiers, and score context — precisely
the payload that must never sit in plaintext. Masking runs before either sink, so even a
developer logging the full payload in a hurry produces a safe entry. Emails partial-mask,
secrets full-mask, and the activity table stays queryable for auditors without becoming a
leak investigators have to contain.

#### NFR-ASM-007 — No N+1 on grading and student views

The grading screen joins assessment, rubric structure, registration, and program data; the
student view multiplies that by every classmate checking results on results day. Relations
load eagerly in the read paths, so page cost stays flat per record instead of per nested
row. A results-day slowdown that punishes students for checking their own grades would
punish exactly the transparency PS-6 promises.

#### NFR-ASM-008 — UUID keys with explicit delete behavior

Every assessment table keys on UUID v7 — unguessable, merge-safe, consistent with the rest
of the system. Foreign keys declare their delete behavior explicitly: placement-side links
cascade because an assessment without its registration is meaningless, rubric links null
rather than cascade because history must survive template retirement, creator links null
because people leave and records stay. No implicit behavior, no mixed key types.

#### NFR-ASM-009 — Strict types and style gate

One declaration at the top of every file, one formatter across the module — the cheapest
rows in this spec and the ones that prevent the strangest bugs. Strict scalar typing turns
a score passed as a string into a loud failure instead of a silent coercion, which matters
in arithmetic this consequential. Style conformance keeps four hundred files mutually
readable. Small disciplines, load-bearing consequences.

---

## 6. API / Data Contracts

### 6.1 Rubric Model

```
Assessment/Rubric/Models/Rubric
  Table: rubrics (UUID PK)
  Fillable: internship_id, name, structure, is_active, created_by
  Casts: structure → array, is_active → boolean
  Relations: internship() BelongsTo Internship, createdBy() BelongsTo User,
             assessments() HasMany Assessment
  Deletes: assessments with rubric_id null on rubric delete (history preserved)
```

Rubric structure JSON shape:

```
structure: {
  "competencies": {
    "<uuid>": {
      "name": string, "description": ?string, "weight": int,
      "evaluator_role": "admin|teacher|supervisor", "order": int,
      "indicators": {
        "<uuid>": {
          "name": string, "description": ?string,
          "max_score": int, "weight": int, "order": int
        }
      }
    }
  }
}
```

### 6.2 Assessment Model

```
Assessment/Models/Assessment
  Table: assessments (UUID PK)
  Fillable: registration_id, rubric_id, evaluator_id, assessment_type,
            score, scores_data, feedback, finalized_at
  Casts: scores_data → array, score → float, finalized_at → datetime
  Relations: registration() BelongsTo Registration, rubric() BelongsTo Rubric,
             evaluator() BelongsTo User
  Bridge: asAssessmentResult() → AssessmentResult
  Unique: (registration_id, assessment_type, evaluator_id)
  Indexes: (registration_id, assessment_type), (evaluator_id, assessment_type), assessment_type
```

Scores payload shape:

```
scores_data: {
  "competencies": { "<competencyUuid>": { "<indicatorUuid>": float } },
  "auto": {
    "submission_avg": ?float, "logbook_rate": ?float, "attendance_rate": ?float,
    "supervision_rate": ?float, "visit_rate": ?float, "report_score": ?float
  }
}
```

### 6.3 AssessmentResult Entity

```
Assessment/Entities/AssessmentResult extends BaseEntity (final readonly)
  Constructed via fromModel(Model): static
  Predicates: isFinalized(): bool  (finalizedAt !== null)
  Math: normalizedIndicatorScore(raw, max): float
        competencyScore(indicatorScores, weights): float
        totalScore(rubricStructure, scoresData): float  (redistributes unscored
          supervisor-role weight proportionally; deterministic for equal inputs)
```

### 6.4 EvaluatorRole Enum

```
Assessment/Enums/EvaluatorRole: string (LabelEnum)
  Cases: ADMIN = 'admin', TEACHER = 'teacher', SUPERVISOR = 'supervisor', SYSTEM = 'system'
```

### 6.5 Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `InitializeAssessmentAction` | `BaseCommandAction` | registration id | assessment + rubric pair |
| `ScoreIndicatorAction` | `BaseCommandAction` | assessment, competency id, indicator id, score, acting user | `Assessment` |
| `UpdateAssessmentScoresAction` | `BaseCommandAction` | assessment, competency id, indicator id, nullable score | `Assessment` |
| `AutoCalculateAssessmentAction` | `BaseCommandAction` | assessment | `Assessment` |
| `FinalizeAssessmentAction` | `BaseCommandAction` | assessment, finalizer | `Assessment` |
| `CreateRubricAction` | `BaseCommandAction` | name, nullable description, active flag | `Rubric` |
| `UpdateRubricAction` | `BaseCommandAction` | rubric, name, nullable description, active flag | `Rubric` |
| `DeleteRubricAction` | `BaseCommandAction` | rubric | `void` |
| `CreateCompetencyAction` | `BaseCommandAction` | rubric, name, nullable description, weight, evaluator role, order | `Rubric` |
| `UpdateCompetencyAction` | `BaseCommandAction` | rubric, competency id, name, nullable description, weight, evaluator role, order | `Rubric` |
| `DeleteCompetencyAction` | `BaseCommandAction` | rubric, competency id | `Rubric` |
| `CreateIndicatorAction` | `BaseCommandAction` | rubric, competency id, name, nullable description, max score, weight, order | `Rubric` |
| `UpdateIndicatorAction` | `BaseCommandAction` | rubric, competency id, indicator id, name, nullable description, max score, weight, order | `Rubric` |
| `DeleteIndicatorAction` | `BaseCommandAction` | rubric, competency id, indicator id | `void` |

### 6.6 Events & Listeners

| Event | Dispatched by | Listener | Queued |
| ----- | ------------- | -------- | ------ |
| `AssessmentFinalized` | `FinalizeAssessmentAction` | `LogAssessmentFinalized` (dual-channel, PII masked) | No |

### 6.7 Policy

| Policy | Abilities |
| ------ | --------- |
| `AssessmentPolicy` | viewAny: super_admin, admin, teacher · view: admin, evaluator, student of registration · create: super_admin, admin, teacher · update: admin, evaluator while unfinalized, mentor proxy · finalize: super_admin, admin, teacher · delete: admin while unfinalized |

### 6.8 Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /assessments` | `AssessmentView` | `assessments` | `auth` |
| `GET /admin/assessments/rubrics` | `RubricManager` | `sysadmin.assessments.rubrics` | `auth`, `role:super_admin\|admin` |
| `GET /admin/assessments/{registration}/grade` | `AssessmentGrading` | `sysadmin.assessments.grade` | `auth`, `role:super_admin\|admin` |

### 6.9 Database Schema

```
rubrics:
  id: uuid (PK)
  internship_id: foreignUuid → internships.id (nullable, indexed, cascadeOnDelete)
  name: string
  structure: json (nullable)
  is_active: boolean (default true)
  created_by: foreignUuid → users.id (nullable, nullOnDelete)
  timestamps

assessments:
  id: uuid (PK)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete)
  evaluator_id: foreignUuid → users.id (cascadeOnDelete)
  rubric_id: foreignUuid → rubrics.id (nullable, indexed, nullOnDelete)
  assessment_type: string(30) (default 'final')
  score: float (nullable)
  scores_data: json (nullable)
  feedback: text (nullable)
  finalized_at: timestamp (nullable)
  timestamps
  Unique: (registration_id, assessment_type, evaluator_id)
  Indexes: (registration_id, assessment_type), (evaluator_id, assessment_type), assessment_type
```

---

## 7. Design Decisions

Recorded choices with their context. None carry a test layer; the behaviors they explain are
covered by the FR rows above.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ASM-001 | Rubric competencies and indicators live as nested JSON in the rubric row rather than normalized tables | P0 | — | — |
| DD-ASM-002 | Scoring authority attaches to the competency via its evaluator role instead of the assessment as a whole | P0 | — | — |
| DD-ASM-003 | Unscored supervisor weight redistributes proportionally across scored competencies at finalization | P0 | — | — |
| DD-ASM-004 | Finalization state and score math live on the entity behind the model bridge, never inline in actions | P0 | — | — |
| DD-ASM-005 | Score keystrokes persist immediately through the component listener with no explicit save control | P1 | — | — |
| DD-ASM-006 | Auto-computed values occupy their own namespace apart from manual competency scores | P1 | — | — |

### 7.1 Structure & Authority

#### DD-ASM-001 — Nested JSON for rubric structure

Rubrics are written rarely and read constantly — the grading screen needs the whole tree in
one fetch, not a join across three tables. A JSON blob delivers that in a single row read,
and UUID keys keep score references stable across reordering. The price is real: SQL cannot
reach inside to query one competency across templates. That price was acceptable because no
MVP flow needs cross-rubric competency queries, while every grading render needs the whole
tree fast. Structure validation moved to the Action layer to cover what the schema gave up.

#### DD-ASM-002 — Authority on the competency, not the assessment

The alternative was one evaluator per assessment — simple, and wrong for every real PKL
program, where teachers own academic competencies and industry supervisors own workplace
ones. Hanging the evaluator role on each competency lets a single assessment collect scores
from several hands, each fenced into its own block. Authorization logic grew more intricate
to match, but the intricacy mirrors the domain instead of fighting it. A single-evaluator
model would have forced schools to split one judgment into several assessments and
reconcile them by hand.

#### DD-ASM-003 — Proportional redistribution of missing weight

Three designs competed for the unreachable-supervisor case. Zero-filling the missing block
punishes the student for the company's absence. Refusing finalization until every block is
scored holds forty grades hostage to one inactive account. Redistribution — spreading the
missing weight over scored blocks in proportion — keeps the verdict fair and the process
moving. Its cost is interpretive: two students with identical raw scores can finalize
differently if different blocks went unscored. That variance is documented on the finalized
record rather than hidden, so it stays explainable.

### 7.2 Evaluation Mechanics

#### DD-ASM-004 — Entity behind the bridge for state and math

The model persists; the entity judges. Finalization state and every line of weight math sit
on the readonly entity constructed through the model bridge, which means the exact
production formula runs in millisecond unit tests with no database. Scattering the null
check and the ratios across fourteen actions would have guaranteed the fifteenth forgot
one. One extra class buys a single source of truth for "is it frozen" and "what is the
total" — the two questions the entire module must answer identically everywhere.

#### DD-ASM-005 — Immediate persistence with no save control

A teacher scores dozens of indicators between interruptions — bell schedules, workshop
noise, dying laptop batteries. A save button in that environment is a data-loss device: the
work exists only in the browser until pressed, and browsers close. Persisting each change
through the component listener trades request volume for safety, and at school scale the
volume is trivial. The batch-save alternative optimized for network packets while risking
the only thing that matters, the scores themselves.

#### DD-ASM-006 — Separate namespace for auto-computed values

Auto-imports advise; mentors decide. Keeping computed sub-scores in their own namespace
makes that hierarchy structural rather than conventional — no import routine can address a
manual score's keys even by bug, because the keys live in different branches. The
alternative, writing imports straight into competency slots, would have made every
aggregation run a potential overwrite of human judgment. Reference data that cannot touch
verdict data is a property of the payload shape, enforced without vigilance.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Out-of-range score writes accepted | 0 | Rejection rate on range validation in scoring actions |
| Scores written outside evaluator authority | 0 | Authorization rejection coverage across role and mentorship cases |
| Mutations accepted after finalization | 0 | Freeze enforcement probes on every mutation path |
| Rubric references to unknown structure | 0 | Action-layer validation rejection on forged identifiers |
| Finalization runs with identical result | Every rerun matches | Determinism probe with fixed inputs |
| Student grade inquiries requiring staff | Decreasing | Read-only view adoption versus manual requests |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [J9GBH](J9GBH-placement.md) | Active placement records — assessments score student performance within a placement |

### Build Guide

With this spec implemented, the system scores students against structured rubrics with
weighted, frozen grades. Assessment is the quantitative half of student evaluation; the
qualitative half — stakeholder feedback — arrives with the evaluation module, and both feed
certification.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [AXKZW](AXKZW-evaluation.md) | Evaluation gathers stakeholder feedback that complements these scores |
| 2 | [J0M04](J0M04-certification.md) | Certification combines frozen assessment scores with evaluation data |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If supervisor inactivity is systemic rather than occasional, proxy-scored industry blocks may dominate finals and weaken industry legitimacy | Open | Maintainer | — |
| A-1 | We assume one active rubric per program at a time; overlapping active rubrics resolve to the first active one | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Assessment phase and build order
- [Architecture](D2FT3-architecture.md) — Action Triad, entity separation, dual-layer authorization
- [Project initialization](QLHDO-project-initialization.md) — global requirements this spec tightens
- [Evaluation](AXKZW-evaluation.md) — qualitative feedback complementing these scores
- [Placement](J9GBH-placement.md) — registration bridge scoping evaluator authority
- [Certification](J0M04-certification.md) — consumer of the frozen score
- [Reports](R6BMW-reports.md) — grade card consumer of the frozen score
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — roles, functional roles, proxy rules
- [Logging & error handling](89SRA-logging-and-error-handling.md) — rejection contract and masked audit logging
