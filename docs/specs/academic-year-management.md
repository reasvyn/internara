# Academic Year Management — Singleton Activation & Lifecycle

> **Last updated:** 2026-07-22 **Changes:** feat — split from institutional-and-academics.md;
> expanded AcademicYear CRUD, singleton activation, deletion guard, bulk delete, events, and
> dashboard cache invalidation into a dedicated spec

## Description

Specification of Internara's academic year lifecycle: CRUD operations for academic year records,
singleton activation enforcement (only one year active at any time), deletion guards against
related records (internships, assessments), bulk deletion with protected-year skipping, domain
events on every mutation, and dashboard cache invalidation on state changes.

---

## 1. Problem Statements

### PS-1 — Academic Year Activation Singleton

Only one academic year can be active at a time. Internship assignments, registration periods,
assessment deadlines, and reporting dashboards all reference the "active" academic year as the
temporal anchor. Without a singleton guard, multiple years could be marked active simultaneously,
causing ambiguous queries, incorrect statistics, and conflicting registration windows.

### PS-2 — Academic Year Deletion With Related Records

Academic years are referenced by internships and assessments via foreign key relationships.
Deleting a year with active internships or completed assessments would orphan those records,
breaking referential integrity and destroying historical audit trails.

### PS-3 — First Academic Year Bootstrap

During initial setup or after a system reset, the first academic year created must automatically
become the active year. Without this bootstrap logic, administrators would need to create a year
and then separately activate it — an unintuitive two-step process for what should be a single
action.

### PS-4 — Bulk Deletion With Mixed Protection States

Administrators may need to clean up multiple obsolete academic years at once. The bulk delete
must validate each year individually and provide clear feedback about which are protected.

### PS-5 — Dashboard Cache Coherence

Dashboard statistics reference the active academic year for current-period metrics. When the
active year changes, cached dashboard data becomes stale. Without automatic cache invalidation,
administrators would see outdated statistics.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for academic year records (create, read, update, delete) |
| G2  | Enforce singleton activation — only one academic year can be active at a time |
| G3  | Auto-activate the first academic year created when no years exist |
| G4  | Block deletion of active years and years with related records (internships, assessments) |
| G5  | Support bulk deletion with per-year protection validation |
| G6  | Dispatch domain events on every academic year mutation |
| G7  | Invalidate dashboard cache on every academic year state change |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Academic year archiving or soft-delete |
| NG2  | Academic year duplication or cloning |
| NG3  | Automatic year rollover at end date |
| NG4  | Multiple concurrent active years (singleton enforced by design) |
| NG5  | Integration with external academic calendar systems |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates an Academic Year

**Actor:** Admin | **Preconditions:** Admin authenticated with admin role
**Flow:**
1. Navigate to `admin/academic-years` → `AcademicYearManager` shows list
2. Click "Create" → fill `name` (unique, max 50), `start_date`, `end_date`
3. `CreateAcademicYearAction` validates, creates model with `is_active = false`
4. Dispatches `AcademicYearCreated` → cache invalidated
**Postconditions:** Year created, dashboard cache invalidated

### UC-2 — Admin Creates First Academic Year (Auto-Activate)

**Actor:** Admin (setup/reset) | **Preconditions:** No academic years exist
**Flow:**
1. Navigate to Academic Years (empty list) → click "Create"
2. Fill name, dates → `CreateAcademicYearAction` detects zero years → `is_active = true`
3. Dispatches `AcademicYearCreated`
**Postconditions:** First year created and automatically active

### UC-3 — Admin Activates a New Academic Year

**Actor:** Admin | **Preconditions:** A different year is currently active
**Flow:**
1. Click "Activate" on non-active year → confirmation dialog
2. Confirm → `ActivateAcademicYearAction`:
   - Validates `canBeActivated()` — rejects if already active
   - Deactivates current active year, activates target (same transaction)
   - Dispatches `AcademicYearActivated` with `previousActive`
3. Cache invalidated
**Postconditions:** Only one year active

### UC-4 — Admin Deletes an Academic Year

**Actor:** Admin | **Preconditions:** Year not active, no related records
**Flow:**
1. Click "Delete" → confirmation → `DeleteAcademicYearAction`
2. If active → `RejectedException`("cannot delete active")
3. If has data → `RejectedException`("cannot delete has data")
4. If safe → deletes in transaction, dispatches `AcademicYearDeleted`
**Postconditions:** Year deleted, cache invalidated

### UC-5 — Admin Bulk Deletes Academic Years

**Actor:** Admin | **Preconditions:** At least one deletable year
**Flow:**
1. Select multiple years → "Delete Selected" → confirmation
2. `BulkDeleteAcademicYearsAction` validates EVERY year before deleting any
3. If ANY protected → `RejectedException` naming that year
4. If all safe → deletes in transaction, `AcademicYearDeleted` per year
**Postconditions:** All selected years deleted

