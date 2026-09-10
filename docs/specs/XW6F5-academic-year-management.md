# Academic Year Management — Singleton Activation & Lifecycle

> **Spec ID:** XW6F5
> **Status:** Full
> **Owner:** Academics
> **Depends on:** 81SMS

## Description

Every dated fact in the system — registrations, placements, attendance, assessments — is anchored
to exactly one academic year, and exactly one year is active at any moment. This spec fixes that
temporal anchor: year CRUD, the singleton activation that guarantees one active year, the
first-year bootstrap, deletion guards that protect years carrying internships or assessments, the
July–June school-year computation, and the downstream gating that refuses writes outside the
active window. Company partnerships that run inside these windows are defined in
[partnership-management](NTHQA-partnership-management.md).

---

## 1. Problem Statements

### PS-1 — Two Active Years Means No Ground Truth

Registration windows, assessment deadlines, and dashboard metrics all resolve "the current year"
by querying for the active row. If two rows ever carry the flag simultaneously, every one of
those queries becomes ambiguous — a student registers into a year the dashboard does not report,
and nobody can say which record is wrong. Activation must therefore be a single atomic swap, not
two independent edits.
**→ Requirement:** FR-YEAR-006 (atomic singleton activation), FR-YEAR-014 (downstream gating on the active row).

### PS-2 — Deleting a Year Destroys Its History

Years accumulate internships and assessments that accreditation folders reference for years. A
delete that cascades into those records is data destruction wearing an admin UI; a delete that
nulls their year key is slower destruction with the same ending. Years with related records, and
the active year itself, must refuse deletion at a layer no caller can bypass.
**→ Requirement:** FR-YEAR-003 (entity deletion predicate), FR-YEAR-007 (action enforcement).

### PS-3 — Setup Dies on a Two-Step First Year

On a fresh installation the administrator creates the first academic year and then — in the old
flow — had to discover and perform a separate activation step before anything else in the
system would accept a date. Every pilot deployment tripped on this; the first year must simply
arrive active.
**→ Requirement:** FR-YEAR-004 (first-year auto-activation).

### PS-4 — Writes Outside the Active Window Corrupt the Timeline

A placement dated to a dormant year, or an assessment posted after its year closed, looks valid
row by row and poisons every aggregate it touches. The active year is not display metadata — it
is a validity boundary that downstream mutations must check before they write.
**→ Requirement:** FR-YEAR-014 (active-window gating with `RejectedException`).

### PS-5 — Stale Year State on the Dashboard Misleads Planning

Capacity planning reads the active year from cached dashboard aggregates. An activation that
leaves the old year's numbers cached until TTL expiry has the new term planning against last
term's figures — the kind of quiet wrongness discovered only at the placement meeting.
**→ Requirement:** FR-YEAR-009 (domain events), FR-YEAR-010 (cache invalidation listener).

---

## 2. Goals & Non-Goals

### Goals

- **Full year CRUD** — create, read, update, and guarded delete through Command Actions. *Why:* the temporal anchor must be as manageable as any other master data.
- **Singleton activation with atomic swap** — deactivating the old year and activating the new one inside one transaction. *Why:* two flags, even briefly, break every query that assumes one.
- **First-year bootstrap** — the inaugural year arrives active with no second step. *Why:* pilot deployments stalled on the manual activation nobody knew was required.
- **Deletion guards for active and referenced years** — enforced in the entity, repeated in the Action. *Why:* accreditation history must survive administrative enthusiasm for cleanup.
- **Atomic bulk delete with abort semantics** — all selected years validated first; any protected row aborts the batch naming it. *Why:* year deletion is rare and destructive enough that partial success is the wrong kindness.
- **Active-window gating for downstream writes** — mutations outside the active year are refused. *Why:* a timeline that accepts writes to dormant years is not a timeline.
- **Single July–June computation shared by seeders and settings** — one support class answers which school year contains today. *Why:* three independent computations drifted and disagreed for half of every calendar year.

### Non-Goals

