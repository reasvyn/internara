# Supervision — Supervision Logs, Monitoring Visits, Cross-Role Proxy & Compliance

> **Spec ID:** 2EHSE
> **Status:** Full
> **Owner:** Journals
> **Depends on:** J9GBH, 1KSWL

## Description

The mentor side of the daily record: supervision logs that document mentoring sessions,
monitoring visits that prove a teacher actually reached the workplace, proxy verification that
keeps workflows moving when industry supervisors go silent, and compliance checks that notice
missing entries before they become missing weeks. Where [daily-activity](1KSWL-daily-activity.md)
is the student's voice, this spec is the mentors' answer.

---

## 1. Problem Statements

### PS-1 — Mentorship Nobody Can See

Industry supervisors mentor students every week, but without structured session records the
school coordinator sees none of it — quality, frequency, and gaps are all invisible. A
mentoring program with no records is a claim, not a program.
**→ Requirement:** FR-SUPV-001–006 (supervision log lifecycle).

### PS-2 — Visits That Leave No Proof

A teacher drives 150 kilometers to a remote placement, talks with the site supervisor, observes
the student — and returns with nothing the system can show except memory. Coordinators cannot
assess monitoring coverage across nearby and far-flung sites alike.
**→ Requirement:** FR-SUPV-007–010 (visit logging with method and verification).

### PS-3 — Workflows Blocked by Silent Supervisors

Industry supervisors miss days, change roles, or simply never open the app. Student logbooks
and supervision reviews pile up behind an approver who is not there, and nobody has standing
authority to step in.
**→ Requirement:** FR-SUPV-011/012 (proxy delegation with audit).

### PS-4 — Missing Entries Discovered Too Late

A student who stops writing goes unnoticed until the end-of-period reckoning, when recovery is
impossible. Silence compounds: three missing days become three missing weeks.
**→ Requirement:** FR-SUPV-013–015 (consecutive-day detection and escalation).

---

## 2. Goals & Non-Goals

### Goals

- **Document every mentoring session** — topic, notes, feedback, and a visible lifecycle from draft to completion. *Why:* turns invisible mentorship into an auditable program.
- **Prove monitoring visits happened** — method, location, duration, observations, and admin verification. *Why:* distance degrades supervision; the visit log is how coordinators see coverage across 15 km and 150 km alike.
- **Let teachers cover silent supervisors** — bounded, audited proxy verification that never waits for permission. *Why:* student workflows cannot stall on an unreachable approver.
- **Catch silence early** — consecutive missing days notify the mentor, prolonged silence reaches the coordinator. *Why:* a nudge on day three saves a period; a reckoning at week twelve saves nothing.

### Non-Goals

- **Video-call integration for virtual visits**. *Why:* the visit record stores that a virtual meeting happened; embedding a meeting platform is product scope far beyond proof-of-visit.
- **Automated escalation to school administration beyond notification**. *Why:* notifying the coordinator is MVP; escalation policy engines with auto-assignment are post-MVP depth.
- **Visit scheduling daemons and route optimization**. *Why:* teachers plan visits themselves; a scheduler that assigns and routes visits is operational machinery the MVP does not need.

---

## 3. User Stories / Use Cases

Visits prove presence, reviews close the feedback loop, and compliance watches the gaps between
the two.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SUPV-001 | Teacher logs a monitoring visit to a student's workplace, later verified by admin | P0 | F | Full |
| UC-SUPV-002 | Supervisor reviews a submitted supervision log with feedback, or the teacher does so by proxy | P0 | F | Full |
| UC-SUPV-003 | Mentor is notified when a student misses consecutive logbook days, coordinator warned on prolonged silence | P1 | F | Full |

### 3.1 Visits and Reviews

#### UC-SUPV-001 — Teacher Logs a Monitoring Visit

The teacher returns from the industrial estate, opens the visit manager, and writes down what
the road taught: the student, the date, whether it was a site visit, a video call, or a phone
check, the location, how long it took, the student's condition, the company's words, and what
needs following up. An admin later verifies the record, stamping it as reviewed fact rather
than self-report. For the far placement visited once a period, that single verified row may be
the only physical proof of oversight the period produces.

