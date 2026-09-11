# T657Z — Assignment Grading

> **Spec ID:** T657C
> **Status:** Full
> **Owner:** Assignment
> **Depends on:** [T657Z](T657Z-assignment.md), [T657Z](T657Z-assignment-submission.md)

## Description

Grading is the mentor side of coursework: teachers and supervisors work a queue of submitted work,
assign numeric scores with written feedback, return weak work for revision, and verify receipt
where the workflow requires it. Brief authoring lives in [assignment](T657Z-assignment.md); the
student submission loop in [submission](T657Z-assignment-submission.md).

---

## 1. Problem Statements

### PS-1 — Feedback Scattered Across Channels

A teacher scores a report 78, tells the student "good, fix the method section" over chat, and
writes "B" in a spreadsheet — three records of one judgment, none of them attached to the work.
When the grade card is compiled, nobody can reconstruct why 78 and not 65. Score and reasoning
must land together, on the submission itself.
**→ Requirement:** FR-GRADE-003 (score contract), FR-GRADE-005 (audit).

### PS-2 — Weak Work Needs a Way Back

A failing score on a fixable report teaches less than a revision round: the student who gets 55
with "resubmit the analysis" learns the analysis; the student who gets 55 full-stop learns the
teacher's patience is finite. Returning work with named, actionable feedback — without destroying
the record — is the pedagogical core of this spec.
**→ Requirement:** FR-GRADE-007 (revision guard), FR-GRADE-008 (feedback contract).

### PS-3 — Graders Must Stay in Their Lane

A mentor grading outside their scope — a workshop supervisor scoring another company's students,
a teacher reaching into a cohort they don't mentor — corrupts the grade card silently, because
the number looks exactly like a legitimate one. Scope is a business invariant, not a UI filter.
**→ Requirement:** FR-GRADE-004 (scope guard in Entity), FR-GRADE-013 (proxy path).

---

## 2. Goals & Non-Goals

### Goals

- **Score with reasoning attached** — 0–100 plus written feedback stored on the submission. *Why:* a number without its why is un-auditable at grade-card time.
- **Revision as a first-class loop** — return work with actionable feedback; student resubmits the same record. *Why:* fixable work should be fixed, not failed.
- **Scope-guarded grading** — graders touch only their own students' work, enforced in the Entity. *Why:* out-of-scope scores are indistinguishable from valid ones after the fact.
- **Proxy-covered supervision** — teachers act for inactive supervisors with full audit trace. *Why:* industry mentors go quiet; student progress cannot wait.
- **Student notified of every outcome** — graded or returned, the student hears promptly. *Why:* a score nobody sees might as well not exist.

### Non-Goals

- **Rubric-based or multi-criterion scoring**. *Why:* weighted rubrics belong to Assessment ([ARDA6](ARDA6-assessment.md)); here one score, one feedback.
- **Brief authoring and submission mechanics**. *Why:* owned by [assignment](T657Z-assignment.md) and [submission](T657Z-assignment-submission.md).
- **Auto-grading or plagiarism engines**. *Why:* post-MVP depth; originality disputes stay human-handled incidents.
- **Peer or collaborative grading**. *Why:* PKL scoring authority rests with mentors, not classmates.

---

## 3. User Stories / Use Cases

The mentor's journey from queue to notified student; the student's resubmission half lives in the
submission spec.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-GRADE-001 | Teacher scores a submitted report with number and feedback; the student is notified | P0 | F | Full |
| UC-GRADE-002 | Teacher returns weak work for revision with actionable feedback instead of failing it | P0 | F | Full |
| UC-GRADE-003 | Teacher grades on behalf of an inactive industry supervisor with the proxy recorded | P1 | F | Full |

### 3.1 Direct Grading

#### UC-GRADE-001 — Teacher Scores a Submission

Monday morning the grading queue shows eleven reports awaiting review for the workshop cohort.
The teacher opens the first — two pages on lathe maintenance, attachment attached — and reads
with the score field empty beside it. Eighty-two, with three sentences on what the conclusion
gets right and what the safety section misses; save; the row flips to GRADED with her identity
and timestamp, the student gets the notification before lunch, and the activity log holds the
before/after for the day anyone asks why 82. Ten more to go, each with its reasoning attached,
which is exactly what makes grade-card week a compilation exercise instead of an archaeology dig.

#### UC-GRADE-002 — Teacher Returns Work for Revision