- **Year archiving or soft deletes**. *Why:* years are referenced by permanent records; deletion is blocked rather than softened, and history lives in the records themselves.
- **Year duplication or cloning**. *Why:* copying date ranges between years is a form interaction, not a domain operation worth its own Action.
- **Automatic rollover at end date**. *Why:* activation is a deliberate administrative decision — a cron job flipping the school's temporal anchor overnight is a failure mode, not a feature.
- **Multiple concurrent active years**. *Why:* excluded by design; the singleton is the invariant this whole spec protects.
- **External academic calendar integration**. *Why:* no government sync at MVP beyond CSV boundaries defined elsewhere.

---

## 3. User Stories / Use Cases

Five journeys cover the lifecycle: create, bootstrap, activate, delete, and sweep. Each is a
browser-verifiable flow through the year manager.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-YEAR-001 | Admin creates a subsequent academic year and it arrives inactive | P0 | B | Full |
| UC-YEAR-002 | Admin creates the first academic year on a fresh system and it arrives active | P0 | B | Full |
| UC-YEAR-003 | Admin activates a new year; the old year deactivates in the same step | P0 | B | Full |
| UC-YEAR-004 | Admin attempts to delete a protected year and is refused with the reason | P0 | B | Full |
| UC-YEAR-005 | Admin bulk-deletes years; any protected row aborts the batch naming it | P1 | B | Full |

### 3.1 Creation and Bootstrap

#### UC-YEAR-001 — Create a subsequent year

In March the admin creates "2026/2027" while "2025/2026" is still running, entering the name
and the July-to-June date range. The new row arrives inactive — visibly so, with the active
badge staying exactly where it was — because an inactive-until-activated default is what keeps
a preparation-time row from hijacking live queries months early. The journey's assertion is
almost boring by design: everything unchanged except one more inactive row in the list.

#### UC-YEAR-002 — Bootstrap the first year

The fresh-install story that used to fail: no years exist, the admin creates "2025/2026", and
the system recognizes the empty table and flags the row active on the way in. No second click,
no settings page detour, no support ticket asking why registration insists there is no active
year. The count check that drives this lives in the create Action rather than the component, so
a seeder-created first year gets the same treatment as a UI-created one.

### 3.2 Activation, Deletion, and Bulk Handling

#### UC-YEAR-003 — Swap the active year

July arrives and the admin presses Activate on "2026/2027". One confirmation, one transaction:
the current year deactivates, the target activates, and the activated event carries both the
new row and the previous one so listeners and the audit trail can describe the handover. The
manager re-sorts with the new active year pinned first, the dashboard cache clears, and at no
instant — not even mid-transaction as observed by another request — did two years hold the
flag.

#### UC-YEAR-004 — Refused deletion with the reason attached

An admin tidying up clicks Delete on the active year, confirms, and meets a refusal that says
exactly why: the year is active, or it still carries internships and assessments. The message
distinguishes the two causes because the remedies differ — activate a successor first, versus
accept that a year with history is permanent. The row is untouched, the cache is untouched,
and the admin has learned something true about the data model instead of something mysterious
about the UI.

#### UC-YEAR-005 — Bulk sweep that aborts honestly

Five obsolete years selected, Delete Selected, confirmation given — and the batch contains one
year somebody forgot still has assessment rows. Rather than deleting four and silently sparing
one (the department-style kindness, wrong here), the bulk Action validates every selected row
before deleting any, then aborts the whole batch naming the protected year. The admin deselects
that row and retries. Years are deleted rarely and each deletion is load-bearing history, so
the all-or-nothing strictness trades a retry click for the guarantee that no batch ever
half-finishes.

---

## 4. Functional Requirements

