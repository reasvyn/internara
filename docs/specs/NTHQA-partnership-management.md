# Partnership Management — Lifecycle, MoU Documents & Renewal

> **Spec ID:** NTHQA
> **Status:** Full
> **Owner:** Partners
> **Depends on:** XI3LB

## Description

A partnership is the formal agreement between the school and one company: an MoU with a number,
a title, a validity window, a slot quota, and a lifecycle from active to expired or terminated.
This spec fixes that agreement layer — status transitions that only move forward, single-file
MoU documents with instant thumbnails, renewal that preserves history by creating new records,
quota caps that refuse overcommit, validity windows that gate placements, and the expiry watch
that warns administrators before agreements lapse. Company profiles underneath are defined in
[company-management](XI3LB-company-management.md).

---

## 1. Problem Statements

### PS-1 — Uncontrolled Status Edits Rewrite History

Without enforced transitions, an expired partnership gets "corrected" back to active the week
before accreditation, a terminated agreement is quietly deleted to hide a dispute, and
downstream placement code cannot trust the status column it reads. The lifecycle must move in
one direction — active toward expired or terminated — with each step explicit, auditable, and
blocked at both the Action and the entity.
**→ Requirement:** FR-PART-003 (enum transition contract), FR-PART-008 (transition Actions),
FR-PART-006 (terminal-only deletion).

### PS-2 — MoU Paperwork That Cannot Be Found

The MoU is the legal fact the partnership claims: without exactly one retrievable document per
agreement, audits become phone calls to former coordinators asking who holds the signed copy.
Duplicate uploads accumulate across semesters while hundred-megabyte scans stall the listing
page — so the constraint must be one file, web-optimized, available the instant the row
renders.
**→ Requirement:** FR-PART-014 (single-file collection), FR-PART-015 (non-queued thumbnail),
FR-PART-016 (upload validation).

### PS-3 — Renewal That Erases What It Renews

When a three-year MoU lapses and the school re-signs with new terms, editing the old row's
dates destroys the original agreement's history — the old quota, the old signatories, the old
window, all gone exactly when an auditor asks what changed. Renewal must birth a new record
and retire the old one, with the MoU carried across inside a single transaction.
**→ Requirement:** FR-PART-011 (new-record renewal), FR-PART-012 (atomic MoU transfer),
FR-PART-013 (single-transaction orchestration).

### PS-4 — Quota Overcommit Discovered at the Placement Meeting

A supplier agrees to forty slots, two coordinators place students in parallel during
enrollment week, and the forty-first placement lands without anything objecting — discovered
only when the company calls to refuse the extra students. The cap must live in the entity as
a business rule that throws on overcommit, not as a dashboard number nobody enforced.
**→ Requirement:** FR-PART-005 (quota-cap guard with `RejectedException`), FR-PART-010
(validity and capacity gating for placements).

### PS-5 — Agreements That Lapse Silently

Partnerships end on fixed dates while placement planning runs months ahead: an agreement
expiring in three weeks with thirty students earmarked against it must surface now, not as an
expired flag discovered after the placements were promised. A daily watch with a configurable
threshold turns silent lapse into planned renewal.
**→ Requirement:** FR-PART-004 (expiring-soon predicate), FR-PART-018 (daily expiry command
with queued notifications).

---

## 2. Goals & Non-Goals

### Goals

- **Agreement CRUD with forward-only lifecycle** — create, read, update, and terminal-guarded delete through Command Actions, with active expiring or terminating but never resurrecting. *Why:* placement and certification read partnership status as ground truth.
- **Single-file MoU documents with instant thumbnails** — one document per agreement, web-optimized rendition generated synchronously. *Why:* the legal fact must be retrievable the moment the row renders, with no worker dependency.
- **History-preserving renewal** — the old record retires, a new record starts, the MoU transfers, all inside one transaction. *Why:* auditors ask what changed between agreement periods, and the answer must be two rows, not one edited row.
- **Quota caps enforced as business rules** — the entity refuses assignments past the cap with `RejectedException`. *Why:* overcommit discovered by an angry phone call from the company is a failure of the system, not of the coordinators.
- **Validity-window gating for downstream writes** — placements accepted only under active agreements whose windows contain the placement dates. *Why:* a placement under an expired MoU is a liability wearing a database row.
- **Proactive expiry watch** — a daily command notifies administrators of agreements approaching lapse. *Why:* renewal takes weeks of signatures; the warning must arrive while there is still time to collect them.

### Non-Goals

- **Company profile management**. *Why:* owned by [company-management](XI3LB-company-management.md); partnerships name companies, never edit them.
- **Student placement assignment**. *Why:* owned by Enrollment; this spec publishes the quota and window truth placements consume.
- **Multi-file MoU versioning or amendment tracking**. *Why:* amendments re-enter as renewals with new records, keeping one file per agreement and the history in the rows.
- **Automatic expiry transitions on a schedule**. *Why:* status changes stay manual and deliberate — only the warning is automated, so no agreement silently expires without an administrator's decision.
- **CSV import/export of partnerships**. *Why:* owned by the cross-cutting [csv-import-export](O2KCR-csv-import-export.md) pipeline.
- **Multi-format document pipelines beyond the thumbnail**. *Why:* one synchronous web rendition satisfies MVP display; conversion matrices are post-MVP depth.

