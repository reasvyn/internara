# Company Management — CRUD, Deletion Guards, CSV Import & Dashboard Stats

> **Spec ID:** XI3LB
> **Status:** Full
> **Owner:** Partners
> **Depends on:** 81SMS, 4HWSB

## Description

Companies (DUDI — the world of business and industry) are the external side of every internship:
they host students, sign partnerships, and supply the placement slots the whole enrollment phase
consumes. This spec fixes company profile CRUD with admin-only writes, the dual-layer deletion
guards that keep placements and partnerships from being orphaned, single-format CSV onboarding
with per-row reporting, and the dashboard aggregation that turns scattered quotas into one
capacity picture. The partnership agreements themselves — MoU lifecycle, status, renewal — belong
to [partnership-management](NTHQA-partnership-management.md).

---

## 1. Problem Statements

### PS-1 — Deleting a Company Tears the Integrity Chain

A company anchors two dependent histories: its partnerships over time and the placements students
actually served. Deleting the company while either exists orphans enrollment records that
certificates and grade cards still reference — rows pointing at a UUID that no longer resolves.
The guard must fire at both the upfront query and the decision-time entity check, because a
single check races with concurrent placement creation during enrollment week.
**→ Requirement:** FR-COMP-005 (entity deletion predicate), FR-COMP-008 (action enforcement),
FR-COMP-009 (batch per-row guards).

### PS-2 — Onboarding Dozens of Partners by Hand Fails

A new deployment routinely starts with forty or more existing DUDI relationships kept in a
department spreadsheet: names, addresses, phone numbers, industry sectors. Manual entry takes
days, introduces typos into legal names, and silently duplicates the three companies two
different staff members both remembered. A validated CSV import with name deduplication and
per-row results turns that week into an afternoon.
**→ Requirement:** FR-COMP-011 (validated import with dedup), FR-COMP-012 (template and export).

### PS-3 — Capacity Planning Without a Capacity Number

Coordinators ask one question every placement season — how many open slots remain across all
partners — and without an aggregation they answer it by opening thirty partnership records and
adding quotas on a calculator. The dashboard must compute available slots as the sum of
remaining quota across active partnerships, so the number on screen and the truth in the rows
are the same number.
**→ Requirement:** FR-COMP-013 (slot aggregation), FR-COMP-014 (dashboard stat set).

### PS-4 — Stale Company Counts Misdirect Enrollment

Company totals, placement coverage, and slot availability are cached for the dashboard. A new
partner registered in the morning that still does not appear at the placement meeting in the
afternoon sends coordinators back to their spreadsheets — and every spreadsheet kept in
parallel is a second source of truth.
**→ Requirement:** FR-COMP-010 (domain events), FR-COMP-016 (cache invalidation listener).

---

## 2. Goals & Non-Goals

### Goals

- **Full company CRUD** — create, read, update, and guarded delete through Command Actions with admin-only writes and universal reads. *Why:* the company registry is the foundation every partnership and placement joins against.
- **Dual-layer deletion guards** — an upfront Action query plus a decision-time entity check, both requiring zero placements and zero partnerships. *Why:* enrollment-week concurrency defeats any single check.
- **Single-format CSV onboarding** — validated import with exact-name dedup, per-row results, a downloadable template, and full export. *Why:* forty-partner onboarding weeks must compress into an afternoon without duplicates.
- **Live capacity aggregation** — available slots computed from active partnership quotas and surfaced with company coverage stats. *Why:* placement season runs on one trustworthy remaining-slots number.
- **Automatic dashboard cache invalidation** — one listener clears company-dependent aggregates on every company event. *Why:* a dashboard that lags registration teaches coordinators to keep parallel spreadsheets.

### Non-Goals

- **Partnership lifecycle, MoU handling, status transitions, renewal**. *Why:* owned by [partnership-management](NTHQA-partnership-management.md); this spec stops at the company registry.
- **Student placement assignment**. *Why:* owned by the Enrollment module, which consumes the slot numbers computed here.
- **Multi-format import matrices (XLSX/PDF)**. *Why:* one CSV pipeline satisfies MVP onboarding; extra formats are post-MVP depth per the trim ADR.
- **Multi-tenant or cross-school company sharing**. *Why:* single-tenant by product definition — one installation, one school, one registry.
- **Fuzzy duplicate detection**. *Why:* exact-name matching keeps import deterministic; near-matches are surfaced as rows for human review, not auto-merged.