Year behavior decomposes into schema, entity predicates, five Command Actions, events and
cache, the manager UI, policy gating, the shared period computation, downstream gating, and
audit. Every row below is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-YEAR-001 | `academic_years` table uses a UUID v7 primary key with a unique name, start/end dates, and an `is_active` flag defaulting false | P0 | A | Full |
| FR-YEAR-002 | `AcademicYear` model extends `BaseModel` with `#[Fillable]` dates and flag, date/boolean casts, internship and assessment relations, an entity bridge, and a factory | P0 | A | Full |
| FR-YEAR-003 | `AcademicYearState` entity is `final readonly`, resolves activity and related-record presence in `fromModel()`, and owns `canBeActivated()` and `canBeDeleted()` | P0 | U | Full |
| FR-YEAR-004 | `CreateAcademicYearAction` validates name uniqueness and the date range, auto-activates only when the table is empty, and otherwise creates inactive | P0 | F | Full |
| FR-YEAR-005 | `UpdateAcademicYearAction` persists edits inside a transaction and dispatches `AcademicYearUpdated` | P0 | F | Full |
| FR-YEAR-006 | `ActivateAcademicYearAction` refuses already-active targets, swaps the flag inside one transaction, and dispatches `AcademicYearActivated` with the previous row | P0 | F | Full |
| FR-YEAR-007 | `DeleteAcademicYearAction` throws `RejectedException` for active years and years with related records, and deletes inside a transaction | P0 | F | Full |
| FR-YEAR-008 | `BulkDeleteAcademicYearsAction` validates every selected year first, aborts naming the first protected row, and otherwise deletes atomically with per-row events | P1 | F | Full |
| FR-YEAR-009 | Each mutation dispatches its domain event (created, activated, updated, deleted) from the Action after commit | P0 | F | Full |
| FR-YEAR-010 | A single listener invalidates the registered dashboard-stats cache key on all four year events | P1 | F | Full |
| FR-YEAR-011 | `AcademicYearManager` lists years active-first with search, sorting, stats, and confirmation dialogs for activate, delete, and bulk delete | P0 | F | Full |
| FR-YEAR-012 | Year form rules require a unique name capped at 50 characters and an end date after the start date | P0 | U | Full |
| FR-YEAR-013 | `AcademicYearPolicy` opens listing and viewing to every authenticated user and restricts create, update, activate, and delete to the admin group | P0 | U | Full |
| FR-YEAR-014 | Downstream mutations resolve the active year and are refused with `RejectedException` when no active year exists or the target date falls outside it | P0 | F | Full |
| FR-YEAR-015 | One shared support class computes the July–June school year containing a date and is used by seeders and the active-year setting alike | P1 | U | Full |
| FR-YEAR-016 | Every year mutation writes a SmartLogger activity entry with actor identity and change summary, masking PII before either sink | P0 | F | Full |

### 4.1 Schema, Model, and Entity

#### FR-YEAR-001 — Years table with flag defaulting to dormant

The table is deliberately small — UUID v7 key, unique name, start and end dates, a boolean
flag defaulting to false — because everything expensive about years lives in the rules around
the flag, not in the columns. The false default is the load-bearing choice: any creation path
that forgets to think about activation produces a dormant row, which is always the safe
direction. Only the explicit empty-table bootstrap may flip the flag on the way in.

#### FR-YEAR-002 — Thin model with casts, relations, and bridge

Date casts keep range comparisons honest, the boolean cast keeps the flag a real boolean
instead of a truthy integer, and the `internships()` and `assessments()` relations exist so
the entity can ask the only question that matters at deletion time. The
`asAcademicYearState()` bridge is the single doorway from persistence to rules, and the
factory lets tests mint years across the July boundary without hand-building date fixtures.

#### FR-YEAR-003 — Entity predicates as the single authority

`canBeActivated()` is the negation of currently-active; `canBeDeleted()` is the negation of
active-or-referenced. Both live on `AcademicYearState`, constructed only through `fromModel()`
with `isActive` read from the row and `hasRelatedRecords` resolved from the two relations'
existence queries. The activate and delete Actions, the policy's delete path, and the bulk
validator all ask these two methods — four callers, two predicates, one definition of what
"protected" means.

### 4.2 The Five Command Actions

#### FR-YEAR-004 — Creation with validation and bootstrap

The create Action validates the name's uniqueness, requires both dates, and rejects an end
date that does not follow the start — the classic June-to-July typo that once produced a
negative-length school year nobody noticed for a month. Then comes the count check: an empty
table means this row is the first, so it arrives active; any other case arrives dormant. The
`AcademicYearCreated` event and the audit entry follow the commit, in that order, always.