The third report in the queue has a solid observations section and a methodology paragraph that
describes the wrong machine entirely — scoring it now would punish a fixable mistake with a
permanent number. Instead the teacher writes what is wrong and what "fixed" looks like, long
enough to act on, and returns it; the record moves to REVISION_REQUIRED, the student sees the
feedback banner atop their submission, and no score exists yet because none was earned yet. When
the corrected version comes back it re-enters the same queue position as any new submission —
the loop added a round trip, not a second-class status.

### 3.2 Proxy

#### UC-GRADE-003 — Grading in the Supervisor's Stead

The industry supervisor at the partner workshop hasn't logged in for nine days; four of his
students' reports sit submitted and aging. Their school mentor opens the queue, sees the proxy
badge on those rows — her role plus his scope — and grades two of them against the workshop's
expectations, each entry stamped with both identities: who acted, in whose stead. The supervisor,
returning later, finds the work handled and the trace complete rather than a backlog and a
mystery. Had she reached for students outside her mentorship, the scope guard would have refused
exactly as it refuses strangers — proxy extends an existing relationship, never invents one.

---

## 4. Functional Requirements

Scoring, revision, verification, and contracts. `U` unit (no DB), `F` feature (real DB),
`B` browser, `A` arch. Every row below is implemented.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-GRADE-001 | `SubmissionGrading` inbox lists awaiting submissions with status, student, and assignment filters | P1 | F | Full |
| FR-GRADE-002 | `GradeSubmissionAction` accepts only scores 0–100, refusing anything else with `RejectedException` | P0 | F | Full |
| FR-GRADE-003 | Grading writes score, feedback, GRADED status, grader identity, and server timestamp in one transaction | P0 | F | Full |
| FR-GRADE-004 | The grader's scope is enforced in the Entity: teachers score mentored students, supervisors their company's, admins any | P0 | U | Full |
| FR-GRADE-005 | Every grading, revision, and verification writes a dual-channel audit entry with PII masking | P0 | F | Full |
| FR-GRADE-006 | Graded students are notified via queued `SubmissionFeedbackNotification` | P1 | F | Full |
| FR-GRADE-007 | `RequestSubmissionRevisionAction` accepts only SUBMITTED records, refusing others with `RejectedException` | P0 | F | Full |
| FR-GRADE-008 | Revision feedback is required, substantive, stored on the record, and shown to the student | P1 | F | Full |
| FR-GRADE-009 | Revision dispatches `SubmissionRevisionRequested` and notifies the student without blocking the request | P1 | F | Full |
| FR-GRADE-010 | `VerifySubmissionAction` records supervisor confirmation with identity and timestamp, reachable via mentor proxy | P1 | F | Full |
| FR-GRADE-011 | Every mutation is dual-gated: Policy at the boundary, scope and state re-checked in Action/Entity | P0 | A | Full |
| FR-GRADE-012 | Input validates server-side through DTOs; failures throw translatable `RejectedException`; all strings via `__()` | P0 | A | Full |
| FR-GRADE-013 | Cross-role proxy (teacher→supervisor, admin→teacher/supervisor) resolves through the registration bridge with proxy identity audited | P0 | F | Full |

### 4.1 Scoring

#### FR-GRADE-001 — The Inbox Finds the Work

The runtime walk opens on the queue, not the record: awaiting submissions across the grader's
scope, filterable by status (submitted versus returned-for-revision), student name, and brief —
because at inbox week the difference between "eleven to grade" and "eleven plus forty already
graded" is the filter. Search resolves against the registration bridge so a name finds the right
enrollment even when two cohorts share a surname. Counts and rows come from one scoped query;
the badge that says "eleven" and the eleven rows below it can never disagree, which sounds
trivial until the morning they did and a report went ungraded for a week.

#### FR-GRADE-002 — The Range Is the Law

Zero to one hundred is not a suggestion the UI enforces — it is a contract the Action owns, so a
crafted request carrying 150 or −5 dies with `RejectedException` instead of corrupting the grade
card's arithmetic. Boundary values pass: 0 is a real score (submitted, utterly off-brief), 100
is a real score (flawless), and anything outside is not a score at all. The check sits before
any write, which means a rejected score leaves no trace on the record — no partial feedback
saved, no timestamp touched, the submission exactly as the student left it.

#### FR-GRADE-003 — Score Lands With Its Reasoning

Grading writes five things at once — score, feedback, GRADED status, grader id, timestamp — in a
single transaction, because a score without its timestamp is undiscoverable in disputes and a
score without its grader is unactionable. Feedback may be brief for a clean pass but it is never
absent: the field that forces even "solid work, minor formatting notes" onto the record is what
separates this system from the spreadsheet era of context-free numbers. Re-grading an already
graded record is refused rather than overwritten — a correction is a new audited decision (return
for revision, then re-grade), never a silent edit of history.