---

## 3. User Stories / Use Cases

Six journeys: create, update, guarded single delete, guarded batch delete, CSV import, and
export with template. Each is a browser-verifiable flow through the company manager.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-COMP-001 | Admin registers a company profile and it appears with a success notice | P0 | B | Full |
| UC-COMP-002 | Admin corrects a company profile and the change is visible immediately | P0 | B | Full |
| UC-COMP-003 | Admin attempts to delete a company with placements or partnerships and is refused with the reason | P0 | B | Full |
| UC-COMP-004 | Admin batch-deletes companies; eligible rows go, guarded rows are reported | P1 | B | Full |
| UC-COMP-005 | Admin imports companies from CSV with per-row created, duplicate, and error reporting | P0 | B | Full |
| UC-COMP-006 | Admin exports the registry to CSV and downloads the import template | P1 | B | Full |

### 3.1 Profile Lifecycle

#### UC-COMP-001 — Register a company

The partnership coordinator returns from a visit to a Karawang automotive supplier with a
business card and a handshake quota of forty slots. She opens the company registry, clicks Add,
and enters the legal name plus whatever contact detail exists — address, phone, email, website,
sector. The name is the only required field because on day one the card may hold nothing else;
validation runs live, the create Action persists through the DTO, and the row lands in the list
before she has filed the card. The dashboard totals tick up on the next view, because the
created event already cleared the cache behind her back.

#### UC-COMP-002 — Correct a company profile

Three months later the supplier moves workshops across the industrial estate and the phone
number changes hands to a new HR contact. The admin opens the row, edits address and phone,
and saves — the update Action validates, persists, emits its event, and the partnership
negotiation happening in the next browser tab quotes the new number without a refresh. The
journey's quiet assertion is that edits propagate: nothing in the system holds a stale copy
of a company's contact detail past the save.

### 3.2 Guarded Deletion

#### UC-COMP-003 — Refused deletion with the specific blocker

Cleanup season: an admin tries to remove a company whose partnership lapsed two years ago,
unaware that eleven historical placements still reference it. The delete Action's upfront
query finds the placements, the entity check confirms from the model's current state, and the
refusal names placements as the blocker with a translatable explanation. The row survives,
the history it anchors survives with it, and the admin learns that "no active partnership"
and "safe to delete" are different statements.

#### UC-COMP-004 — Batch delete with per-row verdicts

Twenty legacy rows selected from an inherited spreadsheet, Delete Selected, one confirmation.
Six still carry partnerships, two carry placements, twelve are genuinely empty. Each row gets
its own guard evaluation; the twelve go through the single-delete Action with full events and
audit entries, the eight stay, and the summary reports both counts with per-row reasons. The
alternative — aborting the batch on the first blocked row — would have protected nothing and
cost the admin twelve manual deletions.

### 3.3 Bulk Onboarding

#### UC-COMP-005 — Import forty partners in one afternoon

Deployment week at a school with forty-three existing DUDI relationships: the coordinator
downloads the template, pastes the spreadsheet columns under the exact headers, and uploads.
Thirty-one rows create cleanly, nine match existing legal names exactly and report as
duplicates, three fail validation with the row number, field, and message spelled out. The
whole import runs inside one transaction — a database-level failure anywhere rolls back
everything rather than leaving a partial registry. By evening the registry is complete, the
duplicates are a review list instead of a data corruption, and the three errors are fixable
from the messages alone.

#### UC-COMP-006 — Export the registry and fetch the template

The reverse direction serves audits and fresh deployments alike: one click exports every
company row with all seven fields under the same headers the importer expects, so an export
round-trips cleanly back through an import. The template download is the same headers with no
data rows — the cheapest possible documentation of the expected shape, and the reason
misaligned-column support tickets never arrive.

---

## 4. Functional Requirements