#### FR-YEAR-005 — Transactional update with event

Renames and date corrections run inside a transaction and emit `AcademicYearUpdated`, which
sounds unremarkable until the morning an admin fixes a typo'd end date and the dashboard
keeps quoting the old range from cache. The event exists so the listener can clear exactly
that staleness, and the transaction exists so a failed correction never leaves a half-written
range behind.

#### FR-YEAR-006 — The atomic flag swap

Activation first asks `canBeActivated()` and refuses the already-active target — pressing
Activate on the current year is a no-op that should say so rather than churn events. Then,
inside one transaction, every currently-active row deactivates and the target activates, and
`AcademicYearActivated` carries both the new row and the previous one. The deactivate-all
(rather than deactivate-one) handles the pathological states defensively: even if some past
bug left two flags set, one activation restores the invariant instead of preserving the mess.

#### FR-YEAR-007 — Deletion that names its refusal

Active years and years with internships or assessments meet `RejectedException` with a
translatable message specific to the cause — the remedies differ, so the messages differ.
Safe rows delete inside a transaction with the deleted event and audit entry following. Like
every guard in this codebase, enforcement sits in the Action rather than the component, so
the console cleanup script and the UI button face identical refusals.

#### FR-YEAR-008 — Bulk validation before any deletion

The bulk Action loads every selected year, runs `canBeDeleted()` across all of them, and on
the first protected row aborts the entire operation with an exception naming that year —
zero rows deleted, nothing half-finished. When all rows pass, they delete inside one
transaction with a per-row deleted event and the count returned. The empty selection returns
zero without touching the database, because a batch of nothing is a valid batch that should
succeed quietly.

### 4.3 Events, Cache, Manager, and Policy

#### FR-YEAR-009 — Post-commit events with the handover attached

Four events — created, activated, updated, deleted — each carrying its row, with the activated
event additionally carrying the previous active year so the audit trail reads as a handover
rather than two disconnected flag edits. All dispatch after commit from the Actions that
earned them. A listener that ever needs "what changed from what" during activation finds both
ends already in hand.

#### FR-YEAR-010 — One listener for all year-shaped staleness

Dashboard aggregates key off the active year, so any year mutation — even a rename that moved
no flag — can stale them, and one listener clearing the registered dashboard-stats key on all
four events covers the whole surface. The key lives in `config/cache-keys.php` per the cache
registry rule, so the forgotten-string-literal class of bug (clearing `dashboard_stats` while
the dashboard reads `admin_dashboard_stats`) is impossible. Cross-module fan-out travels by
event rather than observer, per the observers ADR — the cache belongs to the dashboard, not
to the year.

#### FR-YEAR-011 — Manager with active-first ordering and confirmations

The manager pins the active year atop the list (`is_active` descending, name ascending)
because the question "which year are we in" should never require scrolling. Search, sorting,
and the three confirmation dialogs (activate, delete, bulk delete) complete the table, and
the stats line — total years, total internships, years carrying internships — gives the admin
the deletion-risk picture before any confirmation is ever opened.

#### FR-YEAR-012 — Tight name and range bounds

Fifty characters fits "2026/2027" with room for the odd "2026/2027 (Genap)" variant while
keeping the badge layout intact, and the after-rule on the end date is the entire defense
against inverted ranges. Uniqueness excludes the current row on update, mirroring the
department rule — the two specs share the shape deliberately so administrators meet one
consistent validation behavior across master data.

#### FR-YEAR-013 — Open reading, admin-group writing

Teachers checking which year a supervision visit belongs to, students confirming their
registration year — reading is universal. The four write verbs (create, update, activate,
delete) belong to the admin group, and the delete path additionally consults the entity guard
so authorization and business rules agree at the gate instead of contradicting each other at
runtime.

### 4.4 Gating, Period Computation, and Audit

#### FR-YEAR-014 — The active year as a validity boundary