#### UC-SUPV-002 — Supervisor Reviews a Supervision Log

Submitted logs wait in the review queue until the assigned supervisor opens one, reads the
session account, and answers with feedback — the transition from submitted to reviewed is that
answer made structural. When the supervisor seat is empty, the student's teacher opens the
same queue and reviews in their stead, and the log remembers both the hand that wrote the
review and the role it was written in.

### 3.2 Compliance Watch

#### UC-SUPV-003 — Silence Triggers a Warning

Nobody files anything when a student stops writing — that is precisely the problem. The
compliance check counts consecutive days without a logbook entry, and on the third silent day
the mentor receives a notice naming the student and the gap. If the silence stretches two days
further, the coordinator hears too. What would have been discovered at grading time is now
discovered while a conversation can still fix it.

---

## 4. Functional Requirements

Supervision logs and visits are Command-owned writes with Entity-held rules, like everything
in Journals. Proxy authority resolves through the registration's MentorEntity — one source of
truth, never a role shortcut.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SUPV-001 | SupervisionLogStatus enum (DRAFT, SUBMITTED, REVIEWED, ACKNOWLEDGED, VERIFIED, COMPLETED) with a closed transition map | P0 | U | Full |
| FR-SUPV-002 | SupervisionType enum (GUIDANCE, SUPERVISORING as `mentoring`, MONITORING) | P1 | U | Full |
| FR-SUPV-003 | Role-aware log creation: teachers create GUIDANCE logs auto-completed; other authors create MENTORING logs as SUBMITTED | P0 | F | Full |
| FR-SUPV-004 | Review transitions SUBMITTED to REVIEWED carrying supervisor feedback | P0 | F | Full |
| FR-SUPV-005 | Verification stamps verifier identity and timestamp, moving the log to VERIFIED | P0 | F | Full |
| FR-SUPV-006 | Deletion allowed only for DRAFT logs | P1 | F | Full |
| FR-SUPV-007 | VisitMethod enum (SITE_VISIT, VIRTUAL_MEETING, PHONE_CALL) covering physical and remote oversight | P0 | U | Full |
| FR-SUPV-008 | Visit creation records teacher, registration, date, method, location, duration, and observations | P0 | F | Full |
| FR-SUPV-009 | Visit verification rejects already-verified visits and stamps verifier identity with timestamp | P0 | F | Full |
| FR-SUPV-010 | VisitState predicates (editable, deletable, recent-within-7-days) evaluated on the Entity | P1 | U | Full |
| FR-SUPV-011 | Supervisor-scoped policies delegate proxy decisions to MentorEntity via the registration bridge; the HasMentorProxy trait is the policy helper, the entity is the authority | P0 | U | Full |
| FR-SUPV-012 | Every proxy action records `proxy_role` and reason in the activity properties beside the actor | P0 | F | Full |
| FR-SUPV-013 | N consecutive missing logbook days (default 3) notify the assigned mentor | P1 | F | Full |
| FR-SUPV-014 | Silence reaching N+2 days additionally notifies the program coordinator | P1 | F | Full |
| FR-SUPV-015 | The compliance check runs on demand via command with minimal scheduler cadence | P1 | F | Full |
| FR-SUPV-016 | Visit authorization: creation for admin/teacher, verification for admin, edits bounded by VisitState | P0 | F | Full |

### 4.1 Supervision Logs

#### FR-SUPV-001 — Supervision Log Status Transitions

Six states trace a session from private notes to finished business: draft, submitted,
reviewed, acknowledged, verified, completed. The map on the enum is the only place that
knows reviewed follows submitted and nothing follows completed. An Action attempting any
other jump is rejected with the illegal transition named, which keeps the lifecycle readable
in audits years later — every log's history is a walk along published edges.

#### FR-SUPV-002 — Supervision Type Vocabulary