Company behavior decomposes into schema and model, entity guards, four Command Actions, DTO
and form validation, events and cache, CSV onboarding, dashboard aggregation, policy, and
audit. Every row below is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-COMP-001 | `companies` table uses a UUID v7 primary key with a required name and nullable contact, sector, and description fields | P0 | A | Full |
| FR-COMP-002 | `Company` model extends `BaseModel` with `#[Fillable]` over all seven fields, placement and partnership relations, an entity bridge, and a factory | P0 | A | Full |
| FR-COMP-003 | `CompanyState` entity is `final readonly`, extends `BaseEntity`, and is constructed only via `fromModel()` with relation-count aggregates | P0 | U | Full |
| FR-COMP-004 | `CompanyState::fromModel()` resolves placement and partnership counts from eager-loaded aggregates when available, falling back to existence queries | P0 | U | Full |
| FR-COMP-005 | `CompanyState::canBeDeleted()` requires zero placements and zero partnerships and is the single authority for deletion eligibility | P0 | U | Full |
| FR-COMP-006 | `CreateCompanyAction` and `UpdateCompanyAction` accept the validated DTO, enforce field rules, persist inside transactions, and dispatch their events | P0 | F | Full |
| FR-COMP-007 | `CompanyData` DTO is `final readonly`, extends `BaseData`, requires only `name`, and carries the six optional fields | P0 | U | Full |
| FR-COMP-008 | `DeleteCompanyAction` runs the upfront relation query, re-checks the entity guard, throws `RejectedException` naming the blocker, and deletes inside a transaction | P0 | F | Full |
| FR-COMP-009 | `BatchDeleteCompanyAction` evaluates `canBeDeleted()` per selected row, deletes only eligible rows through the single-delete path, and reports both counts | P1 | F | Full |
| FR-COMP-010 | Each mutation dispatches its domain event (`CompanyCreated`, `CompanyUpdated`, `CompanyDeleted`) from the Action after commit | P0 | F | Full |
| FR-COMP-011 | CSV import validates the required name column, deduplicates by exact name match, reports per-row results, and runs inside one transaction | P0 | F | Full |
| FR-COMP-012 | The manager serves a header-only CSV template download and a full-registry CSV export under identical headers | P1 | F | Full |
| FR-COMP-013 | Available slots aggregate as the sum of remaining quota across active partnerships, never contradicting the partnership quota caps | P0 | F | Full |
| FR-COMP-014 | Dashboard stats expose total companies, companies with placements, active partnerships, and available slots | P1 | F | Full |
| FR-COMP-015 | `CompanyPolicy` opens listing and viewing to every authenticated user, restricts create/update/delete to the admin group, and mirrors the entity guard on delete | P0 | U | Full |
| FR-COMP-016 | A single listener invalidates the registered dashboard cache keys on all three company events | P1 | F | Full |
| FR-COMP-017 | Every company mutation writes a SmartLogger activity entry with actor identity and change summary, masking PII before either sink | P0 | F | Full |

### 4.1 Schema, Model, and Entity

#### FR-COMP-001 — Registry table with UUID key and one required column

Seven columns, exactly one of them required: the legal name, because a partnership visit
frequently yields a name and a handshake before it yields an address. The UUID v7 primary key
follows the project-wide contract — no enumeration of the partner list, no merge collisions —
and the sector column carries an index since the "all manufacturing partners" filter runs
every placement season. Everything else stays nullable rather than forcing coordinators to
invent data to satisfy the form.

#### FR-COMP-002 — Thin model with relations, bridge, and factory

The model holds persistence and points at rules: `BaseModel` for the UUID contract,
`#[Fillable]` over the seven fields, `placements()` and `partnerships()` relations for the
guard queries, the `asCompanyState()` bridge into the entity, and a factory for tests. No
scopes smuggle business meaning, no accessors compute eligibility — a reviewer reading this
file learns the shape of the data and exactly where to go for the meaning.

#### FR-COMP-003 — Entity form and construction discipline

`CompanyState` is `final readonly` and extends `BaseEntity`, constructible from persistence
only through `fromModel()`, which folds the relation aggregates into placement and
partnership counts at construction time. The snapshot semantics matter: the guard evaluates
the counts as they were when the decision started, and the Action's upfront query plus this
decision-time snapshot together close the race that either alone leaves open.