---

## 3. User Stories / Use Cases

Six journeys: create with MoU, terminate, renew, batch delete, update, and replace the MoU.
Each is a browser-verifiable flow through the partnership manager.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PART-001 | Admin creates a partnership with its MoU and it starts active | P0 | B | Full |
| UC-PART-002 | Admin terminates an active partnership; placements already made are untouched | P0 | B | Full |
| UC-PART-003 | Admin renews a lapsed partnership into a new record with the MoU carried across | P0 | B | Full |
| UC-PART-004 | Admin batch-deletes partnerships; active rows survive and are reported | P1 | B | Full |
| UC-PART-005 | Admin updates partnership details without touching its lifecycle state | P1 | B | Full |
| UC-PART-006 | Admin replaces the MoU on an existing partnership; the old file vanishes | P1 | B | Full |

### 3.1 Agreement Lifecycle

#### UC-PART-001 — Sign and register a partnership

The coordinator returns from the supplier with a signed MoU numbered `012/MoU/SMK/2026`, a
July-to-June window, and a negotiated forty slots. She selects the company, enters the
agreement number, title, dates, and quota, attaches the scanned PDF, and saves. The create
Action validates the window and the quota, stores the row as active, files the document into
the single-file collection, and emits the created event — and the partnership appears in the
listing with its thumbnail already rendered, because the conversion ran synchronously rather
than waiting on a worker that a small school may not operate.

#### UC-PART-002 — Terminate an agreement mid-window

A dispute over supervisor availability ends the relationship in October, eight months into a
twelve-month MoU. The admin opens the partnership, presses Terminate, confirms, and the
status moves to terminated with a queued notification to the administrative group and a cache
clear behind it. The thirty students already placed under the agreement stay placed — their
history is facts about the past, unaffected by the relationship's present — while the thirty
first-years earmarked for spring must now be renegotiated elsewhere, which is exactly what
the freed quota math will show.

#### UC-PART-003 — Renew a lapsed agreement without losing its past

The three-year MoU with the textile factory lapses in June; in August the school re-signs
with fifty slots instead of forty and a new signatory on the company side. The admin opens
the expired row, presses Renew, adjusts quota and signatories, and saves. The old row stays
expired with its original number, dates, and terms — the accreditation folder's evidence
intact — while the new active row carries the fresh terms and the transferred MoU. One
transaction covers the retirement and the birth, so a failure anywhere leaves the registry
exactly as it was rather than half-renewed.

### 3.2 Maintenance and Documents

#### UC-PART-004 — Batch delete that spares the living

A inherited registry holds twenty stale rows mixed with six live agreements. The admin
selects all, confirms, and the batch Action evaluates each row's guard: the fourteen expired
and terminated rows delete, the six active ones survive, and the summary names both counts.
The active survivors are not an error to fix but the correct outcome — the journey's
assertion is that bulk cleanup can never be the way an active agreement dies.

#### UC-PART-005 — Edit details without disturbing state

The supplier's HR contact changes mid-year: new name, new phone, new email. The admin edits
those fields and saves, and the update Action persists the contact detail while leaving
status, window, quota, and history untouched. The journey guards the boundary between
"correcting facts" and "transitioning lifecycle" — contact edits must never be a back door
to resurrecting an expired agreement, and the update path enforces that by refusing status
fields entirely.

#### UC-PART-006 — Replace a blurry MoU scan

The original upload turns out to be a crooked phone photo, half-illegible. The admin opens
the partnership, uploads the proper flatbed scan, and the single-file constraint discards
the old document automatically while the fresh thumbnail generates in the same request. At
no point do two MoU files coexist for one agreement, and at no point does the listing show
a broken thumbnail — the replacement is atomic from the viewer's perspective, which is the
only perspective that matters for an audit.

---

## 4. Functional Requirements

