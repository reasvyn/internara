# Incident — Workplace Incident Reporting, Investigation & Resolution

> **Spec ID:** 3RU9S
> **Status:** Full
> **Owner:** Incident
> **Depends on:** J9GBH

## Description

The safety valve of the internship: any participant who witnesses a workplace incident —
accident, safety violation, harassment, disciplinary matter — files a structured report linked
to the placement, and admins carry it through investigation to a documented resolution. Where
the logbook records ordinary days, this spec records the days that must never repeat without
consequence.

---

## 1. Problem Statements

### PS-1 — Incidents Reported Into Thin Air

An intern injured on the workshop floor tells the site supervisor verbally; the account
reaches the school weeks later as hearsay, stripped of date, place, and sequence. Paper
notes and chat messages are losable by design — no structure, no persistence, no audit.
**→ Requirement:** FR-INC-001–007 (structured report linked to registration).

### PS-2 — Every Incident Shouts Equally Loud

A broken finger and a misplaced helmet arrive through the same verbal channel with the same
urgency: none, until someone decides to panic. Without severity classification, triage is
mood, not method.
**→ Requirement:** FR-INC-006 (type and severity enums).

### PS-3 — Investigations With No Paper Trail

Once reported, an incident enters a void: someone is "looking into it", nobody knows who,
and the resolution — if any — lives in memory. Schools cannot demonstrate due diligence
from memory.
**→ Requirement:** FR-INC-008–011 (guarded status workflow), FR-INC-012–014 (resolution record).

### PS-4 — Admins Learn About Danger Late

A critical report filed at night sits unread until the next school day because nothing
pushes it toward the people accountable. Attention arrives on the schedule of whoever
happens to check.
**→ Requirement:** FR-INC-015 (after-commit notification to responsible mentors).

---

## 2. Goals & Non-Goals

### Goals

- **Let anyone present file a report** — student, teacher, supervisor, or admin, against their placement. *Why:* witnesses outnumber role-holders; restricting creation to students would silence the people standing closest to danger.
- **Classify for triage** — type and severity on every report. *Why:* a critical safety hazard and a minor concern must never compete equally for attention.
- **Guard the investigation path** — reported, investigating, resolved, closed, with illegal jumps refused. *Why:* a workflow that can be skipped at will is decoration, not process.
- **Name every resolution** — who closed it, when, and with what outcome. *Why:* due diligence is a sentence with a subject, a date, and a result.
- **Push critical news to the accountable** — queued notification after the report commits. *Why:* a report nobody reads in time protects nobody.

### Non-Goals

- **Evidence file attachments**. *Why:* photo evidence on reports is real value but unshipped; descriptions carry the MVP while media handling follows the logbook pattern later.
- **Severity-based routing matrices**. *Why:* one notification to the responsible mentors covers MVP; per-severity channel fanout (SMS for critical, email for low) is preference machinery for later.
- **Auto-assignment of investigators**. *Why:* admins triage by hand at school scale; an assignment engine optimizes a queue that does not yet exist.
- **Immutable transition timeline**. *Why:* status guards plus resolution records prove the path; a separate append-only transition ledger is post-MVP depth.

---

## 3. User Stories / Use Cases

One report travels from the workshop floor to a filed resolution through five scenes: file,
investigate, resolve, escalate, survey.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-INC-001 | Witness files a structured incident report against their placement | P0 | F | Full |
| UC-INC-002 | Admin opens an investigation on a reported incident | P0 | F | Full |
| UC-INC-003 | Admin resolves an investigated incident with documented outcome | P0 | F | Full |
| UC-INC-004 | Admin raises an incident's severity as investigation reveals more | P1 | F | Full |
| UC-INC-005 | Admin surveys all incidents through a filterable, searchable list | P1 | F | Full |

### 3.1 Reporting and Investigation

#### UC-INC-001 — Witness Files a Report

The afternoon the press machine catches a student's glove, the student — or the supervisor
who saw it — opens the incident form while the details are still vivid. Date, type,
severity, what happened, where, and what was done on the spot go into the form; the
optional fields stay empty where memory is honestly blank. One submit persists the report
as reported and sets the notification in motion. By evening the mentors responsible for
that placement know, with the facts attached rather than retold.

