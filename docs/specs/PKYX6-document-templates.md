# PKYX6 — Document Templates

> **Spec ID:** PKYX6
> **Status:** Full
> **Owner:** Document
> **Depends on:** [8NZAU](8NZAU-installation.md)

## Description

The Document module owns the official paperwork of the school: permit letters, parent consent
forms, internship applications, and the four administrative reports coordinators compile every
period. This spec defines the template repository with version discipline, the placeholder
contract that binds a template to live school data, and the canonical Blade plus DomPDF
pipeline that renders HTML previews and archival PDFs.

Handbook lifecycle and acknowledgment live in [handbooks](ZUFG8-handbooks.md); final
certificates live in [certification](J0M04-certification.md); grade cards and official
correspondence live in [reports](R6BMW-reports.md) and
[official-documents](7H5D6-official-documents.md).

---

## 1. Problem Statements

### PS-1 — Letterhead Drift Across Departments

Every semester the administration office discovers three slightly different permit letters in
circulation: one with last year's principal name, one with an outdated school address, one
copied from a neighbouring SMK with the wrong logo placement. Teachers copy-paste from
whatever file they received last, so no two departments produce the same letter.
**→ Requirement:** FR-DOC-001/002 (central repository, version discipline).

### PS-2 — Hand-Filled Variables Do Not Scale

A parent consent letter carries the student name, class, company, period dates, school name,
and principal signature block. Filling those by hand for 400 students means 2,400 manual
fields and a guaranteed crop of misspelled names the week before departure.
**→ Requirement:** FR-DOC-009/010/011 (placeholder contract, HTML render).

### PS-3 — Reports Rebuilt From Scattered Sources

Completion summaries, mentor evaluations, and participation overviews are assembled from
attendance exports, logbook counts, and memory. Two coordinators produce two different totals
for the same period, and neither can show how the numbers were derived.
**→ Requirement:** FR-DOC-015/016/018 (four report types, sourced generation).

---

## 2. Goals & Non-Goals

### Goals

- **Central template repository** — every official document originates from one versioned record. *Why:* ends letterhead drift and copy-paste formatting.
- **Placeholder contract binding templates to live data** — templates declare variables, the renderer resolves them from registrations and settings. *Why:* removes hand-filled fields at cohort scale.
- **Canonical PDF pipeline** — Blade HTML preview plus DomPDF archival PDF stored on local disk. *Why:* one predictable output path instead of per-feature renderers.
- **Four sourced administrative reports** — completion, performance, participation, mentor evaluation, each traceable to its source template. *Why:* coordinators stop rebuilding totals by hand.
- **Version discipline preserving historical accuracy** — issued documents keep rendering against the version they were born from. *Why:* a consent letter signed in March must not change meaning in June.

### Non-Goals

- **Certificate issuance and serial discipline**. *Why:* owned by [certification](J0M04-certification.md).
- **Final grade cards and official correspondence**. *Why:* owned by [reports](R6BMW-reports.md) and [official-documents](7H5D6-official-documents.md).
- **Collaborative real-time editing**. *Why:* single-admin authoring covers school scale; concurrent editing is version-control depth beyond MVP.
- **External object storage for generated PDFs**. *Why:* local disk outside the web root satisfies single-tenant deployment.

---

## 3. User Stories / Use Cases

An admin authors templates and generates reports; teachers and students read the active
documents addressed to them. The table lists every use case once; the groups below hold the
narrative for each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DOC-001 | Admin creates, edits, and retires official templates with version history preserved | P0 | F | Full |
| UC-DOC-002 | Admin generates one of four report types from a template and a live registration context | P0 | F | Full |
| UC-DOC-003 | Teacher and student open the active documents addressed to them and preview rendered output | P1 | F | Full |

### 3.1 Authoring and Generation

#### UC-DOC-001 — Admin authors a template

The night before a new period the admin opens the template list and finds the parent consent
letter still carrying the previous principal's name. She duplicates it into a new version,
corrects the signature block, and retires the old one without deleting it, because consent
letters signed last semester must keep rendering exactly as signed. The list shows both
versions with their numbers, the retired one greyed out and unselectable for new renders,
and the activity trail records who changed what. Nothing about the flow requires knowing the
storage layout; the admin only sees titles, versions, and active state.

#### UC-DOC-002 — Admin generates a period report