Partnership behavior decomposes into schema, the status enum and state entity with quota caps,
CRUD plus transition Actions, validity gating, renewal orchestration, MoU handling, events and
the expiry watch, manager UI, policy, and audit. Every row below is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PART-001 | `partnerships` table uses a UUID v7 primary key with a cascading company key, unique agreement number, window dates, quota columns, default-active status, and contact/signatory fields | P0 | A | Full |
| FR-PART-002 | `Partnership` model extends `BaseModel` with `#[Fillable]` agreement fields, date and enum casts, the company relation, media handling, an entity bridge, and a factory | P0 | A | Full |
| FR-PART-003 | `PartnershipStatus` enum defines active, expired, and terminated with labels, terminal flags, and a forward-only transition map enforced through `canTransitionTo()` | P0 | U | Full |
| FR-PART-004 | `PartnershipState` entity is `final readonly`, exposes activity, expiry, expiring-soon, and quota predicates, and constructs only via `fromModel()` | P0 | U | Full |
| FR-PART-005 | The entity refuses quota overcommit: assigning past remaining capacity throws `RejectedException`, keeping every quota term non-negative | P0 | U | Full |
| FR-PART-006 | `PartnershipState::canBeDeleted()` permits deletion only in terminal states and is the single authority for deletion eligibility | P0 | U | Full |
| FR-PART-007 | Create and update Actions validate the DTO, enforce window and quota rules, persist inside transactions, and dispatch their events | P0 | F | Full |
| FR-PART-008 | Terminate and expire Actions validate activity through the entity, move the status forward exactly one legal step, and dispatch their events | P0 | F | Full |
| FR-PART-009 | Delete and batch-delete Actions enforce the terminal-state guard per row, throwing or skipping with named reasons, inside transactions | P0 | F | Full |
| FR-PART-010 | Placements are accepted only under active agreements whose windows contain the placement dates and whose remaining quota covers the request | P0 | F | Full |
| FR-PART-011 | Renewal refuses active source agreements, retires the old row to expired, and creates the new active row carrying forward terms and contacts | P0 | F | Full |
| FR-PART-012 | Renewal transfers the MoU document to the new record atomically: transfer failure voids the whole renewal | P0 | F | Full |
| FR-PART-013 | Multi-step partnership sequences run as one transaction emitting one terminal event, following Process orchestration semantics | P1 | F | Full |
| FR-PART-014 | The MoU lives in a single-file media collection: storing a document replaces any predecessor automatically | P0 | F | Full |
| FR-PART-015 | The thumbnail rendition generates synchronously at store time and serves listing and card views without a worker | P1 | F | Full |
| FR-PART-016 | MoU uploads validate MIME type and a 10 MB ceiling, store outside the web root, and serve through generated URLs | P0 | F | Full |
| FR-PART-017 | Each mutation dispatches its domain event after commit; termination additionally queues its notification and every event clears dashboard cache | P0 | F | Full |
| FR-PART-018 | A daily command warns administrators of agreements expiring within the shared configurable threshold, logging the check run | P1 | F | Full |
| FR-PART-019 | `PartnershipManager` joins the company table for a sortable company column and provides search, status filters, stats, and confirmations | P0 | F | Full |
| FR-PART-020 | `PartnershipPolicy` opens listing and viewing to admin group and teachers, restricts all writes to the admin group, and mirrors the entity guard on delete | P0 | U | Full |
| FR-PART-021 | Every partnership mutation writes a SmartLogger activity entry with actor identity and change summary, masking PII before either sink | P0 | F | Full |

### 4.1 Schema, Model, Enum, and Entity

#### FR-PART-001 — Agreement table with quota and window columns

The row carries what the negotiation produced: a cascading company key (a company deleted
means its agreements go with it — the guard upstream makes that deletion rare), a unique
agreement number for the legal identity, title and scope, the validity window, quota and
filled-quota counters, a status defaulting to active, contact and signatory fields, and
timestamps. The quota columns deserve emphasis: the company spec's available-slots
aggregation sums terms this table owns, so these two integers are load-bearing for the whole
enrollment phase, indexed and non-nullable rather than convenient afterthoughts.

#### FR-PART-002 — Thin model with casts, relation, media, and bridge

Date casts keep window comparisons honest, the enum cast keeps status a real
`PartnershipStatus` instead of a string the code must interpret, and the `company()`
relation plus media handling complete the persistence picture. The `asPartnershipState()`
bridge is the only doorway to rules, and the factory mints agreements across window
boundaries — active, expiring-soon, lapsed — so tests cover the lifecycle without
hand-computed date fixtures.

#### FR-PART-003 — Forward-only status contract on the enum

Active may become expired or terminated; expired and terminated may become nothing. The enum
carries this map in `validTransitions()`, answers terminal questions in `isTerminal()`, and
exposes the single decision point `canTransitionTo()` that both transition Actions consult.
Labels render through `__()` so the status badge reads correctly in both locales. An illegal
transition is not a validation message — it is a rejected impossibility at the type level,
which is what keeps a well-meaning "correction" from resurrecting history.

#### FR-PART-004 — Entity predicates for lifecycle, horizon, and capacity

`isActive()`, `isExpired()`, `isTerminated()` read the status; `isExpiringSoon()` with its
configurable thirty-day default reads the window against today for active rows only; the
quota predicates read remaining capacity. All construct through `fromModel()` on a `final
readonly` entity, which keeps every predicate a millisecond unit test — including the
boundary cases (expires today, expires in exactly thirty days, quota exactly full) that
date-and-integer logic always hides.

### 4.2 Quota Caps and Deletion Eligibility

#### FR-PART-005 — Overcommit refused at the entity boundary

The forty-first student against a forty-slot agreement meets `RejectedException` with a
translatable message naming the exhausted quota — thrown from the entity predicate, enforced
in the placement-accepting Action, and therefore identical whether the assignment came from
the UI, the bulk placer, or a console script. This is the quota-cap rule: the
cap is a business invariant evaluated in the entity, not a dashboard number. Because the
refusal precedes the write, `filled_quota` can never exceed `quota`, every remaining-capacity
term stays non-negative, and the company's aggregated headline number never contradicts the
rows beneath it.

#### FR-PART-006 — Terminal states as the only deletable states