#### UC-INC-002 — Admin Opens an Investigation

Morning brings the admin to the incident table, where the new report waits among the
older ones. Filters narrow the view — reported status, the placement, the severity — and
the admin opens the record, reads the account, and moves it into investigating. That
transition is the institution taking ownership: from this moment the incident has an
owner, and "nobody knew" is no longer available as an explanation.

#### UC-INC-003 — Admin Resolves With a Record

Investigation ends the way it should: with sentences. The admin writes what happened,
what was decided with the company, and what prevents recurrence, names themselves by
acting, and closes the case into resolved with timestamp and notes landing together.
A future coordinator facing a similar press-machine incident will find this resolution
and inherit its lessons instead of rediscovering them.

### 3.2 Triage and Oversight

#### UC-INC-004 — Admin Raises the Severity

What arrives as a medium report sometimes grows teeth: the clinic calls back, the injury
is worse than the first account said. The admin reopens the record and raises the
severity, and every subsequent view and notification reflects the new urgency. Severity
is editable precisely because first reports are written in shock; the system trusts the
investigation over the initial filing.

#### UC-INC-005 — Admin Surveys the Incident Landscape

Patterns hide in lists. The admin filters by type across the period and finds three
safety violations at the same company, or sorts by severity and sees the unresolved
critical still open. The table renders human labels from the enums in the reader's
language, so oversight never requires decoding stored values. This is where incident
data starts informing partnership decisions upstream.

---

## 4. Functional Requirements

Reports enter through a validated DTO into a Command; status moves only along published
edges; notifications leave only after the write commits. No Livewire component writes a
report directly.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-INC-001 | IncidentReport model contract: UUIDv7 PK, `#[Fillable]` whitelist, typed casts, registration/reporter/resolver relations | P0 | A | Full |
| FR-INC-002 | ReportIncidentAction accepts a validated DTO, creates the report, and returns the model | P0 | F | Full |
| FR-INC-003 | New reports default to REPORTED status | P0 | F | Full |
| FR-INC-004 | Reporter identity is stamped from the authenticated actor at creation | P0 | F | Full |
| FR-INC-005 | Every report links to a valid registration via cascading, indexed foreign key | P0 | F | Full |
| FR-INC-006 | IncidentType and IncidentSeverity enums with fixed case lists and bilingual labels | P0 | U | Full |
| FR-INC-007 | Description required; location and immediate action-taken optional at filing | P0 | F | Full |
| FR-INC-008 | IncidentStatus enum with REPORTED, INVESTIGATING, RESOLVED, CLOSED and an explicit transition map | P0 | U | Full |
| FR-INC-009 | CLOSED is terminal; no outgoing transitions exist or are honored | P0 | U | Full |
| FR-INC-010 | UpdateIncidentAction applies partial updates only along legal transitions | P0 | F | Full |
| FR-INC-011 | Admin incident table filters by status, severity, and type with search and sort | P1 | F | Full |
| FR-INC-012 | ResolveIncidentAction requires resolution notes | P0 | F | Full |
| FR-INC-013 | Resolution atomically stamps resolver, timestamp, notes, and RESOLVED status | P0 | F | Full |
| FR-INC-014 | RESOLVED closes to CLOSED only through the guarded update path | P1 | F | Full |
| FR-INC-015 | Queued incident notification dispatches after commit to the placement's responsible mentors | P0 | F | Full |
| FR-INC-016 | Every mutation validates server-side; raw payloads never reach create/update | P0 | A | Full |
| FR-INC-017 | Creation is open to any authenticated user; update and delete belong to admins; violations throw RejectedException | P0 | A | Full |
| FR-INC-018 | All user-facing strings use `__()` with mirrored locales; mutations audit-log with PII masking | P0 | F | Full |

### 4.1 Reporting

#### FR-INC-001 — IncidentReport Model Contract