---

## 4. Functional Requirements

### Model & Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-AY1 | `AcademicYear` model must use `#[Fillable]` with `name`, `start_date`, `end_date`, `is_active` |
| FR-AY2 | `AcademicYear` must cast `start_date` → date, `end_date` → date, `is_active` → boolean |
| FR-AY3 | `AcademicYear` must have `hasMany` relationship with `Internship` and `Assessment` models |
| FR-AY4 | `AcademicYear` must provide `asAcademicYearState()` bridge returning `AcademicYearState` |
| FR-AY5 | `AcademicYearState` must be `final readonly` extending `BaseEntity` |
| FR-AY6 | `AcademicYearState::fromModel()` must resolve `isActive` from `$model->is_active` and `hasRelatedRecords` from `internships()->exists()` + `assessments()->exists()` |
| FR-AY7 | `AcademicYearState::canBeActivated()` must return `false` when `isActive` is `true` |
| FR-AY8 | `AcademicYearState::canBeDeleted()` must return `false` when `isActive` or `hasRelatedRecords` |

### Singleton Activation

| ID   | Requirement |
| ---- | ----------- |
| FR-AY9 | `ActivateAcademicYearAction` must reject if `canBeActivated()` returns `false` |
| FR-AY10 | `ActivateAcademicYearAction` must deactivate ALL currently active years within a transaction |
| FR-AY11 | `ActivateAcademicYearAction` must activate the target year in the same transaction |
| FR-AY12 | `ActivateAcademicYearAction` must dispatch `AcademicYearActivated` event |

### Deletion & Bulk Delete

| ID   | Requirement |
| ---- | ----------- |
| FR-AY13 | `DeleteAcademicYearAction` must reject with `RejectedException` if year is active or has related records |
| FR-AY14 | `DeleteAcademicYearAction` must dispatch `AcademicYearDeleted` event after deletion |
| FR-AY15 | `BulkDeleteAcademicYearsAction` must return `0` for empty input |
| FR-AY16 | `BulkDeleteAcademicYearsAction` must validate every year via `canBeDeleted()` before deleting any |
| FR-AY17 | `BulkDeleteAcademicYearsAction` must throw `RejectedException` naming the violating year if any is protected |
| FR-AY18 | `BulkDeleteAcademicYearsAction` must dispatch `AcademicYearDeleted` per deleted year and return count |

### First-Year Auto-Activate

| ID   | Requirement |
| ---- | ----------- |
| FR-AY19 | `CreateAcademicYearAction` must check `AcademicYear::count()` before creation |
| FR-AY20 | `CreateAcademicYearAction` must set `is_active = true` when creating the first year |
| FR-AY21 | `CreateAcademicYearAction` must set `is_active = false` for subsequent years |
| FR-AY22 | `CreateAcademicYearAction` must validate name uniqueness, start_date required, end_date after start_date |
| FR-AY23 | `CreateAcademicYearAction` must dispatch `AcademicYearCreated` event |

### Update

| ID   | Requirement |
| ---- | ----------- |
| FR-AY24 | `UpdateAcademicYearAction` must persist changes within a transaction |
| FR-AY25 | `UpdateAcademicYearAction` must dispatch `AcademicYearUpdated` event |

### Events & Cache

| ID   | Requirement |
| ---- | ----------- |
| FR-AY26 | `AcademicYearCreated` must carry the created model |
| FR-AY27 | `AcademicYearActivated` must carry the activated model and optional `previousActive` |
| FR-AY28 | `AcademicYearUpdated` must carry the updated model |
| FR-AY29 | `AcademicYearDeleted` must carry the deleted model |
| FR-AY30 | All four events must trigger `ClearDashboardCacheOnYearChange` listener |
| FR-AY31 | `ClearDashboardCacheOnYearChange` must call `Cache::forget(config('cache-keys.admin_dashboard_stats'))` |

### Livewire UI & Policy