#### FR-COMP-004 — Aggregate-aware count resolution

When the caller already loaded the relation counts, the bridge reads them without a new
query; when it did not, existence queries answer the boolean the guard actually needs. The
department list taught this pattern first — prefer the eager aggregate, fall back to
`exists()` — and the company registry inherits it, because the manager table showing
per-row dependent counts must not cost a query per row at forty-plus partners.

#### FR-COMP-005 — Two-condition guard with total authority

Deletable means zero placements and zero partnerships, both, no exceptions — the historical
placements matter as much as the live partnerships because certificates issued last year
still join through this row. Policy, Action, and batch flow all consult this one method, so
the three can never disagree about a company's fate. Any future "but what about dormant
partners" nuance lands here as an amended predicate, not as a fourth opinion scattered
across callers.

### 4.2 Actions, DTO, and Guards

#### FR-COMP-006 — Create and update through the DTO boundary

Both Actions accept `CompanyData`, validate field rules (name required with sensible length
bounds, email shaped like an email, website shaped like a URL), persist inside
`$this->transaction()`, and dispatch their event after commit. The symmetry is the feature:
every profile of company write — UI save, CSV row, seeder, console fix — funnels through the
same two Actions, so validation strength never depends on which door the data entered by.

#### FR-COMP-007 — Seven-field DTO with one required member

The DTO mirrors the table exactly: required `name`, six nullable companions, nothing ambient.
Ownership sits with the consuming Actions' module per the DTO rule, and the boundary holds —
no Action in this module accepts a raw request array, which is what keeps the CSV importer
honest, since its rows arrive as untrusted strings that must earn their way into typed
fields.

#### FR-COMP-008 — Delete with two checks and a named refusal

The upfront relation query catches the common case early with a clear message; the entity
re-check at decision time catches the placement created by a concurrent request in between.
When either fires, `RejectedException` names the blocker — placements or partnerships — in
translatable language, and the Livewire layer renders it as guidance rather than an error
page. Safe rows delete inside the transaction with event and audit trailing the commit, the
same ordering every mutation in the system follows.

#### FR-COMP-009 — Batch verdicts per row

The batch Action walks the selected ids, evaluates the guard on each, routes eligible rows
through the single-delete path (preserving per-row transactions, events, and audit entries),
and returns deleted and skipped counts with per-row reasons. Skipping rather than aborting
fits the registry's reality: legacy imports routinely mix a dozen empty rows with a few
anchored ones, and the admin's cleanup afternoon should not end in twelve hand deletions.

### 4.3 Events, Import, Export, and Capacity

#### FR-COMP-010 — Post-commit domain events from Actions

Created, updated, deleted — one event per mutation, dispatched after commit, carrying the
affected model. The batch path emits one deleted event per removed row rather than a
synthetic batch event, so listeners never need a second code path for "one versus many".
Any future subscriber, from notifications to analytics, attaches without reopening the
Actions that already do their jobs.

#### FR-COMP-011 — Transactional import with exact-name dedup

Each CSV row is validated (name present, optional columns parsed, email and URL shapes
checked), matched by exact, case-sensitive name against the registry, and reported through
the `CsvRowResult` enum as created, duplicate, or error with row number and field detail.
The entire import wraps in one transaction: a database failure mid-file rolls back all of
it, because a half-imported partner list is worse than a failed one — the former looks
complete. Exact matching deliberately skips the fuzzy near-duplicate question; "PT Maju"
versus "PT maju" stays a human decision, surfaced in the duplicate list, never auto-merged.

#### FR-COMP-012 — Template and export under one header contract

The template is the seven headers with no data rows; the export is the same seven headers
with every registry row beneath them. One header contract in both directions means an export
edits cleanly in a spreadsheet and re-imports without column-mapping surprises — the
round-trip property that makes the export genuinely useful for audits instead of merely
decorative. Both files generate synchronously at registry scale; the streaming-for-thousands
machinery is deferred depth the MVP registry does not need.

#### FR-COMP-013 — Slot aggregation that respects quota caps