The model declares its shape up front: UUIDv7 key, twelve whitelisted fillable fields,
casts that turn stored strings into type, severity, and status enums plus datetimes, and
three belongs-to links — the placement registration, the reporter, the resolver. Indexes
cover type, severity, status, and the registration-plus-status pair that every filtered
query leans on. Business meaning lives outside this file; the model's job is faithful
storage and honest relations.

#### FR-INC-002 — Report Creation Through a DTO

The creation Action receives a validated data object, not a raw array — the DTO is where
required-versus-optional is proven before the database is involved. Inside a transaction
the Action persists the report and returns the fresh model to the caller. Callers never
assemble reports field by field; there is exactly one construction site, and every
report in the table passed through it.

#### FR-INC-003 — Born Reported

Every report enters life in the reported state, without exception and without a
parameter to say otherwise. The default lives at both the schema and the Action layer,
so no code path can birth an incident already investigating or quietly resolved. The
initial state is a fact about process, not a choice offered to the filer.

#### FR-INC-004 — Reporter Stamped From the Session

The reported-by field is taken from the authenticated actor, never from form input. A
field the filer could set would let reports impersonate witnesses; a stamp from the
session cannot lie. Anonymous reporting is therefore structurally unavailable — every
account in the system names a person, and that person stands behind their report.

#### FR-INC-005 — Anchored to a Registration

An incident floats free of meaning unless it hangs on a placement: which student, which
company, which period. The registration foreign key is mandatory, cascades on
registration removal, and carries an index because nearly every query arrives through
this link. Cross-module reporting — incidents per company, per period, per cohort —
reads outward from this single anchor.

#### FR-INC-006 — Type and Severity Vocabularies

Five types (accident, safety violation, harassment, disciplinary, other) and four
severities (low, medium, high, critical), each with a bilingual human label. The type
tells the admin what kind of response to prepare; the severity tells them how fast.
Both enums implement the label contract so the UI never renders a raw stored value,
and adding a future type extends the enum rather than a string column's folklore.

#### FR-INC-007 — Required Core, Optional Circumstance

The description is mandatory — a report without an account of what happened is noise.
Location and immediate action-taken stay optional, because some incidents have no
meaningful place (a threatening message received off-site) and because a frightened
student filing at midnight may genuinely not know what was done. Admins complete these
details during investigation; the filing gate stays low so urgency is never taxed.

### 4.2 Investigation

#### FR-INC-008 — Status Enum and Transition Map

Four states, six legal moves: reported opens to investigating or jumps to resolved for
the open-and-shut; investigating resolves or closes; resolved closes. The map is a
method on the enum, consulted by every Action before persisting a move. Illegal jumps —
reported straight to closed, skipping every accountable step — die with the transition
named, which is exactly the sentence an auditor wants to find in the logs.

#### FR-INC-009 — Closed Means Closed

The terminal state honors no outgoing edges. Reopening a closed incident is not a
transition but a new report referencing the old one, so history never rewrites itself
into a different verdict. Tests assert the empty outgoing set directly against the
enum — the cheapest test in the suite guarding the most important property of the
workflow.

#### FR-INC-010 — Guarded Partial Updates

Admins edit severity, location, and details freely, but any status change inside an
update passes the same transition gate as a dedicated transition call. The guard runs
before persistence inside the transaction, so a rejected move leaves the record — and
its audit — untouched. This single choke point is why the workflow holds despite
offering a general-purpose edit Action.

#### FR-INC-011 — Filterable Incident Table

Status, severity, and type filters compose with text search and date/severity sorting,
all reactive. The table exists because triage is comparative: one critical among
twenty lows reads differently than one critical alone. Mentor scoping applies here as
elsewhere — the query fans out from supervised registrations, never from the whole
table with the hope that the UI hides the rest.

### 4.3 Resolution

#### FR-INC-012 — Notes Are the Price of Resolution

The resolve call without resolution notes is refused. This is the spec's simplest
sentence and its most load-bearing: it guarantees that no incident ever reaches
resolved with an empty explanation. "Fixed" is not a note; the validation demands
substance, and reviewers enforce the culture the validation cannot.

#### FR-INC-013 — Atomic Resolution Stamp