| ID   | Requirement |
| ---- | ----------- |
| FR-AY32 | `AcademicYearManager` must extend `BaseRecordManager` with paginated list, search, and sorting |
| FR-AY33 | `AcademicYearManager` must display columns: name, start_date, end_date, is_active, actions |
| FR-AY34 | `AcademicYearManager` must sort active year first (`is_active DESC, name ASC`) |
| FR-AY35 | `AcademicYearManager` must provide confirmation dialogs for activate, delete, and bulk-delete |
| FR-AY36 | `AcademicYearManager` must display stats: total years, total internships, years with internships |
| FR-AY37 | `AcademicYearForm` must validate: name (required, string, max 50, unique excluding current), start_date (required, date), end_date (required, date, after start_date) |
| FR-AY38 | `AcademicYearPolicy` must grant `viewAny`/`view` to all authenticated users |
| FR-AY39 | `AcademicYearPolicy` must grant `create`/`update` to admin roles only |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Academic year activation must complete in < 1s (two UPDATEs + event in transaction) |
| NFR-P2 | `AcademicYearManager` page load must complete in < 500ms |
| NFR-S1 | All mutations must be authorized via `AcademicYearPolicy` |
| NFR-S2 | `name` uniqueness enforced at both form validation and Action layer |
| NFR-S3 | Deletion guard checks must execute within the same transaction as the delete |
| NFR-R1 | Activation must be atomic — deactivate old + activate new in single transaction |
| NFR-R2 | Bulk delete must be atomic — all years deleted or none |
| NFR-U1 | Active year must be visually highlighted in the manager with a badge |
| NFR-U2 | Deletion blocked messages must explain why (active / has data) and how to resolve |
| NFR-U3 | Confirmation dialogs must appear before activate, delete, and bulk-delete |
| NFR-A1 | All UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Active year indicator must include text label alongside color |
| NFR-A3 | All form inputs must have associated labels |
| NFR-M1 | All PHP files must declare `strict_types=1` |
| NFR-M2 | All user-facing strings must use `__()` translation helper |
| NFR-M3 | Translation keys must exist in both `lang/en/` and `lang/id/` |

---

## 6. API / Data Contracts

### 6.1 AcademicYear Model

```php
// app/Academics/AcademicYear/Models/AcademicYear.php
class AcademicYear extends BaseModel
{
    #[Fillable(['name', 'start_date', 'end_date', 'is_active'])]
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];
    public function internships(): HasMany;
    public function assessments(): HasMany;
    public function asAcademicYearState(): AcademicYearState;
}
```

### 6.2 AcademicYearState Entity

```php
// app/Academics/AcademicYear/Entities/AcademicYearState.php
final readonly class AcademicYearState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
        private bool $hasRelatedRecords = false,
    ) {}
    public static function fromModel(Model $model): static;
    public function isActive(): bool;
    public function hasRelatedRecords(): bool;
    public function canBeActivated(): bool;   // !isActive
    public function canBeDeleted(): bool;     // !isActive && !hasRelatedRecords
}
```

### 6.3 AcademicYearData DTO