Completion week arrives and the coordinator needs the internship completion summary for the
principal's sign-off. He picks the report type, selects the approved template, and points it
at the period's registrations. The system resolves each student's name, company, and dates,
compiles the PDF, and stores it as a report record linked back to the template it came
from, so anyone auditing the numbers can open the source template and re-derive them. A
missing registration does not abort the whole batch; the failure is reported next to the
name while the rest complete.

### 3.2 Consumption

#### UC-DOC-003 — Teacher and student read active documents

A class teacher checking tomorrow's departure list opens the active permit letter on a phone
and sees the rendered HTML preview with current school metadata, not the draft the admin is
still editing. A student opening the same letter sees identical content because both read
through the same active-version gate. Inactive drafts never appear in either view, which is
what prevents last year's letterhead from resurfacing in a classroom chat group.

---

## 4. Functional Requirements

Every template mutation travels through a Command Action with transaction, audit log, and
event; reads travel through Read Actions or scoped queries; validation runs server-side
before any write. The table is the complete contract; the groups below narrate each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DOC-001 | Admin creates and updates templates through a Command Action accepting a validated DTO | P0 | F | Full |
| FR-DOC-002 | Template versions are immutable history; edits create a new version, never rewrite a retired one | P0 | F | Full |
| FR-DOC-003 | Template slug is unique and auto-derived from the title with collision suffix | P1 | U | Full |
| FR-DOC-004 | Only active template versions are selectable for rendering; retired versions stay readable for audit | P0 | F | Full |
| FR-DOC-005 | Document category enum carries the canonical cases including template, report, handbook, letter, permit, application, and policy | P0 | U | Full |
| FR-DOC-006 | Document model uses UUID v7 primary keys, whitelisted fillable attributes, and declared casts, scopes, and relations | P0 | A | Full |
| FR-DOC-007 | Template and report authorization is enforced at Policy gatekeeping and Action business-rule layers | P0 | A | Full |
| FR-DOC-008 | Template file attachments are validated on MIME type, per-module size limit, and filename safety, stored outside the web root under non-guessable names | P0 | F | Full |
| FR-DOC-009 | Template placeholder contract declares the canonical variable set resolved from registration context and school settings | P0 | F | Full |
| FR-DOC-010 | Unknown or unresolvable placeholders reject rendering with a translatable business-rule failure | P0 | F | Full |
| FR-DOC-011 | Renderer produces HTML preview from a template and its context object | P0 | F | Full |
| FR-DOC-012 | Renderer compiles resolved HTML to PDF through the canonical DomPDF pipeline | P0 | F | Full |
| FR-DOC-013 | Generated PDFs are stored on local disk under deterministic paths that expose no server internals | P0 | F | Full |
| FR-DOC-014 | Template content is sanitized before compilation so embedded markup cannot execute scripts | P0 | A | Full |
| FR-DOC-015 | Report generation covers exactly four types: internship completion, student performance, company participation, mentor evaluation | P0 | F | Full |
| FR-DOC-016 | Report generation runs inside a Command Action transaction with audit logging and a queued completion event | P0 | F | Full |
| FR-DOC-017 | Report requests validate template identity, registration identity, and optional parameters server-side | P0 | F | Full |
| FR-DOC-018 | Every generated report links back to its source template and context through stored metadata | P1 | F | Full |
| FR-DOC-019 | Report deletion removes the stored PDF and its record without touching the source template | P1 | F | Full |
| FR-DOC-020 | On-the-fly rendering serves an inline PDF without persisting unless explicitly stored | P1 | F | Full |
| FR-DOC-021 | Every user-facing string in template and report flows passes through the translation helper with mirrored Indonesian and English keys | P0 | A | Full |
| FR-DOC-022 | Template and report mutations write dual-channel audit entries with PII masking | P0 | F | Full |
| FR-DOC-023 | Foreign keys use ordered UUID references with explicit delete behaviour and composite indexes | P0 | A | Full |
| FR-DOC-024 | Template and report classes extend their layer bases with strict typing and a single-entry Action surface | P0 | A | Full |

### 4.1 Template Lifecycle

#### FR-DOC-001 — Command-driven authoring

During enrollment week two admins edit templates within minutes of each other. Because the
save travels through a Command Action wrapped in a transaction, the second save either
lands completely or rolls back completely; there is no half-written template with a title
from one edit and content from another. The Action accepts a data object rather than a
loose array once the field count crosses three, so the call site reads as named intent and
validation lives in one place. The Livewire manager never touches the model directly; it
maps the response into a toast or a re-render.

