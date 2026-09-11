# ZUFG8 — Handbooks

> **Spec ID:** ZUFG8
> **Status:** Full
> **Owner:** Document
> **Depends on:** [PKYX6](PKYX6-document-templates.md)

## Description

Every placement period opens with rules students must acknowledge: safety conduct, reporting
obligations, attendance discipline. This spec defines the handbook lifecycle — targeted
publishing to students, teachers, and industry supervisors, versioned re-issue when rules
change, and tamper-evident acknowledgment — plus the registration-document linkage through
which enrollment officers verify each student's required papers.

Template infrastructure and rendering live in [document-templates](PKYX6-document-templates.md);
final certificates live in [certification](J0M04-certification.md).

---

## 1. Problem Statements

### PS-1 — Rules That Never Reached Their Readers

A safety briefing updated after a workshop incident circulated as a forwarded file in a chat
group. Half the cohort never saw it, and when a second incident occurred the school could
not prove the updated rules had ever reached the students involved.
**→ Requirement:** FR-HAND-001/006 (targeted publishing, audience matching).

### PS-2 — Acknowledgments Without Evidence

Paper sign-off sheets go missing, chat replies of "siap, Pak" are unsearchable, and nobody
can answer the accreditation assessor's question: which students acknowledged version three
of the conduct handbook, and when.
**→ Requirement:** FR-HAND-010/011/012 (append-only acknowledgment with identity and timestamp).

### PS-3 — Enrollment Papers Without a Checklist

During intake, officers collect permits, consent forms, and medical notes per student across
loose files. A missing consent surfaces only on departure day, when the bus is already
waiting.
**→ Requirement:** FR-HAND-014/015/016 (registration-document linkage with verification state).

---

## 2. Goals & Non-Goals

### Goals

- **Targeted handbook publishing** — each handbook addresses all readers, students, teachers, or supervisors exactly. *Why:* safety rules must reach their readers, not a generic file share.
- **Versioned re-issue with re-acknowledgment** — rule changes produce a new version that resets acknowledgment expectations. *Why:* proves the updated rules reached everyone after an incident.
- **Tamper-evident acknowledgment trail** — every acknowledgment is append-only with actor, timestamp, and request context. *Why:* answers the assessor's question with evidence instead of memory.
- **Enrollment verification checklist** — each registration shows which required documents are pending, verified, or rejected with officer notes. *Why:* missing papers surface during intake, not on departure day.
- **Bilingual handbook content** — Indonesian primary with English mirror for every reader-facing string. *Why:* supervisors and bilingual programs read the same rules without drift.

### Non-Goals

- **Certificate issuance and verification**. *Why:* owned by [certification](J0M04-certification.md).
- **Grade cards and official correspondence**. *Why:* owned by [reports](R6BMW-reports.md) and [official-documents](7H5D6-official-documents.md).
- **External object storage for handbook files**. *Why:* local disk under upload validation covers single-tenant deployment.

---

## 3. User Stories / Use Cases

Admins publish handbooks, readers acknowledge them, and enrollment officers clear document
checklists. The table lists each journey once; the groups below narrate them.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-HAND-001 | Admin publishes and retires handbooks with audience targeting and file upload | P0 | F | Full |
| UC-HAND-002 | Student and supervisor read targeted handbooks and record acknowledgment | P0 | F | Full |
| UC-HAND-003 | Admin verifies registration documents against a per-student checklist | P0 | F | Full |

### 3.1 Publishing and Reading

#### UC-HAND-001 — Admin publishes a handbook

After the workshop incident the conduct officer rewrites the safety chapter, uploads the new
PDF, addresses it to students and supervisors, and publishes it as the next version. The
previous version retires from listings but remains readable for the audit trail, and the
dashboard cache clears before the response returns so no reader keeps seeing stale content.
What the admin experiences is a single form — title, audience, description, file — with the
versioning and cache discipline handled underneath.

#### UC-HAND-002 — Reader acknowledges the current version