Available slots sum remaining quota (`quota` minus `filled_quota`) across active
partnerships, and the partnership entity's overcommit guard — which refuses any assignment
past the cap — is what keeps every term of that sum non-negative. The aggregation therefore
never reports phantom capacity: a partnership that filled its forty slots contributes zero,
not negative twelve, and the coordinator's headline number always matches the sum of the
rows. Quota truth lives in the partnership spec; this row consumes it without duplicating it.

#### FR-COMP-014 — Four numbers that run placement season

Total companies for the registry's size, companies with placements for coverage, active
partnerships for the live network, available slots for the remaining capacity — the four
stats render together because placement decisions need all four at once. Each resolves from
the owning relations rather than a cached guess, and the invalidation listener below is what
lets them be cached at all without lying.

### 4.4 Policy, Cache, and Audit

#### FR-COMP-015 — Open reading, admin-group writing, mirrored guard

Teachers scouting host companies and students confirming their placement site read freely;
creation, update, and deletion belong to the admin group. The delete ability mirrors the
entity guard exactly — same predicate, both layers — because a policy that authorizes what
the Action refuses produces the classic dead button: clickable, authorized, and ultimately
rejected with no explanation at the gate. Here the gate and the Action speak with one voice.

#### FR-COMP-016 — One listener clears company-dependent aggregates

`ClearDashboardOnCompanyChange` handles all three company events with forget calls against
the dashboard keys registered in `config/cache-keys.php`. The fan-out crosses into the
dashboard's module, so per the observers ADR it travels as an event rather than a
same-model observer — companies must not know how the dashboard caches, only that something
relevant changed. Synchronous invalidation keeps the placement meeting's numbers current to
the morning's registrations.

#### FR-COMP-017 — Dual-channel audit with PII masking

Company records hold phone numbers, emails, and contact names — exactly the payload that
must never reach a plaintext log. Every mutation writes its SmartLogger activity entry with
actor identity and change summary through PII masking first, per the dual-channel ADR, while
unexpected failures surface generically to users with full context reserved for the system
channel. The DUDI relationship history this builds — who registered, corrected, or removed
each partner and when — doubles as the partnership dispute record.

---

## 5. Non-Functional Requirements

Guards that hold under enrollment-week concurrency, import feedback a coordinator can act on,
and the structural hygiene the scans enforce. `Target` is `N/A` where enforcement is
architectural rather than measured.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-COMP-001 | Mutations are authorized at both the policy gate and the Action layer | N/A | P0 | A | Full |
| NFR-COMP-002 | Deletion is blocked while any placement or partnership references the row, at both guard layers | 0 orphans | P0 | F | Full |
| NFR-COMP-003 | Company routes require authentication with admin-group gates on writes | N/A | P0 | F | Full |
| NFR-COMP-004 | CSV import sanitizes every field and validates file type with a 1,000-row cap | 1000 rows/import | P0 | F | Full |
| NFR-COMP-005 | CSV import is atomic: any database failure rolls back all rows | 0 partial imports | P0 | F | Full |
| NFR-COMP-006 | Import and batch results report per-row outcomes with counts and reasons | per-row detail | P1 | F | Full |
| NFR-COMP-007 | Validation feedback is real-time, inline, and announced to assistive technology | inline + announced | P1 | B | Full |
| NFR-COMP-008 | All PHP files declare strict types; entities and DTOs stay `final readonly` and framework-pure | 0 violations | P0 | A | Full |
| NFR-COMP-009 | Every user-facing string passes through `__()` with mirrored `en` and `id` keys | 0 missing keys | P0 | A | Full |

### 5.1 Safety and Integrity

#### NFR-COMP-001 — Two gates, no back door

The HTTP path meets the policy before the Action's business rule; direct Action calls from
importers, seeders, and console still meet the business rule. Company deletion is the
operation where a bypass would orphan the most history, so both enforcement points carry
per-mutation tests rather than resting on framework assumptions.

#### NFR-COMP-002 — Zero orphans across both guard layers

The upfront query and the decision-time entity check overlap deliberately: the first gives
the fast, specific refusal, the second closes the race window enrollment-week concurrency
opens between check and delete. Together they reduce the orphan count to zero by
construction, and the batch path inherits the guarantee by routing every row through the
same guarded Action.