This is the row the whole singleton exists to serve: registration, placement, and assessment
mutations resolve the active year first, and when none exists — or when the submitted date
falls outside its range — the Action throws `RejectedException` instead of writing. The
refusal message names the missing precondition in translatable language, because "no active
academic year" tells the admin exactly which settings page to visit. Without this row the
singleton would be decorative; with it, the timeline defends itself.

#### FR-YEAR-015 — One July–June computation for the whole system

Indonesian school years run July to June: a January date belongs to `2025/2026`, an October
date to `2026/2027`. Three places once computed this independently — the year seeder
month-aware and correct, the settings seeder and the UI default each hardcoded to a different
half-year guess — and for six months of every year two of them were wrong. The
`AcademicYearPeriod` support class now owns the `nameFor`, `startDateFor`, `endDateFor`, and
`yearsFor` computations as pure, unit-testable methods, and every consumer calls it.

#### FR-YEAR-016 — Dual-channel audit with PII masking

Year mutations rewrite the school's timeline, so each one writes a SmartLogger activity entry
with the actor and the change summary — including, for activation, the previous-year handover
— with PII masked before either sink per the dual-channel ADR. A future accreditation audit
asking "who moved the active year in July 2026" gets a complete answer from the activity
channel instead of a shrug and a log-file grep.

---

## 5. Non-Functional Requirements

Atomicity where flags move, feedback where deletions refuse, and the structural hygiene the
scans enforce. `Target` is `N/A` where enforcement is architectural rather than measured.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-YEAR-001 | Mutations are authorized at both the policy gate and the Action layer | N/A | P0 | A | Full |
| NFR-YEAR-002 | Year names are unique at the form, Action, and database layers | 0 duplicates | P0 | F | Full |
| NFR-YEAR-003 | Guard checks execute inside the same transaction as the guarded write | N/A | P0 | A | Full |
| NFR-YEAR-004 | Activation deactivates the old year and activates the new one atomically | 1 active year always | P0 | F | Full |
| NFR-YEAR-005 | Bulk delete is atomic: all selected rows removed or none | all-or-nothing | P1 | F | Full |
| NFR-YEAR-006 | Refusal messages state the cause and the remedy for blocked deletions | cause + remedy | P1 | F | Full |
| NFR-YEAR-007 | The active year is identifiable by text badge, never color alone | text + badge | P1 | B | Full |
| NFR-YEAR-008 | All PHP files declare strict types; entities and DTOs stay `final readonly` and framework-pure | 0 violations | P0 | A | Full |
| NFR-YEAR-009 | Every user-facing string passes through `__()` with mirrored `en` and `id` keys | 0 missing keys | P0 | A | Full |

### 5.1 Safety and Atomicity

#### NFR-YEAR-001 — Two gates, no back door

The HTTP path meets the policy before the Action's business rule; a direct Action call still
meets the business rule. Activation and deletion are the two operations where a bypass would
do the most damage — a silently doubled flag, a quietly destroyed history — so both
enforcement points are tested per mutation rather than assumed from the framework defaults.

#### NFR-YEAR-002 — Uniqueness at three altitudes

Form feedback for speed, Action validation for the API-shaped paths, the database constraint
for the concurrent double-submit. Year names double as the human identity of a twelve-month
period across reports and certificates, so a duplicate would not just look wrong — it would
join wrong. All three layers carry the same rule, and the losing request under contention
gets a clean rejection instead of a twin row.

#### NFR-YEAR-003 — Guards inside the transaction they protect

Checking deletability, then opening a transaction, then deleting, leaves a window where a
concurrent write attaches an internship to a year already judged safe. The guard evaluation
and the guarded write share one transaction, collapsing that window to the database's own
isolation semantics. It is a small ordering detail with an outsized blast radius if gotten
wrong, which is why it is written down rather than left to convention.

#### NFR-YEAR-004 — Never two flags, never zero gaps observed

The deactivate-all-then-activate-one sequence inside a single transaction is what makes the
singleton real rather than aspirational. Any observer — another request, a queued listener, a
report mid-render — sees exactly one active year before and exactly one after, because the
intermediate state never commits. The pathological double-flag state, should it ever arise
from older data, heals on the next activation instead of persisting.