Resolver identity, timestamp, notes, and the resolved status land in one atomic write.
Partial resolution — status moved but nobody named, notes saved but timestamp missing —
is the failure this atomicity exists to prevent. A crash mid-resolve therefore leaves
a still-investigating incident rather than a resolved incident with holes, and retrying
the resolve is always safe.

#### FR-INC-014 — The Final Close

Resolved incidents rest before they close: the separate step to closed, taken later
through the guarded update, marks the moment the school considers the matter
administratively finished — follow-ups done, company correspondence filed. Keeping
this distinct from resolution preserves the useful question "resolved but not yet
closed, what remains?" that coordinators ask at period end.

### 4.4 Notification and Integrity

#### FR-INC-015 — Notify After the Write Commits

The notification is queued, addressed to the placement's responsible mentors — admins
and the assigned teachers — and dispatched only after the report transaction commits.
Ordering is the entire content of this requirement: a rolled-back report must never
produce a notification about an incident that does not exist, and a committed report
must never sit silent because delivery was coupled to a request that already
responded. The queued notification carries the report's type, severity, description,
and reporter, enough to triage without opening the app.

#### FR-INC-016 — Server-Side Validation Boundary

Form Requests guard the HTTP edge and the DTO guards the Action edge; between them no
unvalidated payload reaches persistence. The specific shape this forbids is the raw
array passed straight into creation — one extra key in that array, and a filer sets
their own status or reporter. Invalid input dies before the transaction opens, leaving
neither rows nor audit noise.

#### FR-INC-017 — Open Filing, Restricted Handling

Anyone authenticated may file — the witness principle. Only admins may update, resolve,
or delete — the accountability principle. Students therefore never edit or remove
reports, including their own: a report once filed belongs to the process, and
corrections arrive as admin-handled updates, never silent self-edits. Both layers
enforce this — policy at the gate, Action-level rejection inside — and violations
surface as readable rejections rather than server errors.

#### FR-INC-018 — Localized, Audited Mutations

Every string in the incident flow passes through the translation helper with keys in
both locale files, because incident reporters under stress read in their first
language or not at all. Every mutation writes a SmartLogger activity entry with PII
masked before the sink — reporter names stay, contact details and tokens do not. The
audit of an incident thus tells the full procedural story while leaking no personal
data beyond what the process itself requires.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-INC-001 | Status moves outside the transition map are rejected before persistence | 0 illegal transitions persisted | P0 | F | Full |
| NFR-INC-002 | Resolved incidents always carry resolver, timestamp, and notes together | 0 partial resolutions | P0 | F | Full |
| NFR-INC-003 | Every incident links to an existing registration | 0 orphan reports | P0 | F | Full |
| NFR-INC-004 | Rolled-back reports produce no notifications; committed reports always queue one | 0 ghost or silent reports | P0 | F | Full |
| NFR-INC-005 | Filtered incident queries resolve through the composite registration-status index | Indexed in migration (review gate) | P1 | A | Full |
| NFR-INC-006 | Incident UI renders enum labels in the reader's locale, never raw stored values | 0 raw values rendered | P1 | F | Full |

### 5.1 Workflow Soundness

#### NFR-INC-001 — No Illegal Move Persists

The transition gate is only real if hostile input cannot pass it. Attempting every
forbidden jump — reported to closed, resolved back to investigating, anything from
closed — must end in rejection with the record byte-identical to before. The
enumerated adversarial attempts in tests are the proof; the enum map is the law they
prove against.

#### NFR-INC-002 — Resolutions Arrive Whole

A resolution missing its author is rumor; missing its notes is silence; missing its
timestamp is timeless. The atomic write makes partiality a transaction failure rather
than a data state, and the measurable claim scans the table for it: no resolved row
may lack any of the three companion fields. Any such row found is a defect in the
write path, not an acceptable edge.

#### NFR-INC-003 — No Orphan Reports

The foreign key guarantees what application code merely intends: deleting the
constraint check from the Action still cannot orphan a row, because the database
refuses parentless registrations and cascades legitimate removals. Counting reports
without a live registration must always return zero — the join the auditors run first
and should never have to think about again.

### 5.2 Delivery and Presentation