```php
// app/Academics/AcademicYear/Data/AcademicYearData.php
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

| Action | Accepts | Returns |
| ------ | ------- | ------- |
| `CreateAcademicYearAction` | `array $data` | `AcademicYear` |
| `UpdateAcademicYearAction` | `AcademicYear $year, array $data` | `AcademicYear` |
| `ActivateAcademicYearAction` | `AcademicYear $year` | `AcademicYear` |
| `DeleteAcademicYearAction` | `AcademicYear $year` | `void` |
| `BulkDeleteAcademicYearsAction` | `array $ids` | `int` (count) |

All extend `BaseCommandAction`. All mutations wrapped in `$this->transaction()`.

### 6.5 Events

| Event | Properties | Dispatched By |
| ----- | ---------- | ------------- |
| `AcademicYearCreated` | `academicYear` | `CreateAcademicYearAction` |
| `AcademicYearActivated` | `academicYear`, `previousActive: ?AcademicYear` | `ActivateAcademicYearAction` |
| `AcademicYearUpdated` | `academicYear` | `UpdateAcademicYearAction` |
| `AcademicYearDeleted` | `academicYear` | `DeleteAcademicYearAction`, `BulkDeleteAcademicYearsAction` |

All extend `BaseEvent`. Each implements `eventName(): string`.

### 6.6 Listeners

| Listener | Event(s) | Action |
| -------- | -------- | ------ |
| `ClearDashboardCacheOnYearChange` | Created, Activated, Updated, Deleted | `Cache::forget(config('cache-keys.admin_dashboard_stats'))` |

### 6.7 Policy

`AcademicYearPolicy` extends `BasePolicy`: `viewAny`/`view` → all authenticated; `create`/`update` → admin roles; `activate`/`delete` → `false` (delegated to Action layer).

### 6.8 Routes

Single route: `GET /admin/academic-years` → `AcademicYearManager` (name: `sysadmin.academic-years`, middleware: `auth`, `role:super_admin|admin`)

### 6.9 Database Schema

`academic_years`: `id` (uuid PK), `name` (string, unique, max 50), `start_date` (date), `end_date` (date), `is_active` (boolean, default false), timestamps. Migration: `2026_01_03_000001_create_academic_years_table.php`.

### 6.10 Livewire Component

`AcademicYearManager` extends `BaseRecordManager`. Properties: `$showModal`, `$showConfirm`, `$confirmMessage`, `$confirmType` (`'activate'`|`'delete'`|`'delete_selected'`), `$confirmTarget`, `$editingYearId`, `$form` (AcademicYearForm). Methods: `create()`, `edit()`, `store()`, `update()`, `askActivate()`, `askDestroy()`, `askDeleteSelected()`, `confirmAction()`, `stats()` (computed: total, totalInternships, withInternships).

---

## 7. Design Decisions

### DD-1 — Activation Singleton Enforced at Action Level, Not DB Constraint

**Decision:** Singleton enforced by `ActivateAcademicYearAction` deactivating all active years
before activating the target, within a single transaction. No unique partial index on `is_active`.

**Rationale:** A partial unique index (`WHERE is_active = true`) would prevent the multi-row
UPDATE needed to deactivate the old year inside the transaction. Action-level enforcement
within a transaction provides the same guarantee while allowing the clean two-step UPDATE.

**Trade-off:** Concurrent requests could race without the DB constraint. Mitigated by transaction
serialization. Rejected: partial unique index (prevents transactional deactivation).

### DD-2 — Deletion Guard via Entity, Not Policy

**Decision:** Business rules (can/cannot delete) live in `AcademicYearState`, not in
`AcademicYearPolicy`. Policy handles authorization only (is admin → can).

**Rationale:** Separates business rules from authorization. Entity is independently testable
without mocking auth. Policy focuses on role checks. Action calls both.

**Trade-off:** Extra class for boolean checks. Mitigated by Entity reuse for `canBeActivated()`
and project-wide Entity pattern adherence.

### DD-3 — Bulk Delete Aborts on First Protected Year

**Decision:** `BulkDeleteAcademicYearsAction` validates ALL years before deleting any. If ANY
is protected, the entire operation aborts with a `RejectedException` naming the violating year.

**Rationale:** Silent partial deletion risks accidental data loss and confusing UI state.
Aborting with a clear error lets the user deselect the protected year and retry.

**Trade-off:** Multi-step cleanup required when some years are protected. Mitigated by clear
error message naming the specific violating year.

### DD-4 — Dashboard Cache Invalidation via Unified Listener

**Decision:** Single `ClearDashboardCacheOnYearChange` listener handles all four events by
calling `Cache::forget(config('cache-keys.admin_dashboard_stats'))`.

**Rationale:** Dashboard stats depend on the active year. Any mutation could change state.
Single listener avoids proliferation and ensures no event is missed. Cache key registered in
`config/cache-keys.php` per C4.

**Trade-off:** Cache invalidated even for non-activation updates (e.g., rename). Mitigated by
cheap cache rebuild (< 50ms).

### DD-5 — First Year Auto-Activate at Action Layer

**Decision:** `CreateAcademicYearAction` checks `AcademicYear::count()` and sets
`is_active = true` when creating the first year.

**Rationale:** During setup, the first year must be active for registrations/internships to
have temporal context. Auto-activate in the Action (not Livewire) applies regardless of
creation source (UI, seeder, API).

**Trade-off:** Implicit behavior. Mitigated by UI showing active badge immediately.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Multiple active years | 0 at any time | Transaction: deactivate all → activate one |
| Activation rejection (already active) | 100% | `canBeActivated()` returns false |
| Active year deletion blocked | 100% | `DeleteAcademicYearAction` rejects when `isActive()` |
| Related records deletion blocked | 100% | `DeleteAcademicYearAction` rejects when `hasRelatedRecords()` |
| Bulk delete protection | All-or-nothing | Validates every year before deleting any |
| First year auto-active | `is_active = true` | Count check in `CreateAcademicYearAction` |
| Subsequent years inactive | `is_active = false` | Default in `CreateAcademicYearAction` |
| Activation completes | < 1s | Two UPDATEs + event in transaction |
| Manager page load | < 500ms | Paginated query with sorting |
| All mutations dispatch events | 100% | Create/Activate/Update/Delete → respective events |
| Dashboard cache invalidation | On every event | Listener registered for all 4 events |
| Guard error messages include year name | 100% | `RejectedException` with `['name' => $year->name]` |

---

## Quick References

- `app/Academics/AcademicYear/` — All AcademicYear module code (Models, Entities, Actions, Events, Data, Livewire, Policies)
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php` — Dashboard cache invalidation listener
- `routes/web/academics.php` — Route definitions (admin prefix, auth + role middleware)
- `database/migrations/2026_01_03_000001_create_academic_years_table.php` — Schema migration
- `config/event.php` — Event-to-listener registration
- `lang/en/academic_year.php` / `lang/id/academic_year.php` — Translations
- **Related specs:** [school-profile.md](school-profile.md) — School entity; [department-management.md](department-management.md) — Department CRUD
- **Related modules:** [docs/modules/academics.md](../modules/academics.md) — Academics module overview