#### NFR-YEAR-005 — Batches that refuse to half-finish

Validating every selected year before deleting any is the bulk counterpart of the guard
ordering above: the decision is complete before the first irreversible write. An aborted
batch deletes nothing and names the protected row, so retrying is a deselection rather than
an archaeology expedition into which rows survived.

### 5.2 Feedback and Structure

#### NFR-YEAR-006 — Refusals that distinguish their causes

An active-year refusal remedies to "activate a successor first"; a has-records refusal
remedies to "accept permanence or archive downstream first". One generic "cannot delete"
message would conflate two entirely different next steps, so the messages stay distinct and
translatable, and each names the concrete blocker the admin actually faces.

#### NFR-YEAR-007 — Active status readable without color vision

The active badge pairs its color with an explicit text label, because seven percent of the
relevant population cannot distinguish the green-versus-gray encoding alone. Form inputs
carry associated labels throughout the manager for the same reason: the year table is
operated by busy administrators, some of whom navigate entirely by keyboard, and none of whom
should need vision-dependent cues to find the school's temporal anchor.

#### NFR-YEAR-008 — Strict types and pure boundaries

Every PHP file declares strict types, and entities and DTOs remain `final readonly` value
objects free of persistence imports — enforced by the pre-commit scan batch, not by reviewer
memory. The entity predicates that guard deletion and activation run in millisecond unit
tests precisely because this boundary holds; the day an Entity imports a Model, those tests
stop being unit tests.

#### NFR-YEAR-009 — No hardcoded user strings

Headers, badges, toasts, confirmations, and validation messages all pass through `__()` with
keys mirrored across the Indonesian and English catalogs, verified by the D3 scan. The July
activation rush is staffed by whoever is available, in whichever language they read fastest —
no dialog in that flow may be hardcoded to just one.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 AcademicYear Model

```php
// app/Modules/Academics/AcademicYear/Models/AcademicYear.php
class AcademicYear extends BaseModel
{
    #[Fillable(['name', 'start_date', 'end_date', 'is_active'])]

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function internships(): HasMany;   // → Internship::class
    public function assessments(): HasMany;   // → Assessment::class
    public function asAcademicYearState(): AcademicYearState;
}
```

### 6.2 AcademicYearState Entity

```php
// app/Modules/Academics/AcademicYear/Entities/AcademicYearState.php
final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
        private bool $hasRelatedRecords = false,
    ) {}

    public static function fromModel(Model $model): static;
    // isActive from $model->is_active;
    // hasRelatedRecords from internships()->exists() || assessments()->exists()

    public function isActive(): bool;
    public function hasRelatedRecords(): bool;
    public function canBeActivated(): bool;  // !isActive (FR-YEAR-003)
    public function canBeDeleted(): bool;    // !isActive && !hasRelatedRecords (FR-YEAR-003)
}
```

### 6.3 AcademicYearData DTO

```php
// app/Modules/Academics/AcademicYear/Data/AcademicYearData.php
final readonly class AcademicYearData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public bool $isActive = false,
        public ?string $id = null,
    ) {}
}
```

### 6.4 Actions

| Action | Accepts | Returns | Event |
| ------ | ------- | ------- | ----- |
| `CreateAcademicYearAction` | `array $data` | `AcademicYear` | `AcademicYearCreated` |
| `UpdateAcademicYearAction` | `AcademicYear, array $data` | `AcademicYear` | `AcademicYearUpdated` |
| `ActivateAcademicYearAction` | `AcademicYear` | `AcademicYear` | `AcademicYearActivated` |
| `DeleteAcademicYearAction` | `AcademicYear` | `void` | `AcademicYearDeleted` |
| `BulkDeleteAcademicYearsAction` | `array $ids` | `int` (count) | `AcademicYearDeleted` per row |

All extend `BaseCommandAction`; all mutations run inside `$this->transaction()`
(FR-YEAR-004/005/006/007/008). Validation: name required, string, max 50, unique
(excluding current id on update); both dates required dates; end date after start date
(FR-YEAR-012).