A student opening the handbook list sees the conduct handbook marked unread, opens the
rendered content, and confirms acknowledgment with one action. The entry records who
acknowledged which version and when, with request context attached, and the list
immediately reflects the new state. When version four appears next month, the same
handbook returns to unread, because acknowledging an old version must never satisfy a new
one. A supervisor follows the identical journey for supervisor-targeted handbooks.

### 3.2 Enrollment Verification

#### UC-HAND-003 — Officer clears a student's papers

An enrollment officer opens a registration and sees the checklist: permit verified, consent
pending, medical note rejected with a note explaining the blurry scan. She verifies the
re-uploaded consent in place, and the timestamp and verifier identity attach automatically.
At a glance the cohort board shows which registrations are fully cleared and which still
wait, so departure-day surprises stop happening.

---

## 4. Functional Requirements

Handbook writes travel through Command Actions with transactions, validation, and audit
logging; business predicates live on the entity snapshot; acknowledgment is append-only.
The table is the complete contract; the groups below narrate each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-HAND-001 | Admin creates, updates, and deletes handbooks through Command Actions accepting validated data objects | P0 | F | Full |
| FR-HAND-002 | Handbook versions accumulate immutably; publishing a revision creates a successor and retires the predecessor | P0 | F | Full |
| FR-HAND-003 | Handbook audience is drawn from a fixed vocabulary covering all readers, students, teachers, and supervisors | P0 | U | Full |
| FR-HAND-004 | Handbook uploads are validated on MIME type, per-module size limit, and filename safety, stored outside the web root under non-guessable names | P0 | F | Full |
| FR-HAND-005 | Handbook lifecycle events dispatch on create, update, and delete with synchronous cache invalidation | P1 | F | Full |
| FR-HAND-006 | Handbook visibility resolves by audience match so each role sees exactly its addressed handbooks plus all-reader ones | P0 | F | Full |
| FR-HAND-007 | Handbook business predicates live on an immutable entity snapshot bridged from the stored record | P0 | U | Full |
| FR-HAND-008 | Handbook deletion is refused while acknowledgments reference it; removal cleans the stored file | P1 | F | Full |
| FR-HAND-009 | Handbook authorization is enforced at Policy gatekeeping and Action business-rule layers | P0 | A | Full |
| FR-HAND-010 | Reader acknowledgment records actor, handbook version, timestamp, and request context as an append-only entry | P0 | F | Full |
| FR-HAND-011 | Duplicate acknowledgment of the same version is idempotent and never double-counts | P1 | F | Full |
| FR-HAND-012 | A new handbook version resets acknowledgment expectations for its audience | P0 | F | Full |
| FR-HAND-013 | Acknowledgment queries resolve efficiently through indexed lookups at cohort scale | P1 | F | Full |
| FR-HAND-014 | Registration-document linkage records per-student document state with officer notes, verifier, and timestamp | P0 | F | Full |
| FR-HAND-015 | Registration-document pairs are unique and carry indexed status for cohort-wide missing-paper queries | P0 | A | Full |
| FR-HAND-016 | Verification transitions validate state, record the acting officer, and reject illegal jumps with a translatable message | P0 | F | Full |
| FR-HAND-017 | Handbook and verification mutations validate server-side through form and data-object rules sharing entity constraints | P0 | A | Full |
| FR-HAND-018 | Every reader-facing handbook string passes through the translation helper with mirrored Indonesian and English keys | P0 | A | Full |
| FR-HAND-019 | Handbook and acknowledgment mutations write dual-channel audit entries with PII masking | P0 | F | Full |
| FR-HAND-020 | Handbook storage uses ordered UUID keys with explicit delete behaviour and composite indexes | P0 | A | Full |
| FR-HAND-021 | Handbook classes extend their layer bases with strict typing and a single-entry Action surface | P0 | A | Full |

### 4.1 Handbook Lifecycle

#### FR-HAND-001 — Command-driven publishing