#### FR-DOC-002 — Versions accumulate, never rewrite

A dispute over a permit letter issued in February surfaces in May: the parent claims the
dates differ from what they signed. The coordinator opens version two, the version active
in February, and the rendered content matches the parent's copy exactly, because editing
the March wording created version three instead of overwriting version two. Retired
versions are read-only by construction; the only write path creates a successor. That
single property turns every historical document into its own evidence.

#### FR-DOC-003 — Slugs survive duplicate titles

Two departments both name their file "Surat Izin" in the same week. The first keeps the
clean slug; the second receives a short collision suffix derived deterministically, so
bookmarks to the first never break and no admin has to invent a distinguishing title. The
derivation runs in a millisecond unit test against plain strings, which is why it lives on
the value layer rather than behind a database round-trip.

#### FR-DOC-004 — Active gate for rendering

Retired templates lingering in dropdowns caused the classic failure: a teacher generating
letters from an outdated consent form after the school had already approved a new one. The
active flag closes that path. Selection queries scope to active records, while audit views
bypass the scope explicitly so history remains inspectable. Deactivation is itself an
audited mutation, so the moment a version stopped being selectable is on record.

#### FR-DOC-005 — Category vocabulary

When the handbook team added the handbook discriminator and the reports team added the
report discriminator, both reached for the same enum instead of inventing parallel
strings. The shared vocabulary means a query for active letters never accidentally returns
a policy draft, and adding a future category is a one-case change reviewed in a single
file. Each case carries its bilingual label so dropdowns render without hardcoded text.

#### FR-DOC-006 — Model as persistence adapter

The documents table carries the shape every other module assumes: ordered UUID primary
key, whitelisted fillable set, boolean and integer and JSON casts, an ownership relation
that nulls cleanly when an admin account is removed, and the two scopes every listing
relies on. Business predicates stay out of the model entirely; they live on the entity
snapshot bridged from the model, which keeps schema renames contained to the bridge.

#### FR-DOC-007 — Two layers of authorization

A teacher guessing an edit URL for a template they can read receives a forbidden response
at the route gate, and even a crafted direct call into the save Action re-checks the role
before touching the database, rejecting with a translatable business-rule message. The
double check exists because URLs are guessable and Actions are callable; either layer
alone leaves a bypass. Read access stays broad on active documents so classrooms are
never blocked, while every write stays admin-only.

#### FR-DOC-008 — Attachments that cannot escape

An admin uploading a scanned signature page renamed with path separators learns the
filename is normalized server-side: the stored name is non-guessable, the file lands
outside the web root, and only an entity-derived download route ever serves it. MIME
sniffing rejects a script masquerading as a PDF, and the per-module size cap rejects a
40 MB scan before it touches storage. These checks run in the Action as well as the form,
so a crafted request cannot bypass the UI.

### 4.2 Placeholder Contracts and Rendering

#### FR-DOC-009 — Canonical variable set

The consent letter, the permit, and the completion report all draw from one declared
variable set: student identity, program and period dates, company placement, and school
metadata from settings. Declaring the set once means a template author sees exactly which
names are legal, and a renderer resolving them never improvises. When the school profile
gains a new field, extending the contract is a deliberate, reviewed change rather than a
silent template edit.

#### FR-DOC-010 — Unknown variables fail loudly

A template typed `{siswa_nama}` instead of `{student_name}` once rendered 400 letters with
a blank where the name belonged, discovered only at the signing table. Now an
unresolvable name aborts the render with a message naming the offending variable in the
admin's language, and nothing is stored. Loud failure at render time is cheaper than a
reprint run the night before departure.

#### FR-DOC-011 — HTML before PDF

Admins preview the resolved HTML in the browser before committing to PDF because layout
bugs are an order of magnitude cheaper to spot in HTML. The preview uses the same
resolution path as the PDF compile, so what is previewed is what gets printed; there is
no second code path that could diverge. Students and teachers see this same preview when
opening a document on their phones.

#### FR-DOC-012 — One PDF engine

Three features once rendered PDFs three different ways, and a DomPDF upgrade broke only
two of them. The canonical pipeline funnels every template and report through one
renderer service, so engine upgrades, font fixes, and margin corrections land once. The
service is injected through the constructor, which keeps Actions testable with a stubbed
renderer and keeps the engine swappable without touching call sites.