#### NFR-COMP-003 — Authenticated reads, admin-group writes

The registry is visible to every authenticated role — placement only works when teachers
and students can see the host list — while writes sit behind the admin-group middleware and
policy checks. Route middleware, component authorization, and policy abilities form the
three defense layers the RBAC design demands, with no layer assuming another handled it.

### 5.2 Import Discipline and Feedback

#### NFR-COMP-004 — Sanitized fields, bounded files

Every imported field passes through the same sanitization the form applies — no raw HTML
into names, no concatenated SQL anywhere near the loader — and the upload itself is bounded
by MIME validation and a thousand-row cap. The cap is a denial-of-service Humber bridge:
large enough for any real onboarding week, small enough that a malicious file cannot hold a
transaction open indefinitely.

#### NFR-COMP-005 — All rows or none

The single-transaction wrap means a constraint failure on row nine hundred voids the eight
hundred ninety-nine before it. That sounds wasteful until the alternative is considered: a
registry the coordinator believes complete, missing an unknown suffix of rows, discovered
only when a partnership points at a company that never imported. Failure must be loud and
total, never quiet and partial.

#### NFR-COMP-006 — Results a human can finish from

Created, duplicate, and error counts open the report; per-row lines with row numbers, field
names, and messages complete it. The coordinator's job after an import is a finite punch
list — fix three malformed emails, review nine possible duplicates — and this reporting
shape is what makes it finite. Batch deletes report the same way, with per-row skip
reasons, because "8 skipped" without names is a mystery while "8 skipped, named" is a
checklist.

#### NFR-COMP-007 — Feedback where the typing happens

Form validation responds as the coordinator types, errors attach to their fields with
associated labels, and the live region announces them to screen readers — the keyboard-only
administrator uploading the CSV meets the same guidance as everyone else. Import errors
carry their row numbers into the announcement, since "row 31" is the only address a
spreadsheet error has.

### 5.3 Structural Hygiene

#### NFR-COMP-008 — Strict types and pure boundaries

Strict types on every file, `final readonly` entities and DTOs with no persistence imports —
the pre-commit scans fail the commit on drift, not the reviewer's memory. The entity guard's
millisecond unit tests exist only because this boundary holds; an Entity that could query
would turn every guard test into a database test.

#### NFR-COMP-009 — No hardcoded user strings

Registry headers, toasts, confirmations, import reports, and validation messages all pass
through `__()` with keys mirrored in both locale catalogs, proven by the D3 scan. The
coordinator doing onboarding week in Indonesian reads the same actionable import report the
English documentation screenshots show — no hardcoded sentence hiding in the one dialog the
pilot school opens daily.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Company Model

```
App\Partners\Company\Models\Company
  Table: companies (UUID PK, v7)
  Fillable: name, address, phone, email, website, description, industry_sector
  Indexes: name, industry_sector
  Relations:
    placements() HasMany Placement
    partnerships() HasMany Partnership
  Bridge: asCompanyState() → CompanyState::fromModel() (FR-COMP-002)
  Factory: CompanyFactory
```

### 6.2 CompanyData DTO

```
App\Partners\Company\Data\CompanyData extends BaseData (final readonly)
  Required: name: string
  Optional: address, phone, email, website, description, industrySector: ?string
```

### 6.3 CompanyState Entity

```
App\Partners\Company\Entities\CompanyState extends BaseEntity (final readonly)
  Properties: placementCount: int, partnershipCount: int
  Factory: fromModel(Model) — eager aggregates preferred, exists() fallback (FR-COMP-004)
  Methods:
    canBeDeleted(): bool — placementCount === 0 && partnershipCount === 0 (FR-COMP-005)
```

### 6.4 Policy

```
App\Partners\Company\Policies\CompanyPolicy extends BasePolicy
  viewAny / view: all authenticated users
  create / update: admin group (FR-COMP-015)
  delete: admin group AND CompanyState::canBeDeleted() — same predicate as the Action
  forceDelete: super_admin only (no soft deletes in this module)
```

### 6.5 Actions