Deletable means expired or terminated — the two states whose downstream consumers have
already stopped reading the row for live decisions. The policy, the delete Action, and the
batch flow all consult this single predicate, so an active agreement faces three unanimous
refusals instead of three independent opinions. The guard's severity matches the stakes: an
active agreement deleted mid-season strands every placement still counting on its window.

### 4.3 CRUD, Transitions, and Downstream Gating

#### FR-PART-007 — Creation and update with window and quota validation

The DTO requires company, agreement number, title, window dates, and quota; the create
Action validates the window's direction, the quota's positivity, and the agreement number's
uniqueness before persisting the row as active inside a transaction. Updates accept the same
DTO but refuse status fields outright — the lifecycle moves only through its dedicated
Actions, never as a side effect of editing a phone number. Both paths dispatch their events
after commit and audit through the activity channel.

#### FR-PART-008 — One legal step per transition Action

Terminate asks `isActive()`, checks `canTransitionTo(TERMINATED)`, and moves exactly that
step; expire does the same toward expired. Neither Action accepts a target state parameter,
because a parameterized transition is a generic status writer wearing a uniform — the two
dedicated Actions make the audit trail read "terminated" or "expired" as deliberate verbs
rather than string assignments. Each dispatches its named event so listeners distinguish a
dispute ending from a calendar ending.

#### FR-PART-009 — Deletion guards with per-row verdicts

Single delete throws `RejectedException` naming the active status when the guard fails;
batch delete evaluates every selected row, removes only terminal ones through the
single-delete path, and reports both counts with reasons. Transactions wrap each removal so
a row's deletion, its event, and its audit entry succeed together. The batch never aborts
wholesale here — unlike academic years, where abort protects history — because a mixed
registry of stale and live agreements is the normal cleanup shape, and the live rows'
survival is the expected outcome, not an error.

#### FR-PART-010 — Window and capacity gating for placements

The placement-accepting path resolves the named agreement, requires active status, requires
the placement dates to fall inside the agreement window, and requires remaining quota to
cover the request — failing any check with `RejectedException` before writing. The spring
cohort earmarked against an October-terminated MoU is refused in translatable language at
assignment time, not discovered as a liability at the audit. This row is the reason the
status, window, and quota columns exist as enforced truth rather than descriptive text.

### 4.4 Renewal Orchestration

#### FR-PART-011 — New record forward, old record retired

Renewal first refuses active sources — renewing a live agreement is almost certainly a
misclick, and the refusal says so. It then retires a non-terminal source to expired,
creates the new active row with the supplied terms while carrying forward scope, contacts,
signatories, and notes, and dispatches the renewed event. The old agreement number stays
with the old row; the new row takes a new number, preserving the uniqueness invariant and
giving auditors two distinct legal identities to compare.

#### FR-PART-012 — MoU transfer inside the same transaction

The document moves from old record to new through the media library within the renewal
transaction, so a transfer failure voids the retirement and the creation alike. A renewal
that produced a document-less new agreement alongside a retired old one would be the worst
outcome in this spec — the legal fact stranded on a historical row — and the atomicity
exists specifically to make that state unreachable.

#### FR-PART-013 — Process semantics for multi-step sequences

Renewal, termination-with-notification, and any future multi-step partnership sequence run
as one transaction emitting one terminal module event, with steps composed through
injection rather than smeared across the Livewire layer — the Process pattern from the
action-pattern ADR, applied whether or not the class name carries the `Process` suffix.
Partial failure handling is total rollback, never compensation choreography: at school
scale, "all of it or none of it" is both simpler and more correct than sagas. One terminal
event per completed sequence keeps listeners reasoning about outcomes instead of steps.

### 4.5 MoU Documents

#### FR-PART-014 — Exactly one document per agreement, structurally

The `mou_document` collection's single-file constraint enforces the cardinality at the
framework level: storing a replacement discards its predecessor with no Action code
involved and no orphaned file left behind. The crooked-phone-photo replacement story works
because the structure makes two coexisting MoU files unrepresentable, not because every
upload path remembered to delete the old one. Retrieval always reads the first (only)
media, so callers never choose among versions that cannot exist.

#### FR-PART-015 — Thumbnails without a worker dependency

The web rendition generates synchronously at store time, so the listing thumbnail renders
on the very first view after upload — critical for schools running without a queue worker,
where a queued conversion would sit unprocessed until someone noticed the broken images.
The format and width are fixed by the conversion registration, keeping every card and table
visually consistent without per-viewer decisions about what "thumbnail" means.

#### FR-PART-016 — Validated, bounded, non-public document storage

Uploads validate MIME type and the ten-megabyte ceiling before anything touches disk,
rejecting the executable-renamed-as-PDF and the uncompressed hundred-megabyte scan with
equal firmness. Files store outside the web root and serve through generated URLs, so no
document is ever directly addressable by guessing paths — the MoU's legal sensitivity
demands at least that much. The ceiling is configurable per the uploads contract, not
hardcoded into the form.

### 4.6 Events, Expiry Watch, Manager, Policy, and Audit

#### FR-PART-017 — Six events, two listeners, zero missed invalidations

Created, updated, deleted, terminated, expired, renewed — each dispatched after commit from
the Action that earned it, each carrying its row. The dashboard listener clears the
registered keys on all six, synchronously, because placement-season numbers cannot wait a
queue. The termination listener queues its notification instead, since mail delivery is
exactly the deferrable side effect queues exist for. Splitting the two by urgency rather
than by habit is what keeps the dashboard honest while keeping the request fast.