#### FR-GRADE-004 — Scope Lives in the Entity

The failure this row exists to prevent has no error message: a valid-looking score from an
out-of-scope grader, discovered (if ever) at grade-card reconciliation when nobody remembers who
touched what. So the Entity answers "may this grader score this submission" from the
registration bridge — mentored students for teachers, own company's for supervisors, any for
admins — in a database-free predicate the unit suite exhausts: assigned teacher allowed,
unassigned teacher refused, foreign supervisor refused, admin allowed. Because the check is
business logic rather than route filtering, it holds identically for HTTP, console, and direct
Action calls.

#### FR-GRADE-005 — Every Judgment Leaves a Trace

If a parent ever asks why their child's report scored 64, the answer is retrievable without
anyone's memory: actor, timestamp, before/after, and the feedback text, in the queryable
activity channel with student PII masked. The masking matters because the most convenient thing
to log — the full submission payload — is also the most confidential, so payloads are reduced
to identifiers before they reach either sink. Verification and revision write the same shape of
entry; the audit story of a submission reads as one chronological narrative from submit to
grade.

#### FR-GRADE-006 — The Student Hears Promptly

A score the student never sees changes nothing — so grading queues `SubmissionFeedbackNotification`
on commit, and the queue (not the request) carries it to mail, broadcast, and database channels.
The ordering guarantee is commit-before-queue: no notification can ever announce a grade whose
transaction rolled back. During grading week, when a mentor scores thirty reports in an hour, the
fan-out drains behind the scenes while the inbox stays instant; the alternative — inline
delivery — was measured once, watched the request times climb with the mail server's mood, and
abandoned.

### 4.2 Revision & Verification

#### FR-GRADE-007 — Only Submitted Work Can Be Returned

Returning a DRAFT for revision is nonsense (nothing was offered), returning a GRADED record is
revisionism (judgment already rendered) — so the Action demands SUBMITTED and refuses the rest
with a message naming the actual state. The guard's real work is concurrency: two mentors
opening the same report, one grading while the other reaches for "request revision" — whoever
commits second meets the state the first left behind and gets the refusal instead of silently
overwriting a fresh grade. Second writer loses, loudly, which is the only safe resolution when
both writers are human.

#### FR-GRADE-008 — Feedback Worth Acting On

"Fix it" is not feedback — so revision requires substantive text, long enough to name the defect
and the remedy, stored on the record where the student cannot miss it and the re-reviewer can
compare round against round. Empty or token-length feedback is refused at validation, before any
state change, because a returned submission with no guidance strands the student worse than a
low score would. The stored text becomes part of the audit narrative: months later the grade
card's story for that student reads submitted → returned ("methodology describes wrong machine,
resubmit with correct procedure") → resubmitted → graded, with every step attributed.

#### FR-GRADE-009 — The Loop Announces Itself

The moment a submission moves to REVISION_REQUIRED, two things happen: the
`SubmissionRevisionRequested` event dispatches for any future listener (analytics, escalation
timers, coordinator digests), and the student notification queues from the request path so the
news never waits on a listener that doesn't exist yet. Students learn their work came back
within the same hour, not at the next login surprise. The dual mechanism mirrors the publish
fan-out deliberately — immediate inline notice plus durable event — because the failure mode is
identical: a state change its human doesn't hear about might as well not have happened.

#### FR-GRADE-010 — Verification Confirms Receipt

Some workflows need a lighter touch than a score: the supervisor confirms the report arrived and
matches workshop reality, stamping VERIFIED with identity and timestamp, and the teacher's
scoring proceeds on verified ground. Verification is reachable through the same mentor-proxy
path as grading, since the inactive-supervisor problem bites here first — unconfirmed reports
pile up exactly where supervisors go quiet. VERIFIED is terminal for the submission's forward
motion toward grading but never overwrites a score; the two marks coexist because they answer
different questions (was it received? how good was it?).

### 4.3 Contracts

#### FR-GRADE-011 — Two Gates, Including Scope

Policy answers role ("may graders touch this"), Action re-checks state and scope inside the
transaction ("may this grader touch this record now") — and the second gate is where FR-GRADE-004
plugs in, so scope violations throw `RejectedException` with the same gravity as state
violations. The pairing the pilot got wrong was revision: the policy allowed it, but nothing
re-checked SUBMITTED inside the transaction until the concurrent-mentor incident forced the
second gate in. Every grading mutation now passes both, from every entry point, without
exception.

#### FR-GRADE-012 — Validated, Explainable, Translated