The handbook manager delegates every write to its Command Action, which validates the data
object, persists inside a transaction, and announces the outcome through a domain event.
The component itself never issues a query that writes; it maps the Action response into
feedback. That separation is what keeps the enrollment-week rush from producing
half-persisted handbooks when two admins publish within the same minute.

#### FR-HAND-002 — Revisions succeed, never overwrite

When the safety chapter changed after the incident, the school needed two facts to stay
true simultaneously: new readers see the new rules, and last period's acknowledgments
still refer to the old wording they actually signed. Successor-versioning keeps both
true. The predecessor retires from selection while remaining retrievable, so a dispute
about what version three said is settled by opening version three, not by reconstructing
memory.

#### FR-HAND-003 — Fixed audience vocabulary

Four audience values cover every targeting need the school has expressed, and fixing them
as enum cases keeps addressing exact. A handbook for supervisors never leaks into the
student list because of a typo, and the bilingual labels render consistently in both
languages. Broadening the vocabulary later is a reviewed change, not a free-text field
someone fills differently each semester.

#### FR-HAND-004 — Files that cannot escape validation

The uploaded handbook PDF passes MIME sniffing, the per-module size cap, and filename
normalization before anything touches disk, and the stored name is non-guessable under a
directory outside the web root. Serving happens only through an entity-derived route, so
guessing a URL never yields another cohort's handbook. The checks run in the Action as
well as the form, closing the crafted-request bypass that UI-only validation leaves
open.

#### FR-HAND-005 — Events that clear before returning

Publishing dispatches a lifecycle event, and the dashboard cache listener clears
synchronously within the same request. Synchronous matters here: a deferred clear would
leave a window where a student opens the dashboard and still sees yesterday's rules
after the officer announced the update. The event also gives notifications and audit
fan-out a single attachment point instead of scattered inline calls.

### 4.2 Visibility and Rules

#### FR-HAND-006 — Each role sees its own rules

A student and a supervisor opening their handbook lists see different contents because
visibility resolves through audience matching against the reader's role, with
all-reader handbooks appearing in both. The matching predicate is unit-tested against
every role without a database, which is why it lives on the entity rather than inside
a query scope someone might bypass. Misaddressed rules — safety instructions visible
only to teachers — become impossible by construction rather than by careful querying.

#### FR-HAND-007 — Predicates on the snapshot

Availability, audience match, version comparison against an acknowledgment, and
deletion safety all read from the immutable entity snapshot instead of live model
state. Snapshots freeze the inputs, so a predicate evaluated during a request cannot
change meaning halfway through when another admin publishes concurrently. Schema
evolution touches only the bridge that builds the snapshot, leaving every consumer of
the predicates untouched.

#### FR-HAND-008 — Deletion with a conscience

Deleting a handbook that students already acknowledged would orphan the evidence the
assessor relies on, so the rules refuse it while references exist, explaining the
refusal in the admin's language. When deletion is legitimate, the stored file goes
with the record inside the same boundary, leaving neither a row without a file nor a
file without a row. Retirement, not deletion, remains the normal end of life.

#### FR-HAND-009 — Two gates on every write

Reading stays open to the addressed audience, while creating, updating, and deleting
stay admin-only through both the route Policy and the in-Action business-rule check.
The pairing exists for the same reason as everywhere else in the system: links get
forwarded, requests get replayed, and either gate alone can be sidestepped. Together
they turn a guessed URL into a forbidden page and a forged call into a rejected
message.

### 4.3 Acknowledgment

#### FR-HAND-010 — Evidence in every entry

The acknowledgment entry carries the reader, the exact handbook version, the timestamp,
and the request context, written once and never updated. That shape answers the
assessor directly: this student, this version, this moment. Because the store is
append-only, later corrections arrive as new entries rather than edits, so the trail
shows what was claimed and when, even if a reader acknowledges twice across devices.

#### FR-HAND-011 — Double taps count once

Students acknowledge from phones on unstable connections, and retried requests arrive
twice. The second arrival for the same reader and version resolves to the existing
entry instead of creating a twin, so compliance counts stay exact and the audit trail
stays clean. Idempotency here is a correctness property, not a convenience: inflated
acknowledgment counts would mislead an accreditation review.