#### NFR-INC-004 — Notifications Match Reality Exactly

Two failure directions, both unacceptable: a notification for a report that rolled
back sends mentors chasing a ghost, while a committed report with no queued
notification leaves danger unread. The after-commit dispatch closes both. Tests prove
it from both sides — forced rollback yields silence, successful filing yields exactly
one queued notification — and the queue's persistence carries the promise past
process restarts.

#### NFR-INC-005 — Filtered Queries Stay Indexed

Incident tables grow across periods, and the admin's filtered survey is the most-run
read in the module. The composite index on registration plus status, with single
indexes on type and severity, keeps those reads logarithmic rather than linear. The
gate is migration review rather than a latency budget: the indexes exist in schema,
so no load rig is needed to believe the queries will hold.

#### NFR-INC-006 — Humans Read Labels, Not Values

A severity column showing `critical` to an Indonesian coordinator is a small failure
of care repeated on every row of every survey. Enum labels render through the
locale, so the reader sees the word in their language with the stored value nowhere
in sight. Spot-checking the rendered table in both locales — labels present, raw
values absent — is the whole verification.

---

## 6. API / Data Contracts

### 6.1 IncidentReport Model

```
App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport
  Table: incident_reports (UUIDv7 PK)
  Fillable: registration_id, reported_by, incident_date, type, severity,
            description, location, action_taken, status,
            resolved_by, resolved_at, resolution_notes
  Casts: type → IncidentType, severity → IncidentSeverity, status → IncidentStatus,
         incident_date → datetime, resolved_at → datetime
  Default: status = IncidentStatus::REPORTED
  Relations: registration() BelongsTo Registration,
             reporter() BelongsTo User (reported_by),
             resolver() BelongsTo User (resolved_by)
  Indexes: type, severity, status, (registration_id, status) composite
```

### 6.2 Enums

```
IncidentType: string implements LabelEnum
  ACCIDENT='accident', SAFETY_VIOLATION='safety_violation',
  HARASSMENT='harassment', DISCIPLINARY='disciplinary', OTHER='other'

IncidentSeverity: string implements LabelEnum
  LOW='low', MEDIUM='medium', HIGH='high', CRITICAL='critical'

IncidentStatus: string implements StatusEnum
  REPORTED='reported', INVESTIGATING='investigating',
  RESOLVED='resolved', CLOSED='closed'
  Terminal: CLOSED
  Transitions: REPORTED → [INVESTIGATING, RESOLVED]
               INVESTIGATING → [RESOLVED, CLOSED]
               RESOLVED → [CLOSED]
               CLOSED → []
```

### 6.3 Actions and Notification

| Action | Base | Accepts | Returns | Side effect |
| ------ | ---- | ------- | ------- | ---------- |
| `ReportIncidentAction` | `BaseCommandAction` | validated DTO | `IncidentReport` | Queues `IncidentReportedNotification` after commit |
| `UpdateIncidentAction` | `BaseCommandAction` | `IncidentReport`, validated DTO | `IncidentReport` | Transition-guarded partial update |
| `ResolveIncidentAction` | `BaseCommandAction` | `IncidentReport`, validated DTO (notes required) | `IncidentReport` | Atomic resolution stamp |

```
IncidentReportedNotification (ShouldQueue)
  Trigger: ReportIncidentAction success, dispatched after commit
  Recipients: admin-role users and the placement's assigned teachers
  Payload: type, severity, description, reporter identity, registration link
```

### 6.4 Policy, Routes, Schema

```
IncidentReportPolicy
  viewAny: super_admin, admin, teacher, supervisor
  view:    admin, mentors of the registration, reporter
  create:  any authenticated user
  update:  admin
  delete:  admin
```

Routes: `GET /student/incidents/report` (`IncidentForm`, `auth` + student),
`GET /admin/incidents` (`IncidentManager`, `auth` + admin).