#### FR-PART-018 — The daily watch with a shared threshold

Every day the scheduled command queries active agreements whose end dates fall within the
threshold, dispatches a queued notification per expiring agreement to the administrative
group with title, company, end date, and detail link, and logs the run with its count —
including the zero-result run, which logs quietly at info level rather than alarming
anybody. The threshold lives in one configuration key shared with the entity's
`isExpiringSoon()`, so the dashboard badge and the mailed warning can never disagree about
what "soon" means. The status transition itself stays manual: the watch warns, humans
decide.

#### FR-PART-019 — Manager with company-aware listing

The partnership table joins companies to expose a sortable, filterable company-name column
— eager loading alone cannot sort at the database level, and collection-sorting a paginated
page sorts only the page. Search, status filters, the expiring-soon surfacing, per-row
stats, and confirmations for terminate, renew, delete, and bulk delete complete the
workspace where coordinators spend placement season. The MoU upload rides the standard file
upload trait, keeping document handling consistent with every other uploader in the system.

#### FR-PART-020 — Teachers read, admins write, guards agree

Listing and viewing extend to teachers — supervision planning needs the agreement terms —
while every write verb belongs to the admin group. Deletion additionally mirrors the
entity's terminal-state predicate, so the gate authorizes exactly what the Action would
permit and the dead-button contradiction cannot arise. The cross-role proxy mechanism stays
out of this policy: partnership writes are administrative acts, not mentorship acts, and no
proxy path needs to reach them.

#### FR-PART-021 — Dual-channel audit with PII masking

Agreements hold contact names, phones, and emails alongside quota negotiations — a payload
that must reach the audit trail but never a plaintext file. Every mutation writes its
SmartLogger activity entry with actor and change summary through PII masking first, per the
dual-channel ADR, and the renewal entry links both rows so the history reads across the
retirement. Unexpected failures take the system channel with full context while users see
the generic calm message, the same split every module honors.

---

## 5. Non-Functional Requirements

Lifecycle safety under enrollment-week concurrency, MoU handling a school without a worker
can rely on, and the structural hygiene the scans enforce. `Target` is `N/A` where
enforcement is architectural rather than measured.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PART-001 | Mutations are authorized at both the policy gate and the Action layer | N/A | P0 | A | Full |
| NFR-PART-002 | Active agreements are never deleted; guards hold at Action and entity layers | 0 active deletions | P0 | F | Full |
| NFR-PART-003 | Partnership routes require authentication with admin-group gates on writes | N/A | P0 | F | Full |
| NFR-PART-004 | MoU uploads validate MIME type with a 10 MB ceiling and store outside the web root | 10 MB max | P0 | F | Full |
| NFR-PART-005 | The thumbnail rendition generates synchronously with no worker dependency | 0 queued conversions | P1 | F | Full |
| NFR-PART-006 | Renewal is atomic across retirement, creation, and document transfer | all-or-nothing | P0 | F | Full |
| NFR-PART-007 | Batch operations report exact deleted and skipped counts with per-row reasons | per-row detail | P1 | F | Full |
| NFR-PART-008 | Refusals and validation feedback are real-time, inline, actionable, and announced to assistive technology | inline + announced | P1 | B | Full |
| NFR-PART-009 | Status display uses the enum label with text and icon, never color alone | text + icon | P1 | B | Full |
| NFR-PART-010 | The expiry threshold is configured once and shared by the entity predicate and the daily command | 1 config key | P1 | F | Full |
| NFR-PART-011 | All PHP files declare strict types; entities and DTOs stay `final readonly` and framework-pure | 0 violations | P0 | A | Full |
| NFR-PART-012 | Every user-facing string passes through `__()` with mirrored `en` and `id` keys, including enum labels | 0 missing keys | P0 | A | Full |

### 5.1 Lifecycle Safety

#### NFR-PART-001 — Two gates, no back door

The HTTP path meets the policy before the Action's business rule; direct Action calls from
seeders, console, and tests still meet the business rule. Termination and renewal are the
operations where a bypass would rewrite legal history, so both enforcement points carry
per-mutation tests rather than resting on the framework's goodwill.

#### NFR-PART-002 — The living are never deleted

The terminal-state predicate, evaluated in the Action and re-evaluated at the entity,
reduces active-agreement deletion to the impossible — not the forbidden-by-policy (which a
privileged caller might bypass) but the refused-by-rule (which no caller bypasses). The
batch path inherits the guarantee row by row, and the count of active deletions in
production is the metric that must stay at zero forever.

#### NFR-PART-003 — Authenticated reads, admin-group writes

Teachers read agreements for supervision planning; students never need the MoU layer at
all. Writes sit behind the admin-group middleware and matching policy abilities, forming
the route-component-policy defense depth the RBAC design requires. No layer assumes
another handled authorization — each verifies, because the one that assumed is the one the
incident report names.

### 5.2 Documents and Renewal Integrity

#### NFR-PART-004 — Bounded, typed, non-public documents