#### FR-HAND-012 — New version, new obligation

Publishing version four re-opens the handbook as unread for its entire audience, no
matter how promptly everyone acknowledged version three. The version comparison
predicate makes this automatic: an acknowledgment satisfies only the version it names.
The school that updated its safety rules after an incident can therefore prove not
just that rules were published, but that every current student confirmed the new ones.

#### FR-HAND-013 — Lookups that scale with the cohort

The acknowledged-or-not indicator on each handbook row resolves through indexed
queries that stay flat as the cohort grows past a thousand students. Without the
index discipline, the morning ritual of every student opening the list at once would
turn into a full-table scan per reader. The failure being prevented is invisible at
pilot scale and crippling at production scale.

### 4.4 Registration Documents

#### FR-HAND-014 — A checklist per student

Each student's required papers live as explicit linkage rows carrying state, officer
notes, verifier identity, and verification time. The row is the checklist item: pending
when requested, verified when accepted, rejected with a reason when sent back. An
officer opening a registration sees the whole picture at once instead of hunting
through chat attachments, and the cohort board aggregates the same rows into
missing-paper counts.

#### FR-HAND-015 — Uniqueness with fast status cuts

The pair of registration and document is unique, so a second upload for the same
requirement updates the existing checklist item instead of spawning a duplicate. A
composite index over registration and status makes the two queries officers run
constantly — what is missing for this student, what is pending across the cohort —
resolve without scanning. Duplicates and slow boards both disappear through schema
rather than through discipline.

#### FR-HAND-016 — Transitions that explain themselves

Moving a document from pending to verified records who acted and when; attempting an
illegal jump, such as verifying without an uploaded file, is rejected with a message
in the officer's language rather than a silent no-op. The strictness protects the
checklist's meaning: verified must always mean an officer saw an acceptable file,
never that a state field drifted.

### 4.5 Cross-Cutting Contracts

#### FR-HAND-017 — Validation in one home

Form objects and request classes share the entity's rule definitions instead of each
declaring their own, so the title-length limit or the audience-required rule cannot
drift between the admin form and the Action boundary. Invalid payloads are rejected
before persistence in both paths, and the shared rules are unit-tested once rather
than asserted separately per entry point.

#### FR-HAND-018 — Two languages for every rule

Safety instructions read by students and supervisors render through the translation
helper with mirrored catalogues, and names travel as placeholders so sentence
structure survives between languages. An untranslated key on a conduct handbook reads
as an institutional mistake and erodes the authority of the rules themselves, which
is why catalogue completeness is a contract rather than an aspiration.

#### FR-HAND-019 — Audited without leaking

Publishes, acknowledgments, and verifications each write dual-channel entries with
actor identity and change summary, masked before either sink so student names never
leak into operational logs. The activity channel gives assessors their queryable
trail; the system channel gives operators their debuggable stream. Both carry the
same masked payload by construction.

#### FR-HAND-020 — Keys that join cleanly

Linkage rows reference ordered UUIDs with explicit delete behaviour — removing a
registration cascades its checklist, removing an officer nulls the verifier without
destroying history — and composite indexes keep cohort queries fast. Uniform key
discipline is what lets the period-close routines join handbooks, acknowledgments,
and registrations without type-mismatch failures.

#### FR-HAND-021 — Bases and boundaries

Handbook classes extend their layer bases under strict typing, Actions expose a
single entry point, and the entity stays free of persistence calls. Reviewers never
negotiate these properties per file; the contract scan asserts them across the
module, which keeps a small feature consistent with the rest of the system without
relying on memory.

---

## 5. Non-Functional Requirements