### 6.5 Events and Listener

| Event | Carries | Dispatched by |
| ----- | ------- | ------------- |
| `AcademicYearCreated` | created row | `CreateAcademicYearAction` |
| `AcademicYearActivated` | activated row + `previousActive: ?AcademicYear` | `ActivateAcademicYearAction` |
| `AcademicYearUpdated` | updated row | `UpdateAcademicYearAction` |
| `AcademicYearDeleted` | deleted row | `DeleteAcademicYearAction`, bulk action |

All extend `BaseEvent` with `eventName(): string`. The single
`ClearDashboardCacheOnYearChange` listener handles all four and calls
`Cache::forget(config('cache-keys.admin_dashboard_stats'))` (FR-YEAR-010).

### 6.6 Policy and Period Support

`AcademicYearPolicy` extends `BasePolicy`: `viewAny`/`view` open to all authenticated users;
`create`/`update`/`activate`/`delete` restricted to the admin group, with delete additionally
consulting `canBeDeleted()` (FR-YEAR-013).

```php
// app/Modules/Academics/AcademicYear/Support/AcademicYearPeriod.php
final class AcademicYearPeriod
{
    public static function nameFor(Carbon $date): string;       // "2025/2026"
    public static function startDateFor(Carbon $date): string;  // July 1 of the school year
    public static function endDateFor(Carbon $date): string;    // June 30 of the school year
}
```

January–June maps to `Y-1/Y`; July–December maps to `Y/Y+1` (FR-YEAR-015).

### 6.7 Routes and Schema

Single Livewire route behind authentication and the admin-group gate:
`GET /admin/academic-years` → `AcademicYearManager`
(middleware `auth`, `role:super_admin|admin`).

```
academic_years:
  id:         uuid (PK, v7)
  name:       varchar(50) (unique, not null)
  start_date: date (not null)
  end_date:   date (not null, after start_date)
  is_active:  boolean (default false)
  created_at: timestamp (nullable)
  updated_at: timestamp (nullable)

Migration: database/migrations/2026_01_03_000001_create_academic_years_table.php
```

---

## 7. Design Decisions

One table of the calls that shaped this spec. Each decision below is load-bearing: reversing
any of them reopens the incident or the drift it was written to close.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-YEAR-001 | Singleton enforced by the activation Action inside a transaction, not by a partial unique index | P0 | — | — |
| DD-YEAR-002 | Deletion business rules live in the entity; the policy carries authorization only | P0 | — | — |
| DD-YEAR-003 | Bulk delete aborts the whole batch on the first protected row | P1 | — | — |
| DD-YEAR-004 | Dashboard invalidation is one listener on four events via the registered cache key | P1 | — | — |
| DD-YEAR-005 | First-year auto-activation lives in the create Action, keyed on an empty table | P0 | — | — |
| DD-YEAR-006 | The active year is the July–June school year containing today, computed in exactly one place | P1 | — | — |

### 7.1 Enforcement Points and Batch Semantics

#### DD-YEAR-001 — The swap, not the constraint, guards the singleton

A partial unique index on the flag would have forbidden the very multi-row update the swap
needs: deactivating the old year and activating the new one cannot both commit if the database
rejects the intermediate state. Action-level enforcement inside a transaction gives the same
guarantee with room for the two-step write, and the deactivate-all phrasing heals historical
double-flag data instead of tripping over it. The residual risk is a true concurrent race,
accepted at school scale and mitigated by transaction isolation rather than schema ceremony.

#### DD-YEAR-002 — Rules in the entity, roles in the policy

The policy answers "is this caller an admin"; the entity answers "is this year deletable".
Merging the two once produced policy methods running existence queries against internships —
authorization code reaching across module boundaries into persistence it had no business
touching. Separating them keeps each independently testable (the entity without auth, the
policy without a database of internships) and means the console cleanup path, which never
meets a policy, still meets the rule.

#### DD-YEAR-003 — Abort, because years are history