Score, feedback, and identity arrive as validated DTOs (`GradeSubmissionData` carries score plus
feedback); raw request data never reaches the write. Failures speak the student's and mentor's
language literally — translatable messages through `__()`, mirrored locales, dynamic values as
placeholders. The message students read most ("submission returned for revision: …") and the one
mentors read most ("score must be between 0 and 100") both survived translator review without
restructuring, which is the quiet proof the placeholder discipline holds.

#### FR-GRADE-013 — Proxy Without Role Creep

Nobody gains a second role: the teacher stays a teacher, and the proxy resolves at request time
through the registration bridge — same mentorship check as FR-GRADE-004, plus the proxy-role
stamp in the audit entry recording in whose stead she acted. Admins proxy widest (any student,
either mentor role); teachers proxy supervisors for mentored students only; supervisors and
students proxy nobody. The hierarchy direction is load-bearing: downward only, never sideways,
so no supervisor ever grades in a teacher's stead and no student ever borrows either. Session
banner and policy badge make the active proxy visible in the UI, because invisible delegated
authority is how trust in the grade card erodes.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-GRADE-001 | All PHP files declare `strict_types=1` | N/A | P0 | A | Full |
| NFR-GRADE-002 | Every translation key exists in both `lang/en/` and `lang/id/` | N/A | P0 | A | Full |
| NFR-GRADE-003 | Grading inbox is paginated with eager-loaded relations; no N+1 | N/A | P1 | F | Full |
| NFR-GRADE-004 | Grade commit precedes notification queueing; no notice for rolled-back writes | N/A | P0 | F | Full |
| NFR-GRADE-005 | Scores render from the submission record everywhere; no cached copies in grade cards | N/A | P1 | A | Full |

### 5.1 Conventions

#### NFR-GRADE-001 — Strict Types Everywhere

A float score of 82.5 coerced silently to string "82.5" in one place and rounded to 82 in another
produced the only grade dispute the pilot couldn't resolve from the audit log — the log was
right, the rendering was lossy. Strict types don't fix rendering, but they force every boundary
to be explicit about what a score is, so the next lossy conversion fails loudly at the boundary
instead of quietly in the view. The scan enforces the declaration file by file; no grading-path
file is exempt.

#### NFR-GRADE-002 — Mirrored Locales

Revision feedback travels in the mentor's language, but every word around it — labels, buttons,
refusal messages, notification subjects — resolves bilingually. The D3 scan holds parity across
locale files so a supervisor working in English and a student reading in Indonesian see the same
verdict with equal clarity. Placeholder discipline carries the weight here: feedback text
interpolates into translated templates without breaking sentence structure in either language.

### 5.2 Delivery & Consistency

#### NFR-GRADE-003 — The Inbox Stays Fast

Grading week concentrates the whole cohort's reports into one screen, and an inbox that fires a
query per row turns dedication into a loading spinner. Pagination bounds the page; eager loading
bounds the queries; the two together keep inbox week interactive on shared hosting. The fixed
page size is deliberate — coordinators asking for "everything on one page" get the same refusal
as students asking for late submission, for the same reason: unbounded work fails at the worst
moment.

#### NFR-GRADE-004 — Commit Before Announcement

The ordering invariant is small and absolute: the grade transaction commits first, the
notification queues second, and no code path inverts them. Its violation would be the phantom
grade — a student celebrating an 82 that rolled back — which is worse than a late notification
by every measure that matters. Feature tests assert the order by observing queued notifications
only after successful commits; a rolled-back grading run must show an empty queue.

#### NFR-GRADE-005 — One Source for Every Score

Grade cards, transcripts, and the student's own view all read the score from the submission
record — no duplicated score columns, no snapshot copies that drift when a re-grade lands.
Duplication was proposed once for "report performance" and rejected: at school scale the join is
cheap, while a stale cached 78 beside a corrected 82 is a dispute factory. If a future scale
tier needs denormalization, it arrives as an invalidated cache behind the registry, never as a
second writable copy.

---

## 6. API / Data Contracts

### 6.1 Actions & Data

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `GradeSubmissionAction` | `BaseCommandAction` | `Submission, GradeSubmissionData` | `Submission` |
| `RequestSubmissionRevisionAction` | `BaseCommandAction` | `Submission, string $feedback` | `Submission` |
| `VerifySubmissionAction` | `BaseCommandAction` | `Submission` | `Submission` |

`GradeSubmissionData` extends `BaseData` (readonly): `score: int (0–100)`, `feedback: ?string`.
Revision feedback validates for substance (minimum actionable length) server-side.