#### FR-DOC-013 — Deterministic disk paths

Generated files live under the module's local disk directory with names derived from the
record identity plus an optional suffix, never from user input. Responses carry the
download stream, never the absolute server path, so a leaked error message cannot reveal
directory layout. Deterministic naming also makes re-renders idempotent: rendering twice
overwrites the same path instead of littering orphans.

#### FR-DOC-014 — Markup that cannot bite

Because template content is admin-authored HTML, a pasted snippet from a web page once
carried an inline script into the compile. Sanitization at the render boundary strips
executable content while preserving formatting, and output escaping in surrounding views
closes the second half of the vector. The rule is structural rather than advisory: the
scan flags unescaped user content, so the protection does not depend on author caution.

### 4.3 Report Generation

#### FR-DOC-015 — Four fixed report types

Coordinators asked for a report builder with arbitrary queries; what they actually needed
was four questions answered reliably: who completed, how students performed, which
companies participated, how mentors evaluated. Fixing the four types keeps each one's
context-gathering explicit and testable instead of hiding it behind a formula dialect
nobody at the school can debug. A fifth type, if ever needed, arrives as a reviewed code
change with its own context assembly, not as a runtime surprise.

#### FR-DOC-016 — Transactional generation

Generating a completion report writes a record, stores a file, and announces completion.
If the disk write fails halfway, the transaction rolls the record back so the listing
never shows a report with no file behind it. Success writes an audit entry and dispatches
the completion event after commit, which is where notifications and cache invalidation
attach without bloating the Action itself.

#### FR-DOC-017 — Requests validated twice

A report request carrying a template identifier from another school's export, or a
registration identifier with a transposed character, is rejected before any rendering
starts. Form-level rules check existence and UUID shape; the Action re-validates the
resolved records' eligibility, so a request that passes syntax but names a retired
template still fails with a clear message rather than a blank PDF.

#### FR-DOC-018 — Reports remember their parents

When the principal asks how the participation totals were computed, the coordinator opens
the report's metadata and finds the source template identifier, the context snapshot, and
the generation timestamp. That linkage turns a bare PDF into a reproducible derivation:
re-running the same template over the same snapshot yields the same numbers, which is the
entire basis for trusting administrative statistics.

#### FR-DOC-019 — Deletion cleans both halves

Deleting a report removes the database record and the stored file together. The ordering
matters: the file is removed first, then the record, inside the same transactional
boundary, so a crash cannot leave a record pointing at a missing file or a file with no
record. Source templates are untouched; deleting a derived report must never endanger
the template other reports still reference.

#### FR-DOC-020 — Preview without persistence

A coordinator unsure about a template's wording renders an inline preview straight to the
browser without creating any record. Only an explicit store action persists. This split
keeps the documents table free of abandoned drafts from exploratory previews, and it
keeps preview latency low because no audit write or event dispatch rides along.

### 4.4 Cross-Cutting Contracts

#### FR-DOC-021 — Bilingual by construction

A newly added report label appearing in Indonesian on the admin screen and as a raw key on
the English screen is a defect, not a cosmetic gap. Every string in these flows passes
through the translation helper with mirrored keys, and dynamic values travel as
placeholders rather than concatenation, so word order differences between the two
languages never corrupt a sentence.

#### FR-DOC-022 — Audited with masked payloads

Each template save and report generation writes an entry carrying actor identity and a
change summary through the dual-channel logger, landing in the queryable activity store
for the audit trail and in the system log for operations. Personal names inside payloads
are masked before either sink, so a support engineer grepping logs never harvests
student identities by accident.

#### FR-DOC-023 — Keys that join cleanly

Every foreign key in this module references an ordered UUID with an explicit delete
behaviour and sits under a composite index alongside its status flags. The uniformity is
what keeps the period-close queries fast when thousands of report rows join back to
registrations, and it is what prevents the mixed-type join failures that plagued the
early integer-key prototype.

#### FR-DOC-024 — Bases and boundaries

Template and report classes extend their layer bases, declare strict typing, and expose
exactly one public entry point per Action. The uniformity pays off during review: nobody
asks whether a new Action wraps its writes in a transaction, because the base makes the
answer structural, and the contract scan proves it without human memory.

---

## 5. Non-Functional Requirements