Constraints that keep acknowledgment trustworthy and listings usable. Each row names its
target; the groups below narrate the reasoning.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-HAND-001 | Handbook writes are unreachable without authorization at both Policy and Action layers | N/A | P0 | A | Full |
| NFR-HAND-002 | Acknowledgment entries are append-only and never mutated or removed by application code | N/A | P0 | A | Full |
| NFR-HAND-003 | Acknowledgment state lookups stay indexed and bounded at full-cohort concurrency | N/A | P1 | F | Full |
| NFR-HAND-004 | Published handbook changes become visible to readers within the same request cycle | N/A | P1 | F | Full |
| NFR-HAND-005 | Uploaded handbook files are served only through authorized routes, never by direct path | N/A | P0 | A | Full |
| NFR-HAND-006 | Indonesian and English handbook strings remain complete and mirrored across releases | N/A | P1 | A | Full |

### 5.1 Trust and Protection

#### NFR-HAND-001 — Authorization without bypass

Every handbook and verification mutation is asserted unreachable without the dual
authorization layers. Forwarded admin links and replayed component calls are the
threats being closed; the guarantee turns both into rejections rather than incidents,
and the assertion runs as a structural scan rather than a manual checklist.

#### NFR-HAND-002 — The trail cannot be rewritten

No application path updates or deletes an acknowledgment entry; corrections arrive as
new entries. The property is what makes the trail evidence instead of assertions: an
assessor can trust that the record of who confirmed which version reflects history
rather than the latest edit. Verification inspects the write surface for any
mutation path and finds none.

#### NFR-HAND-003 — Reads that survive the morning rush

When the whole cohort opens the handbook list before first period, each
acknowledged-or-not indicator resolves through indexed lookups with bounded query
counts. The discipline is invisible in testing with dozens of rows and decisive with
thousands, which is exactly why it is fixed as a constraint rather than left to
operational luck.

### 5.2 Freshness and Language

#### NFR-HAND-004 — Publishes visible immediately

The synchronous cache clear means the officer announcing new safety rules and the
student opening them seconds later see the same version. A deferred clear would open
a window where the announcement and the content disagree — the precise window in
which incidents produce liability. Same-request visibility closes it.

#### NFR-HAND-005 — Files behind authorization

Stored handbook files are unreachable by direct path guessing; only the authorized
route serves them after resolving the reader's access. The guarantee matters because
handbooks carry internal disciplinary procedures the school does not publish
broadly. Path design and route gating together keep them addressed rather than
public.

#### NFR-HAND-006 — Two languages, no drift

Release checks assert both catalogues cover every reader-facing key. A conduct rule
rendered as a raw key in one language undermines compliance more than a missing
page would, because readers assume the system itself is unreliable. Mirrored
catalogues keep institutional authority intact in both languages.

---

## 6. API / Data Contracts

### 6.1 HandbookEntity

```
HandbookEntity extends BaseEntity (final readonly)
  Fields: id string, title string, version int, isActive bool,
    audience HandbookAudience, description ?string, hasFile bool, createdAt ?Carbon
  Bridge: Document.asHandbook(): HandbookEntity via fromModel()
  Predicates: isTargetedAt(?User): bool, isNewerThan(?Activity): bool,
    isAvailable(): bool, canBeDeleted(): bool
```

### 6.2 HandbookData DTO

```
HandbookData extends BaseData (final readonly)
  Fields: title string, audience HandbookAudience, description ?string,
    isActive bool, file ?UploadedFile
```

### 6.3 HandbookAudience Enum

```
HandbookAudience: string, implements LabelEnum
  ALL = all, STUDENT = student, TEACHER = teacher, SUPERVISOR = supervisor
```

### 6.4 Actions, Events, Listeners, Routes

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| CreateHandbookAction | BaseCommandAction | HandbookData | Document |
| UpdateHandbookAction | BaseCommandAction | Document, HandbookData | Document |
| DeleteHandbookAction | BaseCommandAction | Document | void |
| AcknowledgeHandbookAction | BaseCommandAction | Document, User | void |

| Event | Dispatched by |
| ----- | ------------- |
| HandbookCreated | CreateHandbookAction |
| HandbookUpdated | UpdateHandbookAction |
| HandbookDeleted | DeleteHandbookAction |