```
incident_reports:
  id: uuid PK
  registration_id → registrations.id (cascade, indexed)
  reported_by → users.id (nullable)
  incident_date: datetime
  type: string (indexed); severity: string (indexed)
  description: text; location: string (nullable); action_taken: text (nullable)
  status: string (default 'reported', indexed)
  resolved_by → users.id (nullable); resolved_at: datetime (nullable)
  resolution_notes: text (nullable)
  timestamps
```

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-INC-001 | One status enum with an explicit transition map governs the whole workflow | P0 | — | — |
| DD-INC-002 | Filing is open to every authenticated user; handling is restricted to admins | P0 | — | — |
| DD-INC-003 | Notifications dispatch queued after commit, never inline in the write transaction | P0 | — | — |
| DD-INC-004 | Location and immediate-action fields stay nullable at filing | P1 | — | — |

### 7.1 Workflow Shape

#### DD-INC-001 — The Map on the Enum

A four-state workflow with no branching tempted nobody toward a state-machine library,
and that restraint holds: one enum, one method listing legal moves, every Action
consulting it. The alternative — transition logic scattered across three Actions —
would let the Actions disagree about what investigating may become, and the
disagreement would surface as an auditor's finding. Centralizing the map means the
workflow has exactly one author, and future states extend the map rather than
renegotiating it in three places.

#### DD-INC-002 — Asymmetric Filing Rights

Opening creation to every role was the contested call. Restricting it to students
would have missed the supervisor who witnesses harassment of their intern and the
teacher who hears about it on a visit — the two people most likely to file the
reports that matter most. The asymmetry (open create, admin-only handling) balances
the volume risk: more reports arrive, but only accountable hands move them, and the
table's filters keep triage cheap. Volume without handling rights would be noise;
handling rights without volume would be blindness.

### 7.2 Delivery and Pragmatism

#### DD-INC-003 — After Commit, Through the Queue

An earlier cut dispatched the notification inside the write transaction — conceptually
"along with" the report. That ordering risks the ghost: transaction rolls back,
notification already gone, mentors alarmed over nothing. Dispatching after commit
through the queued notification inverts the risk profile — the report is durable
before anyone is told, and the queue's durability carries the message past crashes.
The small delay (seconds, while the worker picks it up) buys exactly-once alignment
between reality and announcement, the cheapest reliability this module will ever get.

#### DD-INC-004 — Forgiving First Reports

Requiring location and immediate action would produce confident-sounding fiction from
filers who do not actually know — a precise but invented location is worse than an
honest blank. Nullable fields invite truth: describe what you saw, skip what you did
not. Investigation exists to complete the picture, and a report filed in two minutes
with gaps beats a perfect report filed never because the form demanded the
unknowable.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Illegal status transitions persisted | 0 | Transition-gate tests on every forbidden jump |
| Partial resolutions (missing author/time/notes) | 0 | Table scan for incomplete resolved rows |
| Orphan reports without registration | 0 | Registration join audit |
| Ghost or silent notifications | 0 | Rollback-silence and commit-notify tests |
| Report-to-awareness delay | Same-day mentor awareness | Queued notification delivery on filing |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [placement](J9GBH-placement.md) | Active placement records — incidents anchor to a registration |

### Build Guide

With this spec built, any participant can report a workplace incident and admins can
carry it from filing to documented resolution with the responsible mentors notified.
Daily operations now cover the ordinary (logbook, attendance), the overseen (visits,
reviews), and the exceptional (incidents).

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assessment](ARDA6-assessment.md) | Competency assessment gains incident context for affected placements |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume admin triage keeps pace with open filing; if report volume overwhelms hand triage, severity pre-screening becomes a follow-up spec | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Daily Operations phase; all specs Full
- [Placement](J9GBH-placement.md) — registration anchor for every report
- [Supervision](2EHSE-supervision.md) — visits and reviews sharing the placement context
- [Project initialization](QLHDO-project-initialization.md) — FR-GLB-008/010 authorization and exception invariants
- [Architecture](D2FT3-architecture.md) — Action Triad, Entity/DTO boundaries
- [Exception hierarchy ADR](../adr/adr-exception-hierarchy.md) — RejectedException contract
- [SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md) — dual-channel audit with PII masking
- [Eloquent observers ADR](../adr/adr-eloquent-observers.md) — observer versus event selection