Constraints on how the pipeline behaves under misuse, failure, and growth. Each row names
its measurable target; the groups below narrate the reasoning.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DOC-001 | Template and report mutations are unreachable without authorization at both Policy and Action layers | N/A | P0 | A | Full |
| NFR-DOC-002 | Rendered template output carries no executable scripts from template content or context values | N/A | P0 | A | Full |
| NFR-DOC-003 | Error and download responses never expose absolute server paths | N/A | P0 | A | Full |
| NFR-DOC-004 | Interrupted PDF stores leave no orphan files and no record without a file | N/A | P0 | F | Full |
| NFR-DOC-005 | Template and report listings stay paginated and eager-loaded at cohort scale | N/A | P1 | F | Full |
| NFR-DOC-006 | Indonesian and English template strings remain complete and mirrored across releases | N/A | P1 | A | Full |

### 5.1 Protection and Integrity

#### NFR-DOC-001 — Authorization without bypass

A scanner walks every template and report mutation asserting the Policy gate and the
in-Action business-rule check both exist. The reasoning is adversarial: URLs leak through
chat forwards, and Livewire calls can be replayed, so either check alone is a single
point of failure. Dual enforcement turns a leaked URL into a forbidden page rather than
a data incident.

#### NFR-DOC-002 — Output without scripts

The sanitization boundary is verified by feeding hostile markup through the renderer and
asserting the compiled output carries no executable content. Template authors paste from
the web constantly, and context values include human-entered names; trusting either
source would eventually deliver a stored script to every reader of an official letter.

#### NFR-DOC-003 — Paths stay inside

Download and error responses are inspected for filesystem leakage: no absolute paths, no
disk names, no stack traces. A parent downloading a consent letter should see the letter,
never a hint about where the server keeps its files. This holds even on failure, when
verbose errors are most tempted to confess internals.

#### NFR-DOC-004 — Storage without orphans

Killing the process mid-store must leave either a complete record-file pair or neither.
The write lands atomically and the record creation shares the transactional boundary, so
a coordinator re-running generation after a crash finds no phantom rows and no stray
files consuming disk. Verification replays the interruption rather than assuming it.

### 5.2 Scale and Language

#### NFR-DOC-005 — Listings that stay fast

Opening the template list during the busiest admin morning must not degrade as the
record count grows into the thousands. Pagination bounds every listing and relations
load eagerly, so the page issues a flat query count regardless of cohort size. The
failure mode being prevented is the classic one: a dashboard that felt instant with
fifty rows and collapses with five thousand.

#### NFR-DOC-006 — Two languages, no drift

Each release asserts the Indonesian and English catalogues mirror each other for every
key these flows render. A missing translation is caught by the scan before it reaches a
classroom, because an untranslated raw key on a parent consent letter reads as an
official error and generates support calls the school cannot afford during departure
week.

---

## 6. API / Data Contracts

### 6.1 Document Model

```
Document — documents table, UUID v7 PK
  Fillable: type, slug, title, content, file_path, version, is_active, metadata, created_by
  Casts: is_active → boolean, version → integer, metadata → json
  Relations: createdBy() BelongsTo User (nullOnDelete)
  Media: file (single), handbook_file (single)
  Scopes: active(), ofType(string)
  Bridge: asHandbook() → HandbookEntity
```

### 6.2 DocumentCategory Enum

```
DocumentCategory: string, implements LabelEnum
  APPLICATION = application, PERMIT = permit, CERTIFICATE = certificate,
  REPORT = report, LETTER = letter, POLICY = policy, HANDBOOK = handbook
```

### 6.3 Renderer Service

```
DocumentRenderer (final readonly, constructor-injected)
  renderHtml(Document, object $context): string
  renderPdf(Document, object $context): string  — via DomPDF
  storePdf(Document, object $context, ?string $suffix = null): string
  Disk: generated-documents/ (local, outside web root)
```

### 6.4 Actions, Policies, Routes, Requests

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| SaveDocumentTemplateAction | BaseCommandAction | validated DTO | Document |
| GenerateReportAction | BaseCommandAction | Document template, context object | Document |
| RenderDocumentAction | BaseCommandAction | Document, Registration | Document |
| DeleteReportAction | BaseCommandAction | Document | void |