| Listener | Events | Queued |
| -------- | ------ | ------ |
| ClearHandbookCache | HandbookCreated, HandbookUpdated, HandbookDeleted | No (synchronous) |

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| GET /admin/handbooks | HandbookManager (Livewire, BaseRecordManager) | auth, role:admin |
| GET /student/handbooks | StudentHandbookList (Livewire) | auth, role:student |
| GET /admin/documents/{document}/render/{registration} | DocumentRenderController@show | auth, role:admin |
| GET /admin/documents/{document}/render/{registration}/save | DocumentRenderController@store | auth, role:admin |

### 6.5 Database Schema

```
registration_documents:
  id: uuid PK
  registration_id: foreignUuid → registrations.id cascadeOnDelete
  document_id: foreignUuid → documents.id cascadeOnDelete
  status: string default 'pending', indexed
  admin_notes: text nullable
  verified_by: foreignUuid → users.id nullOnDelete nullable
  verified_at: timestamp nullable
  timestamps
  Unique: (registration_id, document_id)
  Indexes: (registration_id, status)
```

The `documents` table itself is defined in
[document-templates](PKYX6-document-templates.md) §6.5.

---

## 7. Design Decisions

Recorded choices with their context. The table lists each decision once; the groups below
carry the narrative.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-HAND-001 | Handbook rules live on a dedicated immutable entity rather than on the shared document model | P0 | — | — |
| DD-HAND-002 | Acknowledgments are recorded in the append-only activity trail rather than a dedicated table | P0 | — | — |
| DD-HAND-003 | Cache invalidation runs synchronously in-request rather than deferred | P1 | — | — |

### 7.1 Rules and Evidence

#### DD-HAND-001 — Rules beside the model, not inside it

The shared document model serves templates, reports, and handbooks; growing
handbook-specific predicates onto it would tangle three concerns in one class and
drag business logic into the persistence layer. A dedicated immutable entity keeps
targeting, availability, and deletion-safety together, testable without a database,
with the bridge as the single seam. One extra class buys the separation the whole
architecture depends on.

#### DD-HAND-002 — The activity trail as acknowledgment store

A dedicated acknowledgment table would duplicate exactly what the activity trail
already provides: append-only rows with actor, timestamp, and request context,
queryable by event and subject. Reusing the trail keeps compliance evidence in the
same store assessors already query, with IP and agent context attached for free. A
purpose-built table would add schema without adding information.

### 7.2 Freshness

#### DD-HAND-003 — Clearing cache before responding

Deferring the dashboard clear would trade a few milliseconds of request time for a
window of stale rules visible after every publish — the worst possible trade for
safety content. Synchronous clearing keeps the publish announcement and the reader
view consistent within the same cycle. The cost is bounded and constant; the window
it closes is a liability matter.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Handbook visible to the wrong audience | 0 | Audience-match review across roles |
| Acknowledgment missing actor, version, or timestamp | 0 | Entry-shape audit |
| Duplicate registration-document linkage | 0 | Unique-constraint review |
| Stale handbook visible after publish | 0 | Synchronous-clear review |
| Unauthorized handbook mutation | 0 | Policy plus Action authorization review |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [document-templates](PKYX6-document-templates.md) | Template repository, renderer pipeline, report infrastructure |

### Build Guide

Handbooks consume the template engine and precede certification: acknowledgment of the
current conduct handbook is part of graduate readiness, checked before certificates
issue.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [certification](J0M04-certification.md) | Handbook acknowledgment feeds graduate eligibility |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the activity trail's retention covers the accreditation evidence window | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Certification phase and build order
- [Spec template](../templates/spec-template.md) — section skeleton and ID rules
- [Document templates](PKYX6-document-templates.md) — renderer pipeline and reports
- [Certification](J0M04-certification.md) — graduate eligibility and issuance
- [File uploads and media](WQGTP-file-uploads-media.md) — upload validation and storage
- [RBAC and authorization](T4B26-rbac-and-authorization.md) — role model and policies
- [Logging and error handling](89SRA-logging-and-error-handling.md) — dual-channel logging and exception trees