The ten-megabyte ceiling and MIME validation bound both malice and accident before disk is
touched, and storage outside the web root with generated-URL serving keeps every document
one capability away from enumeration. The MoU's legal weight justifies paranoia here that
would be excessive for an avatar: a leaked agreement exposes commercial terms, not just a
photo.

#### NFR-PART-005 — Thumbnails with no infrastructure ransom

A school running on shared hosting with no queue worker gets working thumbnails on day one
because the conversion runs inline at store time. Depending on a worker for the listing
page to render would hold basic functionality ransom to infrastructure the MVP never
promised — the synchronous choice costs milliseconds per upload and buys independence from
the queue entirely.

#### NFR-PART-006 — Renewal atomicity as the absence of horror states

Retirement without creation strands the school with no live agreement; creation without
retirement duplicates the legal identity; either without document transfer strands the
legal fact on the wrong row. The single transaction makes all three horror states
unreachable at once. Testing asserts the negative space: a forced mid-renewal failure must
leave the registry byte-identical to before, not merely "mostly fine."

#### NFR-PART-007 — Counts that close the loop

Deleted and skipped counts with per-row reasons turn batch day from archaeology into
bookkeeping: the admin knows exactly which agreements went, which survived, and why each
survivor did. The termination and expiry notifications carry the same completeness —
partnership title, company, end date, detail link — because a warning without an address is
a worry, not a work order.

### 5.3 Feedback, Thresholds, and Hygiene

#### NFR-PART-008 — Feedback where the work happens

Form validation responds as the admin types, errors attach to their fields with associated
labels, and live regions announce them to screen readers; guard refusals state the cause
and the remedy in translatable sentences. The coordinator renegotiating six agreements
before lunch meets guidance at every step, never a dead end — and the keyboard-only
colleague meets the same guidance, because announcement is built in, not bolted on.

#### NFR-PART-009 — Status readable without color vision

Active, expiring, expired, terminated — four states distinguished by text label plus icon,
never by hue alone. The expiring-soon badge pairs its warning color with the words and the
day count, since the coordinator triaging renewals needs "expires in 12 days" as data, not
as decoration. Meaning carried by color alone is inaccessible by definition, and agreement
states are too load-bearing for decoration.

#### NFR-PART-010 — One threshold, two consumers, no disagreement

The thirty-day default lives in a single configuration key read by both the entity
predicate and the scheduled command. Two independent thresholds would drift — the badge
warning at thirty days while the mail warns at fourteen — and the resulting confusion
("but the dashboard said…") would cost exactly the trust the expiry watch exists to build.
Override the key once, and badge, query, and mail move together.

#### NFR-PART-011 — Strict types and pure boundaries

Strict types on every file, `final readonly` entities and DTOs with no persistence imports —
the pre-commit scans fail the commit on drift. The quota and lifecycle predicates run as
millisecond unit tests only because this boundary holds; the boundary is also what lets the
expiry math be tested against fabricated dates without a database of agreements.

#### NFR-PART-012 — No hardcoded user strings, labels included

Every administrator-facing sentence — headers, toasts, confirmations, reports,
notifications — passes through `__()` with keys mirrored in both catalogs, and the enum's
`label()` resolves through the same helper so status badges translate with everything
else. The D3 scan proves it per commit, which is what lets renewal season run in
Indonesian while the documentation screenshots show English.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Partnership Model

```
App\Partners\Partnership\Models\Partnership
  Table: partnerships (UUID PK, v7; company_id FK → companies.id, cascadeOnDelete, indexed)
  Fillable: company_id, agreement_number (unique), title, start_date, end_date,
            status, quota, filled_quota, scope, contact_person_name,
            contact_person_phone, contact_person_email, signed_by_school,
            signed_by_company, signed_at, notes
  Casts: start_date→date, end_date→date, signed_at→date, status→PartnershipStatus
  Relations: company() BelongsTo Company
  Media: mou_document (singleFile), thumb rendition (synchronous)
  Bridge: asPartnershipState() → PartnershipState (FR-PART-002)
  Default: status = ACTIVE
  Factory: PartnershipFactory
```

### 6.2 PartnershipData DTO

```
App\Partners\Partnership\Data\PartnershipData extends BaseData (final readonly)
  Required: companyId, agreementNumber, title, startDate, endDate, quota
  Optional: scope, contactPersonName, contactPersonPhone, contactPersonEmail,
            signedBySchool, signedByCompany, signedAt, notes
```

### 6.3 PartnershipStatus Enum

```
App\Partners\Partnership\Enums\PartnershipStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: ACTIVE='active', EXPIRED='expired', TERMINATED='terminated'
  Methods: label(): string (__()-backed), isTerminal(): bool,
           validTransitions(): array, canTransitionTo(StatusEnum): bool
  Transitions: ACTIVE→[EXPIRED, TERMINATED], EXPIRED→[], TERMINATED→[] (FR-PART-003)
```

### 6.4 PartnershipState Entity