Guidance for the teacher's academic sessions, mentoring for the industry supervisor's floor
work, monitoring for oversight checks. The middle case carries a historical scar: the enum
case reads SUPERVISORING while the stored value says `mentoring`, a backward-compatibility
alias from before the vocabulary settled. Renaming the stored value would orphan every
existing row's meaning, so the alias stays, documented where it cannot be missed.

#### FR-SUPV-003 — Role-Aware Log Creation

A teacher writing guidance is both author and authority, so the log lands complete —
verified by construction, no review queue involved. Anyone else writing a mentoring log
produces a claim awaiting judgment, landing as submitted. This asymmetry mirrors reality:
the school trusts its own teachers' session accounts immediately, while industry accounts
pass through review before they count.

#### FR-SUPV-004 — Review With Feedback

Review is not a button but an answer: the supervisor's feedback is the payload that moves
the log from submitted to reviewed. A review without feedback is refused, because an empty
approval teaches the student nothing and tells the coordinator nothing. The reviewer
identity and timestamp land with the feedback, binding the judgment to a person and a
moment.

#### FR-SUPV-005 — Verification Stamp

Verification closes the loop the review opened. The verifier — supervisor, or teacher
standing in — is named alongside the timestamp, and the log reaches verified. From here the
only honest onward move is completion of whatever the session set in motion. The stamp is
monotonic: nothing in the system un-verifies, so the audit never has to explain a
disappearing endorsement.

#### FR-SUPV-006 — Draft-Only Deletion

Only drafts die. Once a log is submitted it belongs partly to its reviewers — deleting it
would erase a pending obligation someone else is already counting on. A submitted log the
author regrets is withdrawn through status movement or superseded by a newer entry, both of
which leave history. The delete path checks the state first and rejects anything beyond
draft with the state named as the reason.

### 4.2 Monitoring Visits

#### FR-SUPV-007 — Visit Method Vocabulary

Three methods span the distance curve from the supervision research: site visits for the
nearby placements teachers reach monthly, virtual meetings for the mid-range sites, phone
calls for the far posts where even video fails. Recording the method matters because a
period covered by three site visits and a period covered by three phone calls are different
oversight stories, and the coordinator reading coverage statistics needs to see which one
each placement got.

#### FR-SUPV-008 — Creating a Visit Record