Departments forgive a blocked row and continue the batch; years refuse the batch over a
single protected row. The asymmetry is intentional: deleting a department removes an org-chart
label, while deleting a year removes the temporal container of permanent records, and a batch
that half-deletes containers deserves to fail loudly rather than succeed confusingly. The
exception names the offending year so the retry is one deselection, keeping the strictness
cheap to live with.

### 7.2 Cache, Bootstrap, and Calendar

#### DD-YEAR-004 — Every mutation clears, even the harmless-looking rename

A rename moves no flag and touches no relation, yet the dashboard quotes year names from
cache — so a unified listener on all four events clears unconditionally rather than asking
each event whether it "really" changed anything. The over-invalidation costs one cheap cache
rebuild; the under-invalidation it replaces cost a planning meeting run on stale figures. The
registered key removes the string-literal mismatch class of bug entirely.

#### DD-YEAR-005 — Bootstrap by counting, in the Action, not the UI

Putting the empty-table check in the Livewire component would have given seeders and console
creation a dormant first year and the UI an active one — two creation paths, two behaviors,
one confused administrator wondering why the seeded install cannot register students. The
Action-layer check applies uniformly regardless of caller, and its implicitness is repaid the
first time a fresh install simply works.

#### DD-YEAR-006 — One calendar computation ends a three-way disagreement

The settings seeder guessed `Y-1/Y` always, the UI default guessed `Y/Y+1` always, and the
year seeder actually looked at the month — so for half of every calendar year, two of the
three disagreed about which school year today belongs to. Centralizing the July–June mapping
in `AcademicYearPeriod` and pointing all three consumers at it removed the duplication and,
with it, the drift. The methods are pure functions over a date, which makes the calendar
itself unit-testable — including the June 30 to July 1 boundary where school years turn over.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Coexisting active years | 0 at any time | Singleton feature test asserting one flag after activation storms |
| Deletions of active or referenced years | 0 | Guard unit tests plus delete-action feature tests |
| First-year installs without an active year | 0 | Bootstrap feature test on an empty table |
| Downstream writes accepted outside the active window | 0 | Gating feature tests with dormant-year payloads |
| Stale dashboard figures after year changes | 0 | Listener test asserting cache forget per event |
| Hardcoded user-facing strings | 0 | D3 scan on the module |

---

## 9. Roadmap

### Prerequisites

The school profile spec ([81SMS](81SMS-school-profile.md)) must be complete first, since years
are scoped to the school it defines, and the settings infrastructure
([YB22J](YB22J-settings-infrastructure.md)) hosts the active-year setting that mirrors the
active row.

### Build Guide

With this spec implemented, the system has a trustworthy temporal anchor: one active year,
guarded history, and downstream writes that respect the window. Partnerships, internships,
and placements all date themselves against this anchor, so this spec unlocks the phases that
schedule within years.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [company-management](XI3LB-company-management.md) | Companies offer internship slots inside academic-year date ranges |
| 2 | [partnership-management](NTHQA-partnership-management.md) | Partnership validity windows are interpreted against the active year |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the Indonesian school year runs July–June for every deploying school; a school on a different calendar needs the period computation amended first | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad, entity split, and event contracts this spec builds on
- [Spec-zero](QLHDO-project-initialization.md) — global requirements (FR-GLB-002/004/007/008/010) inherited here
- [Department management](4HWSB-department-management.md) — organizational counterpart to years
- [Partnership management](NTHQA-partnership-management.md) — validity windows gated by the active year
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — closure and archival of finished year cohorts
- [ADR: action pattern over services](../adr/adr-action-pattern-over-services.md) — why one Action per operation
- [ADR: entity-model separation](../adr/adr-entity-model-separation.md) — why predicates live in the entity
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` as the business-refusal voice
- [ADR: smartlogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — activity audit with PII masking
- [ADR: flat RBAC](../adr/adr-flat-rbac-with-functional-roles.md) — admin-group gating for writes
- [ADR: program closure & archival](../adr/adr-program-closure-archival.md) — terminal lifecycle handling for finished cohorts