```
App\Partners\Partnership\Entities\PartnershipState extends BaseEntity (final readonly)
  Properties: status: PartnershipStatus, endDate: ?string, quota: int, filledQuota: int
  Factory: fromModel(Model)
  Methods:
    isActive() / isExpired() / isTerminated(): bool
    isExpiringSoon(thresholdDays = 30): bool — active && endDate within threshold
    remainingQuota(): int — quota − filledQuota, never negative (FR-PART-005)
    assertCapacity(int $requested): void — throws RejectedException on overcommit
    canBeDeleted(): bool — isExpired() || isTerminated() (FR-PART-006)
```

### 6.5 Actions

| Action | Base | Accepts | Returns | Event |
| ------ | ---- | ------- | ------- | ----- |
| `CreatePartnershipAction` | `BaseCommandAction` | `PartnershipData` | `ActionResponse` | `PartnershipCreated` |
| `UpdatePartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` | `PartnershipUpdated` |
| `DeletePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` | `PartnershipDeleted` |
| `TerminatePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` | `PartnershipTerminated` |
| `ExpirePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` | `PartnershipExpired` |
| `RenewPartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` | `PartnershipRenewed` |
| `BatchDeletePartnershipAction` | `BaseCommandAction` | `Collection` | `ActionResponse` | `PartnershipDeleted` per row |

### 6.6 Events and Listeners

| Event | Dispatched by | Carries |
| ----- | ------------- | ------- |
| `PartnershipCreated` | create Action | `Partnership` model |
| `PartnershipUpdated` | update Action | `Partnership` model |
| `PartnershipDeleted` | delete + batch Actions | `Partnership` model |
| `PartnershipTerminated` | terminate Action | `Partnership` model |
| `PartnershipExpired` | expire Action | `Partnership` model |
| `PartnershipRenewed` | renew Action | old + new `Partnership` models |

`ClearDashboardOnPartnershipChange` handles all six synchronously (cache forget);
`NotifyOnPartnershipTerminated` queues the termination mail (FR-PART-017).

### 6.7 Scheduled Command

```
partnerships:check-expiry — daily via the scheduler (FR-PART-018)
  Query: ACTIVE partnerships with end_date within config('partners.expiry_threshold_days', 30)
  Per hit: queued PartnershipExpiringNotification (title, company, end_date, detail link)
           to all admin and super_admin users
  Logging: SmartLogger partnership.expiry_check with expiring count (info on zero)
```

### 6.8 Policy and Routes

`PartnershipPolicy` extends `BasePolicy`: `viewAny`/`view` for the admin group plus
teachers; `create`/`update`/`delete` for the admin group, with delete mirroring
`canBeDeleted()` (FR-PART-020).

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/companies/partnerships` | `PartnershipManager` | `partners.partnerships` | `auth`, `role:super_admin\|admin` |

### 6.9 Database Schema

```
partnerships:
  id: uuid (PK, v7)
  company_id: foreignUuid → companies.id (cascadeOnDelete, indexed)
  agreement_number: string (unique)
  title: string
  start_date: date
  end_date: date
  status: string (default 'active', indexed)
  quota: integer (not null, ≥ 0 — negotiated slot capacity)
  filled_quota: integer (default 0 — assigned placements; guarded ≤ quota)
  scope: text (nullable)
  contact_person_name: string (nullable)
  contact_person_phone: string (nullable)
  contact_person_email: string (nullable)
  signed_by_school: string (nullable)
  signed_by_company: string (nullable)
  signed_at: date (nullable)
  notes: text (nullable)
  timestamps