The creation call takes the teacher, the registration, and the visit account — date, method,
location, duration in minutes, notes, the student's observed condition, the company's
feedback, and follow-up actions. Location is a plain description ("PT Maju Jaya workshop,
Line 2"), never coordinates: the visit proves a human went and looked, and prose places
that better than pins. Duration is a number the teacher reports, used for coverage
statistics, not a stopwatch the system enforces.

#### FR-SUPV-009 — Verifying a Visit

Admin verification is what promotes a visit from self-report to institutional fact. The
Action refuses already-verified records — double verification would suggest the first stamp
was somehow insufficient — and otherwise stamps verifier and timestamp in one atomic write.
An unverified visit still shows in coverage views, but marked as pending review, so
coordinators can distinguish claimed visits from confirmed ones at a glance.

#### FR-SUPV-010 — Visit Edit, Delete, and Recency Rules

A visit younger than seven days is still fresh enough to correct: the teacher who
misremembered the duration fixes it, and the Entity's recency predicate says yes. Older
visits lock against casual edits — memory fades, and a month-late correction is really a
new claim wearing an old date. Deletion follows the same temperament as supervision logs:
fresh, unverified, and owned, or not at all. All three predicates live on the Entity, so
unit tests freeze time and assert the boundary without a database.

### 4.3 Cross-Role Proxy

#### FR-SUPV-011 — MentorEntity as the Proxy Authority

Every supervisor-scoped policy method in Journals ends in the same single line: ask the
registration's MentorEntity whether this actor may act. The HasMentorProxy trait on the
policy is plumbing — it fetches the entity through the registration bridge — while the
grant itself lives in entity methods like `canReviewSupervisionLog` and
`canProxyAsSupervisor`, tested in milliseconds with fabricated mentor collections. An admin
may stand in for teacher or supervisor on any record; a teacher may stand in for the
supervisor only for students in their own mentorship; supervisors and students hold no
proxy power. No policy invents its own role arithmetic, so mentorship scope can never be
bypassed by a bare role check.

#### FR-SUPV-012 — Proxy Audit Trail

A proxy verification writes the ordinary activity entry — actor, event, model — plus two
properties that change its meaning: the proxied role and the reason, typically supervisor
silence. Years later the audit reads unambiguously: teacher X verified this logbook acting
as supervisor because the supervisor was inactive. Without those properties, cover would be
indistinguishable from forgery; with them, it is documented stewardship. The schema never
changed for this — properties JSON carried the context, no migration required.

### 4.4 Compliance Monitoring

#### FR-SUPV-013 — Missing-Day Detection and Mentor Notice

The check walks each active student's recent days and counts the trailing run of dateless
days. At three, the assigned mentor gets a notice naming the student and the length of the
silence — early enough that a conversation, a sick note, or a scolding still changes the
period's outcome. Weekends and holidays are excluded from the count, because punishing rest
days would train everyone to ignore the warnings entirely.

#### FR-SUPV-014 — Coordinator Escalation on Prolonged Silence

Two days past the mentor notice, with the gap still open, the coordinator hears as well.
The escalation is additive, not transferred — the mentor stays responsible, the coordinator
gains visibility. This is the point where a personal problem becomes a program problem, and
the second pair of eyes exists to tell the difference between a lazy week and a placement
breaking down.

#### FR-SUPV-015 — On-Demand Compliance Command

The whole check lives in one artisan command that any operator can run by hand after a long
holiday or before accreditation season. A minimal scheduled cadence keeps it running
without ceremony — daily at most, a single pipeline, no worker supervision or fan-out. The
deliberate absence of scheduling machinery is the point: a command plus a clock line covers
every school in the program, and anything grander would be operational theater.

#### FR-SUPV-016 — Visit Authorization Bounds

Creation belongs to admins and teachers — the people who actually travel. Verification
belongs to admins alone, keeping the reviewer independent of the traveler. Later edits and
deletes pass through the VisitState predicates first, so authorization and freshness compose:
even an admin editing a two-month-old verified visit must go through the revision path
rather than silent mutation. Policy tests assert each grant and each refusal per role.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SUPV-001 | Pending supervision items surface on the responsible mentor's dashboard | 100% of pending items visible | P1 | F | Full |
| NFR-SUPV-002 | No hardcoded user-facing strings in supervision views, components, or notifications | 0 hardcoded strings (D3 scan clean) | P0 | A | Full |
| NFR-SUPV-003 | Supervision audit entries mask personal data in notes and feedback payloads | 0 PII leaks | P0 | F | Full |
| NFR-SUPV-004 | Teachers read only their own mentees' supervision data; cross-mentorship access is refused | 0 cross-mentorship leaks | P0 | F | Full |
| NFR-SUPV-005 | Compliance gaps emit one notice per level per day, never duplicate escalations | 0 duplicate escalations per day | P1 | F | Full |

### 5.1 Visibility and Language

#### NFR-SUPV-001 — Pending Work Finds Its Owner

A submitted log awaiting review is a promise the system made to a student, and promises
kept in invisible queues rot. Every pending item — unreviewed logs, unverified visits,
unprocessed gaps — renders on the dashboard of whoever owes the next move. The measurable
claim counts coverage: for any pending item, its owner's dashboard shows it. A supervisor
who never opens the app still fails, but the failure is theirs, not the system's.

#### NFR-SUPV-002 — Zero Hardcoded Strings

Supervisors and teachers read in Indonesian first; every supervision screen, email, and
toast passes through the translation helper with keys mirrored in both locale files. The D3
scan enforces this mechanically across the domain. Dynamic content — student names, company
titles, dates — travels as placeholders, never concatenation, so neither language reads as
an afterthought.

### 5.2 Privacy and Restraint

#### NFR-SUPV-003 — Masked Supervision Audit

Supervision notes name names: students, conditions, company remarks. When those payloads
enter the audit trail they pass through PII masking first — emails, phones, and identity
numbers masked, the professional substance intact. Sampling audit entries and finding full
personal data in plaintext is the failure mode; the target is its total absence.

#### NFR-SUPV-004 — Mentorship-Scoped Reads

A teacher mentoring one internship group must find nothing when probing another group's
supervision logs — not an error page that confirms existence, but a refusal identical to
non-existence. The bridge resolves mentorship from group membership, so scope follows the
actual teaching relationship rather than the role title. Tests probe across the boundary in
both directions: own mentees visible, others' invisible.

#### NFR-SUPV-005 — One Notice Per Gap Per Day

A compliance system that spams trains its recipients to filter it into oblivion. Each gap
emits at most one mentor notice and one coordinator escalation per day, no matter how many
times the command runs. Re-running the check is therefore always safe — an operator
nervous before accreditation week can run it hourly without burying anyone's inbox.

---

## 6. API / Data Contracts

### 6.1 SupervisionLog Model

```
App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog
  Table: supervision_logs (UUIDv7 PK)
  Fillable: registration_id, supervisor_id, type, date, topic, notes, status,
            supervisor_feedback, reviewed_by, reviewed_at,
            is_verified, verified_by, verified_at
  Casts: date → date, status → SupervisionLogStatus, reviewed_at → datetime
  Relations: registration() BelongsTo Registration,
             supervisor() BelongsTo User, reviewer() BelongsTo User
  Bridge: asSupervisionLogState() → SupervisionLogState
```

### 6.2 MonitoringVisit Model

```
App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit
  Table: monitoring_visits (UUIDv7 PK)
  Fillable: registration_id, teacher_id, visit_date, method, location,
            duration_minutes, notes, student_condition, company_feedback,
            follow_up_actions, is_verified, verified_by, verified_at
  Casts: visit_date → date, method → VisitMethod, is_verified → boolean
  Relations: registration() BelongsTo Registration, teacher() BelongsTo User,
             verifier() BelongsTo User
  Bridge: asVisitState() → VisitState
```

### 6.3 Enums

| Enum | Cases |
| ---- | ----- |
| `SupervisionLogStatus` | DRAFT, SUBMITTED, REVIEWED, ACKNOWLEDGED, VERIFIED, COMPLETED |
| `SupervisionType` | GUIDANCE, SUPERVISORING (value `mentoring`), MONITORING |
| `VisitMethod` | SITE_VISIT, VIRTUAL_MEETING, PHONE_CALL |

### 6.4 Actions

SupervisionLog: `CreateLogAction`, `CreateSupervisionLogAction`, `DeleteLogAction`,
`ReviewLogAction`, `VerifySupervisionLogAction` (Command).
MonitoringVisit: `CreateVisitAction`, `VerifyVisitAction` (Command).
Compliance: `journals:check-compliance` artisan command (plus
`ReadUnaccountedDatesAction` for gap reads).

### 6.5 Proxy and Policies

Proxy authority: `App\Modules\User\Domain\Mentor\Entities\MentorEntity` bridged via
`Registration::asMentorEntity()`; policy helper
`App\Modules\User\Policies\Concerns\HasMentorProxy::mentorProxyFor()`.

Policies: `SupervisionLogPolicy` (create: student/mentor; review: supervisor or
MentorEntity proxy; delete: admin or owner while DRAFT); `MonitoringVisitPolicy`
(create: admin/teacher; verify: admin; update/delete: admin or owner while editable).

### 6.6 Routes and Schema

Routes: `GET /student/supervision-logs` (`student.supervision-logs`),
`GET /student/monitoring-visits` (`student.monitoring-visits`),
`GET /supervision/logs` (`supervision.logs`),
`GET /monitoring-visits/` (`monitoring-visits.index`) — all behind `auth` plus role
middleware; proxy authority is checked at the policy layer, never in middleware.

```
supervision_logs:
  id: uuid PK; registration_id → registrations (cascade);
  supervisor_id → users (set null)
  type, date, topic (nullable), notes (text), status (default 'draft')
  supervisor_feedback, reviewed_by → users (set null), reviewed_at
  is_verified, verified_by → users (set null), verified_at

monitoring_visits:
  id: uuid PK; registration_id → registrations (cascade);
  teacher_id → users (set null)
  visit_date, method, location (varchar 512), duration_minutes (unsigned)
  notes, student_condition, company_feedback, follow_up_actions
  is_verified, verified_by → users (set null), verified_at
```

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SUPV-001 | SupervisionType keeps the `mentoring` stored value behind the SUPERVISORING case for backward compatibility | P1 | — | — |
| DD-SUPV-002 | Proxy lives at the policy layer through MentorEntity, not in middleware or a second role | P0 | — | — |
| DD-SUPV-003 | Compliance is an on-demand command with notifications, not a daemon pipeline | P1 | — | — |

### 7.1 Naming and Authority

#### DD-SUPV-001 — The Mentoring Alias

Early in the build the industry session concept was called mentoring in the database and
supervising in conversation, and both names shipped before anyone noticed the split. By
then rows existed, reports filtered on the stored value, and renaming the column would
have rewritten history. The enum now wears the business name while persisting the legacy
value — a small, documented scar that costs one confused look per new developer and saves
one data migration nobody wants to run during placement season.

#### DD-SUPV-002 — Policy-Layer Proxy Without Second Roles

Granting teachers a second supervisor role would have been the one-line fix, and it would
have poisoned everything it touched: audit entries showing a teacher's id with no hint of
which hat they wore, workload reports unable to separate teaching from cover, every policy
growing doubled role checks, and teachers accidentally unlocking industry-only screens.
Runtime delegation through the registration's MentorEntity avoids all five: one role per
user, per-method granularity, scope bounded by real mentorship, and the audit carrying
both identities. Middleware was never a candidate — it sees requests, not records, and
proxy is a question about a specific student's registration.

#### DD-SUPV-003 — A Command, Not a Pipeline

There was pressure, briefly, for a supervised worker that watches silence continuously
with retries, backoff, and its own dashboard. That machinery serves thousands of events a
minute; this domain produces a handful of gaps a day. One command, runnable by hand and
ticked by a single clock line, finds every gap the pipeline would find. If a future school
with ten thousand students proves otherwise, the command's logic graduates into a job —
until that evidence, the daemon stays unwritten.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Unverified visits confirmed or flagged after review cycle | 0 lingering unreviewed | VerifyVisitAction coverage in tests |
| Missing-entry gaps reaching coordinator without prior mentor notice | 0 skipped levels | Escalation ordering tests |
| Duplicate compliance notices per gap per day | 0 | Re-run command, count notices |
| Cross-mentorship reads succeeding | 0 | Boundary probe tests |
| Architecture violations (C1–C8, D1–D6) | 0 | `scan_violations.py` on the Journals module |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [placement](J9GBH-placement.md) | Active placement records — supervision scopes to a registration |
| [daily-activity](1KSWL-daily-activity.md) | Logbook entries and attendance — the records mentors review |

### Build Guide

With this spec built, mentors log sessions and visits, cover each other's silence with
audited proxy, and hear about missing entries while recovery is still possible. The daily
loop is fully closed: students write, mentors answer, the system watches the gaps.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [incident](3RU9S-incident.md) | Incident reports reference placement and supervision context |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the 3-day/5-day compliance thresholds suit every school; per-school tuning arrives only if coordinators ask for it | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Daily Operations phase; all specs Full
- [Daily activity](1KSWL-daily-activity.md) — the student record mentors oversee
- [Placement](J9GBH-placement.md) — registration and mentor assignment scope
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — MentorEntity bridge and proxy gates
- [Project initialization](QLHDO-project-initialization.md) — FR-GLB-002/008 role and authorization invariants
- [Architecture](D2FT3-architecture.md) — Action Triad, Entity/DTO boundaries
- [Cross-role proxy ADR](../adr/adr-cross-role-proxy.md) — delegation without second roles
- [Flat RBAC ADR](../adr/adr-flat-rbac-with-functional-roles.md) — five stored roles, derived functional roles