| Policy | Abilities |
| ------ | --------- |
| DocumentPolicy | viewAny: super_admin, admin, teacher, student · view: admin or active document · create, update, delete: admin |

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| GET /admin/reports | ReportsManager (Livewire) | auth, role:admin |
| GET /admin/documents/{document}/render/{registration} | DocumentRenderController@show | auth, role:admin |
| GET /admin/documents/{document}/render/{registration}/save | DocumentRenderController@store | auth, role:admin |

| Request | Rules |
| ------- | ----- |
| GenerateReportRequest | document_id: uuid, exists:documents,id · registration_id: uuid, exists:registrations,id · options: array, optional |

### 6.5 Database Schema

```
documents:
  id: uuid PK
  type: string default 'template', indexed
  slug: string unique
  title: string
  content: text nullable
  file_path: string nullable
  version: integer default 1
  is_active: boolean default true, indexed
  metadata: json nullable
  created_by: foreignUuid → users.id nullOnDelete nullable
  timestamps
  Indexes: (type, is_active)
```

---

## 7. Design Decisions

Recorded choices with their context. The table lists each decision once; the groups below
carry the narrative.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DOC-001 | Rendering pipeline lives in a dedicated injected service rather than inside Actions | P1 | — | — |
| DD-DOC-002 | Report catalogue is four fixed types rather than a runtime report builder | P1 | — | — |
| DD-DOC-003 | Generated PDFs stay on local disk rather than external object storage | P1 | — | — |
| DD-DOC-004 | Historical template versions are retained read-only rather than overwritten | P0 | — | — |

### 7.1 Pipeline and Catalogue

#### DD-DOC-001 — One renderer, many callers

Three Actions once each contained their own copy of the Blade-to-PDF sequence, and they
drifted: one handled fonts, another did not. Pulling the sequence into a single injected
service ended the drift and gave engine upgrades one landing place. The cost is one more
class to own, repaid every time DomPDF or its fonts need attention.

#### DD-DOC-002 — Fixed four over a builder

A runtime report builder with a formula dialect was prototyped and abandoned: none of the
coordinators who would use it could debug a broken formula during completion week, and
each custom report became untestable. Four fixed types with explicit context assembly are
predictable, reviewable, and covered by tests. Expressiveness was traded for reliability
at exactly the moment reliability matters most.

### 7.2 Storage and History

#### DD-DOC-003 — Local disk by default

Object storage would add credentials, buckets, and per-school billing for files that never
leave the school's own server. Local disk outside the web root, served through
entity-derived routes, satisfies the threat model at zero operational cost. If a future
deployment outgrows one disk, the renderer service is the single seam where a new driver
attaches.

#### DD-DOC-004 — History is append-only

Overwriting a template in place once retroactively altered the meaning of already-signed
letters, discovered during a parent dispute with no way to reconstruct the original. The
append-only version rule makes that class of dispute decidable: the version active at
signing time renders byte-identical forever. Storage overhead is trivial at school scale
compared with the evidentiary value.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Unauthorized template or report mutation | 0 | Policy plus Action authorization review |
| Report without source-template linkage | 0 | Metadata presence audit |
| Orphan generated file on disk | 0 | Atomic store plus deletion cleanup review |
| Executable content in rendered output | 0 | Sanitization scan over renderer output |
| Template list usable at cohort scale | paginated, no degradation | Listing review at seeded volume |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [installation](8NZAU-installation.md) | Provisioned system with default settings and school profile |

### Build Guide

Template infrastructure lands before the features that consume it: handbooks render
through the same pipeline, certificates snapshot template content at issuance, and
official documents reuse the renderer service.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [handbooks](ZUFG8-handbooks.md) | Handbook rendering and acknowledgment build on this pipeline |
| 2 | [certification](J0M04-certification.md) | Certificate issuance snapshots template content from this repository |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume DomPDF covers the school's layout needs; complex multi-column designs stay out of scope | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Certification phase and build order
- [Spec template](../templates/spec-template.md) — section skeleton and ID rules
- [Handbooks](ZUFG8-handbooks.md) — handbook lifecycle and acknowledgment
- [Certification](J0M04-certification.md) — certificate issuance and serials
- [File uploads and media](WQGTP-file-uploads-media.md) — upload validation and storage
- [PDF generation](7UB7S-pdf-generation.md) — canonical PDF engine
- [RBAC and authorization](T4B26-rbac-and-authorization.md) — role model and policies
- [Logging and error handling](89SRA-logging-and-error-handling.md) — dual-channel logging and exception trees