```

---

## 7. Design Decisions

One table of the calls that shaped this spec. Each decision below is load-bearing: reversing
any of them reopens the incident or the drift it was written to close.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PART-001 | Renewal creates a new record and retires the old one instead of editing dates in place | P0 | — | — |
| DD-PART-002 | MoU documents use the single-file media collection with a synchronous thumbnail | P0 | — | — |
| DD-PART-003 | The manager joins companies for a sortable company column rather than sorting loaded collections | P1 | — | — |
| DD-PART-004 | Lifecycle and quota rules live in the state entity behind the model bridge; models stay persistence-only | P0 | — | — |
| DD-PART-005 | Deletion guards run at the Action and the entity against one shared terminal-state predicate | P0 | — | — |
| DD-PART-006 | Renewal and sibling multi-step sequences are single transactions with one terminal event | P0 | — | — |

### 7.1 History and Documents

#### DD-PART-001 — Two rows tell the truth one edited row cannot

In-place renewal was the original design, and it failed the first accreditation rehearsal:
asked what changed between the 2023 and 2026 agreements with the textile factory, the
system could show only the current row — the old quota, signatories, and window existed
nowhere. New-record renewal preserves both legal identities with their own numbers, at the
cost of a uniqueness discipline on agreement numbers and a historical row per renewal cycle.
The history-table alternative — versioning inside one row — was rejected as a second table
maintained for the marginal benefit over the simpler shape: the old row already is the
history.

#### DD-PART-002 — Cardinality enforced by structure, speed by synchronicity

The single-file collection makes "two MoU files, one agreement" unrepresentable rather than
forbidden-by-convention, which matters because upload paths multiply (create form, replace
flow, renewal transfer) and conventions leak across exactly such multiplications. The
synchronous thumbnail trades milliseconds per upload for independence from queue
infrastructure — the shared-hosting school with no worker sees rendered thumbnails on day
one. Multi-file versioning was weighed and set aside: amendments re-enter as renewals, so
versions live as rows, and the collection stays honestly singular.

#### DD-PART-003 — Sorting belongs to the database

Eager loading plus collection sorting produces the classic paginated lie: each page sorted
internally while the dataset sprawls unsorted across pages. The explicit join exposes a
real `company_name` column the database orders before paginating, at the cost of a slightly
more involved query builder. Partnerships without readable company context are meaningless
rows, and sorting by company is the primary way coordinators scan them — the join earns its
complexity every placement season.

### 7.2 Rules, Guards, and Atomicity

#### DD-PART-004 — The entity as the single court of appeal

Status questions, horizon questions, quota questions, deletion questions — all answered by
`PartnershipState`, all constructed through `fromModel()`, all unit-testable without a
database. The alternative history, business methods on the model, made every predicate a
database test and scattered schema knowledge across callers; the bridge concentrates the
translation where a column rename ripples exactly once. Framework pragmatism applies:
dates and collections may appear inside the entity, but persistence calls may not — the
rule stays evaluable in isolation from the write it guards.

#### DD-PART-005 — Two checkpoints against races and dead buttons

The Action's upfront status check gives the fast, specific refusal; the entity's
decision-time check closes the race where a concurrent request transitions the row between
check and delete; the policy's mirrored predicate keeps the gate authorizing exactly what
the Action permits. Three evaluations of a cheap question per deletion, against the two
failure modes that matter: an active agreement removed mid-season, and a button that
authorizes what the Action then refuses. A bare database constraint could prevent the
first but explain neither, which is why the guards live in code that speaks.

#### DD-PART-006 — Transactions, not choreography, for school-scale sequences

Renewal's three moves — retire, create, transfer — commit together or not at all, emitting
one `PartnershipRenewed` event on success. The saga-and-compensation machinery that larger
systems use for multi-step workflows would trade a simple rollback for orchestrated
reversals nobody at this scale needs; total rollback is both the failure handling and the
simplicity. The single terminal event keeps listeners reasoning about the completed
outcome, while the step-level events of the constituent moves stay available for the audit
trail's finer grain.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Illegal status transitions performed | 0 | Enum transition tests plus transition-action feature tests |
| Active partnerships deleted by any path | 0 | Guard tests on single, batch, and direct-Action paths |
| Quota overcommits accepted | 0 | Overcommit feature tests at and past the cap boundary |
| Placements under expired or lapsed agreements | 0 | Gating feature tests with out-of-window payloads |
| Renewals leaving partial state | 0 | Forced-failure rollback test asserting registry identity |
| Agreements per MoU file count diverging from one | 0 | Single-file constraint tests on replace and transfer |
| Expiring agreements unwarned before lapse | 0 | Expiry-command tests with threshold-boundary fixtures |
| Hardcoded user-facing strings | 0 | D3 scan on the module |

---

## 9. Roadmap

### Prerequisites

Company management ([XI3LB](XI3LB-company-management.md)) must be complete first, since every
partnership names a company counterparty, which itself rests on the school profile and
department specs beneath it.

### Build Guide

With this spec implemented, the system holds formal, quota-bearing, windowed agreements with
preserved history and a working expiry watch. Partnerships are the legal and capacity
foundation that internship programs schedule against and placements consume — the bridge
between the external DUDI world and the internal academic structure is now contractual
rather than verbal.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [internship-lifecycle](7C5WM-internship-lifecycle.md) | Internship programs scope to partnerships; program phases run inside partnership windows |
| 2 | [placement](J9GBH-placement.md) | Placement consumes the quota and window truth this spec publishes |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If two coordinators assign the final slot concurrently, both assignments can pass the quota check before either commits; at MVP scale this is accepted as rare and auditable, and a database-level guard is the documented escalation if placement contention ever proves otherwise | Open | Maintainer | — |
| A-1 | We assume the 10 MB MoU ceiling fits real scanned agreements; oversized scans are the school's to compress, not the system's to stream | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad, entity split, and event contracts this spec builds on
- [Spec-zero](QLHDO-project-initialization.md) — global requirements (FR-GLB-001/002/004/007/008/010) inherited here
- [Company management](XI3LB-company-management.md) — counterparty registry beneath every agreement
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — programs scheduled inside partnership windows
- [Placement](J9GBH-placement.md) — consumes quota and window truth published here
- [CSV import/export](O2KCR-csv-import-export.md) — cross-cutting bulk pipeline
- [ADR: action pattern over services](../adr/adr-action-pattern-over-services.md) — Command vs Process semantics for renewal
- [ADR: entity-model separation](../adr/adr-entity-model-separation.md) — why lifecycle and quota rules live in the entity
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` as the business-refusal voice
- [ADR: smartlogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — activity audit with PII masking
- [ADR: flat RBAC](../adr/adr-flat-rbac-with-functional-roles.md) — admin-group writes with teacher reads
- [ADR: MVP trim](../adr/adr-mvp-spec-trim.md) — deferred formats, fanout, and daemons recorded here
- [ADR: program closure & archival](../adr/adr-program-closure-archival.md) — terminal lifecycle handling downstream