| Action | Base | Accepts | Returns | Events |
| ------ | ---- | ------- | ------- | ------ |
| `CreateCompanyAction` | `BaseCommandAction` | `CompanyData` | `ActionResponse` | `CompanyCreated` |
| `UpdateCompanyAction` | `BaseCommandAction` | `Company, CompanyData` | `ActionResponse` | `CompanyUpdated` |
| `DeleteCompanyAction` | `BaseCommandAction` | `Company` | `ActionResponse` | `CompanyDeleted` |
| `BatchDeleteCompanyAction` | `BaseCommandAction` | `Collection` | `ActionResponse` | `CompanyDeleted` per row |

### 6.6 Events and Listener

| Event | Dispatched by | Carries |
| ----- | ------------- | ------- |
| `CompanyCreated` | `CreateCompanyAction` | `Company` model |
| `CompanyUpdated` | `UpdateCompanyAction` | `Company` model |
| `CompanyDeleted` | `DeleteCompanyAction`, batch action | `Company` model |

`ClearDashboardOnCompanyChange` handles all three synchronously and forgets the registered
dashboard keys (FR-COMP-016).

### 6.7 Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/companies` | `CompanyManager` | `partners.companies` | `auth`, `role:super_admin\|admin` |

### 6.8 Database Schema

```
companies:
  id:               uuid (PK, v7)
  name:             string (indexed, required — natural dedup key)
  address:          text (nullable)
  phone:            string (nullable)
  email:            string (nullable)
  website:          string (nullable)
  description:      text (nullable)
  industry_sector:  string (nullable, indexed)
  created_at:       timestamp
  updated_at:       timestamp
```

### 6.9 CSV Column Contract

| CSV Column | DB Column | Required | Notes |
| ---------- | --------- | -------- | ----- |
| `name` | `name` | Yes | Dedup key — exact, case-sensitive match |
| `address` | `address` | No | Free-form address |
| `phone` | `phone` | No | Contact phone |
| `email` | `email` | No | Contact email |
| `website` | `website` | No | Company URL |
| `description` | `description` | No | Free-form description |
| `industry_sector` | `industry_sector` | No | Industry classification |

Per-row outcomes use the `CsvRowResult` enum (`created`, `duplicate`, `error`) with
`label()` via the `LabelEnum` contract (FR-COMP-011).

### 6.10 Dashboard Stats

```
total_companies:      count(companies)
with_placements:      companies having ≥ 1 placement
active_partnerships:  companies having ≥ 1 ACTIVE partnership
available_slots:      Σ (quota − filled_quota) over active partnerships (FR-COMP-013)
```

---

## 7. Design Decisions

One table of the calls that shaped this spec. Each decision below is load-bearing: reversing
any of them reopens the incident or the drift it was written to close.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-COMP-001 | Deletion guards run at the Action query, the entity check, and the policy gate against one shared predicate | P0 | — | — |
| DD-COMP-002 | Business rules delegate through the `asCompanyState()` entity bridge; models stay persistence-only | P0 | — | — |
| DD-COMP-003 | CSV import deduplicates on exact name match inside a single transaction | P0 | — | — |
| DD-COMP-004 | Dashboard freshness comes from synchronous event-listener invalidation, not lazy cache reads | P1 | — | — |
| DD-COMP-005 | One CSV shape serves import, export, and template alike; further formats are deferred | P1 | — | — |

### 7.1 Guards and Import

#### DD-COMP-001 — Three checkpoints, one predicate, zero orphans

A single guard was tried first and failed exactly where predicted: the upfront query passed,
a concurrent enrollment-week request created a placement, and the delete orphaned it. Adding
the decision-time entity check closed the race; aligning the policy onto the same predicate
closed the dead-button gap where the gate authorized what the Action would refuse. The cost
is three evaluations of one cheap question per deletion — negligible against an orphaned
enrollment history, and the shared predicate means the three can never silently diverge.

#### DD-COMP-002 — The bridge keeps models honest

Company business logic once lived as model methods, which made every guard test a database
test and every schema rename a scavenger hunt. The `asCompanyState()` bridge concentrates
the persistence-to-rules translation in one factory, the entity holds the pure predicates,
and the model returns to being a persistence adapter the C1 scan approves of. One extra
class per submodule is the standing price of the architecture, paid gladly here for
millisecond guard tests.