### 6.2 Events & Notifications

| Class | Role |
| ----- | ---- |
| `SubmissionRevisionRequested` | Dispatched by `RequestSubmissionRevisionAction` |
| `SubmissionFeedbackNotification` | To the student on graded or revision-requested; mail, broadcast, database; `ShouldQueue` |

### 6.3 Policies & Routes

`SubmissionPolicy` grading surface: viewAny admin/teacher/supervisor; view admin/owner/
mentor-proxy; verify admin/mentor-proxy. Routes serving `SubmissionGrading`:
`GET /admin/submissions/grading` (`role:super_admin|admin`),
`GET /supervision/submissions/grading` (`role:teacher|supervisor`),
`GET /teacher/submissions/grading` (`role:teacher`) — all behind `auth`.

### 6.4 Score & Scope Predicates

Grading writes `score`, `feedback`, `status=GRADED`, `graded_by`, `graded_at` atomically.
Scope predicate (Entity, unit-tested): teacher ∈ submission's mentorship, supervisor ∈ owning
company, admin unrestricted; proxy resolves through the same predicate with `proxy_role`
recorded in `activity_log.properties`.

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-GRADE-001 | Score range enforced in the Action, not only in the UI | P0 | — | — |
| DD-GRADE-002 | Grading scope enforced in the Entity, not only in the Policy | P0 | — | — |
| DD-GRADE-003 | Revision notification goes inline; the event stays for future listeners | P1 | — | — |

### 7.1 Grading Shape

#### DD-GRADE-001 — The Action Owns the Range

UI sliders stop at 100 and start at 0, which is exactly as trustworthy as the browser running
them — the ultimatum that settled this was a crafted request scoring a friend's report 999
during a late-night "test". The Action's range check makes the UI a convenience and the domain
the law; both agree in practice, and when they disagree the domain wins with an explainable
refusal. Client validation stays (fast feedback for honest mistakes), but no test, audit, or
argument ever depends on it.

#### DD-GRADE-002 — The Entity Owns the Scope

Policies see requests; Entities see relationships — and scope is a relationship question (who
mentors whom, whose company is whose), so it lives where relationships are judged. The
practical payoff is test speed: the whole scope matrix asserts in milliseconds without
factories or migrations, and every new proxy rule lands in the same predicate its siblings
already cover. The policy still gates coarsely (graders versus everyone else), keeping the
division the architecture prescribes: boundary says maybe, domain says yes or no.

#### DD-GRADE-003 — Notify Now, Event Anyway

Revision notifications go out from the request path because the student waiting on returned
work checks hourly, not daily — deferring the notice to a listener that currently has no other
consumer would trade immediacy for architecture points. The event dispatches regardless, so the
day a coordinator wants "unanswered revisions older than three days" the hook already exists
with history behind it. Inline for the human, event for the future: the same split the publish
flow chose, for the same reason.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Scores outside 0–100 stored | 0 | Action range guard; score audit |
| Grades by out-of-scope graders | 0 | Entity scope predicate; proxy audit |
| Graded submissions without student notice | 0 | Queued notification per commit |
| Revisions without actionable feedback | 0 | Feedback substance validation |
| Scores disagreeing across views | 0 | Single-source reads; grade-card review |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [T657Z](T657Z-assignment-submission.md) | `Submission` model, `SubmissionStatus`, student submit/resubmit flow |
| [TXR2H](TXR2H-notification-infrastructure.md) | Channel registration behind the feedback notification |

### Build Guide

With this spec, mentors work the grading inbox, score with reasoning, return weak work for
revision, verify receipt, and proxy for quiet supervisors — every outcome notified and audited.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [T657Z](T657Z-assignment.md) | Publish fan-out feeds the queue this spec drains |
| 2 | [PKYX6](PKYX6-document-templates.md) | Scores flow into report cards and certificates |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume single-score-plus-feedback suffices for MVP; weighted multi-criterion scoring arrives with Assessment rubrics | Accepted | Maintainer | — |

## Quick References

- [Assignment](T657Z-assignment.md) — brief lifecycle, publish fan-out, closure
- [Assignment submission](T657Z-assignment-submission.md) — student submit/resubmit loop, late policy, uploads
- [Assessment](ARDA6-assessment.md) — rubric scoring, outside single-score scope
- [Placement](J9GBH-placement.md) — registration and mentorship bridge scope relies on
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — roles, ownership, cross-role proxy hierarchy
- [Notification infrastructure](TXR2H-notification-infrastructure.md) — channels behind feedback notices
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger channels, PII masking, exception trees