#### DD-COMP-003 — Exact dedup, atomic file

Fuzzy matching was prototyped and abandoned after it flagged "PT Maju Jaya" against "PT
Maju Jayatama" — two genuinely different suppliers sharing an industrial estate — and nearly
auto-merged them. Exact, case-sensitive name matching never merges wrongly; the near-misses
it leaves become review lines in the duplicate report instead of corruption in the registry.
The single-transaction wrap pairs with this conservatism: cautious matching plus loud,
total failure keeps the registry exactly as trustworthy as the spreadsheet it replaced.

### 7.2 Freshness and Formats

#### DD-COMP-004 — Eager invalidation for planning numbers

Lazy cache reads would have left a staleness window between registering a partner and seeing
it counted — precisely the window the placement meeting falls into. Synchronous listener
invalidation closes it at the cost of milliseconds per mutation, a trade that favors
correctness every time the numbers drive enrollment decisions. The day a second consumer
appears for company events, it subscribes without touching the Actions, which is the
decoupling dividend the event choice already paid for.

#### DD-COMP-005 — One format done well beats three done thinly

XLSX and PDF variants were requested early and deferred under the trim discipline: every
additional format multiplies header-mapping bugs, MIME validation surface, and per-format
test matrices, while the schools asking for them open CSV files in the same spreadsheets
anyway. The single header contract across import, export, and template concentrates all
format knowledge in one place, and the deferred formats re-enter through a spec amendment
when a post-MVP phase — not a passing request — demands them.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Orphaned placements or partnerships after company deletion | 0 | Dual-guard feature tests including concurrent-create coverage |
| Duplicate companies after CSV import | 0 | Exact-name dedup tests with adversarial near-matches |
| Partial CSV imports persisted | 0 | Transaction rollback test on mid-file failure |
| Stale dashboard figures after company changes | 0 | Listener test asserting cache forget per event |
| Import reports lacking row-level detail | 0 | Report-content assertions on mixed-result files |
| Hardcoded user-facing strings | 0 | D3 scan on the module |

---

## 9. Roadmap

### Prerequisites

The school profile ([81SMS](81SMS-school-profile.md)) must be complete first, since companies
partner with that school, and department management
([4HWSB](4HWSB-department-management.md)) must exist because companies offer slots against
specific departments.

### Build Guide

With this spec implemented, the system holds a guarded, importable company registry with live
capacity numbers. The registry is the counterparty every partnership agreement names, so this
spec unlocks the formal relationship layer next.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [partnership-management](NTHQA-partnership-management.md) | Partnerships formalize the company relationship with MoU terms, quotas, and windows |
| 2 | [csv-import-export](O2KCR-csv-import-export.md) | Cross-cutting bulk pipeline that this spec's CSV contract plugs into |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume exact, case-sensitive name matching is sufficient dedup while the registry stays school-scale; fuzzy review tooling is deferred, not rejected | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad, entity split, and event contracts this spec builds on
- [Spec-zero](QLHDO-project-initialization.md) — global requirements (FR-GLB-001/002/004/007/008/010) inherited here
- [Department management](4HWSB-department-management.md) — slots are offered against departments
- [Partnership management](NTHQA-partnership-management.md) — quotas, MoU terms, and windows on top of companies
- [CSV import/export](O2KCR-csv-import-export.md) — cross-cutting bulk pipeline
- [Placement](J9GBH-placement.md) — consumes the aggregated slot numbers
- [ADR: entity-model separation](../adr/adr-entity-model-separation.md) — why guards live in the entity
- [ADR: action pattern over services](../adr/adr-action-pattern-over-services.md) — why one Action per operation
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` as the business-refusal voice
- [ADR: smartlogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — activity audit with PII masking
- [ADR: flat RBAC](../adr/adr-flat-rbac-with-functional-roles.md) — admin-group gating for writes
- [ADR: MVP trim](../adr/adr-mvp-spec-trim.md) — why one CSV format and no fuzzy matching at MVP
