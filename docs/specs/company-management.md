# Company Management — CRUD, Deletion Guards, CSV Import & Dashboard Stats

> **Last updated:** 2026-07-22 **Changes:** Split from `partnership.md` — company management
> portion extracted into standalone spec covering company CRUD, dual deletion guards, CSV
> import/export, and dashboard statistics

## Description

Complete specification of the Internara company management feature: company profile CRUD with
admin-only write access, dual deletion guards preventing orphaned dependent records, CSV
import/export for bulk onboarding with per-row validation, and dashboard statistics aggregating
company counts and available placement slots.

---

## 1. Problem Statements

### PS-1 — Company-Partnership-Placement Data Integrity Chain

A company may have multiple partnerships over time, and each partnership provides placement slots
for interns. Deleting a company that has active placements or partnerships would orphan enrollment
records and break the integrity chain. The system must enforce referential guards at both the Action
layer and the Entity layer to prevent destructive operations on companies with dependent data.

### PS-2 — Dual Deletion Guard Layers for Company Records

A company deletion guard at the Action layer (querying for related placements and partnerships) may
race with concurrent requests. The Entity layer must provide a second guard via `withCount`
aggregation that reflects the model's current state at decision time. Both layers must agree before
deletion proceeds. Without this dual approach, a company could be deleted while a concurrent request
creates a new placement or partnership, leading to orphaned records.

### PS-3 — Bulk Company Onboarding via CSV Import

Schools often have dozens of existing partner companies to onboard. Manual entry is error-prone and
time-consuming. The system must support CSV import with validation, deduplication by name, error
reporting per row, and a downloadable template for correct column formatting.

### PS-4 — Placement Capacity Visibility Across Companies

Administrators need visibility into total available placement slots across all companies (sum of
`quota - filled_quota` per active partnership). Without real-time aggregation, capacity planning
decisions are based on stale data. The system must compute available slots accurately and surface
company counts and capacity statistics in a dashboard.

### PS-5 — Cache Invalidation on Company Mutations

Dashboard statistics (company counts, available slots, partnership summaries) rely on cached values.
When a company is created, updated, or deleted, cached statistics become stale. The system must
invalidate dashboard cache automatically on every company mutation via domain events and listeners
to ensure administrators always see current data.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for company profiles with admin-only write access and public read |
| G2  | Track placement capacity (available slots) per company and aggregate across all companies |
| G3  | Support CSV import/export for companies with per-row validation, deduplication, and error reporting |
| G4  | Enforce dual deletion guards (Action query + Entity `withCount`) to prevent orphaning dependent records |
| G5  | Dispatch domain events on all company mutations with cache invalidation listeners for dashboard freshness |
| G6  | Expose dashboard statistics including total companies, companies with placements, active partnerships, and available slots |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Partnership (MoU) lifecycle management — covered in [partnership-management.md](partnership-management.md) |
| NG2  | Student placement assignment — owned by Enrollment module |
| NG3  | Internship program definitions and scheduling — owned by Program module |
| NG4  | Certificate issuance for completed internships — owned by Certification module |
| NG5  | MoU document uploads, conversions, or versioning — covered in [partnership-management.md](partnership-management.md) |
| NG6  | Partnership status transitions, termination, or renewal — covered in [partnership-management.md](partnership-management.md) |
| NG7  | Multi-tenant support or cross-school company sharing |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Company Profile

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `admin/companies`
2. Clicks "Add Company" button; `CompanyManager` opens modal with `CompanyForm`
3. Admin fills in required field `name` and optional fields (`address`, `phone`, `email`, `website`, `description`, `industry_sector`)
4. `CompanyForm` validates input via Livewire real-time rules
5. On submit, `CompanyManager` calls `CreateCompanyAction::execute(CompanyData)`
6. `CreateCompanyAction` creates `Company` model, dispatches `CompanyCreated` event
7. `ClearDashboardOnCompanyChange` listener invalidates dashboard cache
**Postconditions:** Company record exists, dashboard cache cleared, success flash message shown

### UC-2 — Admin Updates a Company Profile

**Actor:** Admin
**Preconditions:** Company exists; admin has `admin` role
**Flow:**
1. Admin views company list in `CompanyManager`
2. Clicks edit action on a company row; `CompanyManager` opens modal with `CompanyForm` pre-filled
3. Admin modifies fields as needed
4. `CompanyForm` validates updated input via Livewire real-time rules
5. On submit, `CompanyManager` calls `UpdateCompanyAction::execute(Company, CompanyData)`
6. `UpdateCompanyAction` updates model, dispatches `CompanyUpdated` event
7. `ClearDashboardOnCompanyChange` listener invalidates dashboard cache
**Postconditions:** Company record updated, dashboard cache cleared, success flash message shown

### UC-3 — Admin Deletes a Single Company

**Actor:** Admin
**Preconditions:** Company exists; admin has `admin` role
**Flow:**
1. Admin views company list in `CompanyManager`
2. Clicks delete action on a company row
3. `DeleteCompanyAction::execute(Company)` is called
4. Action queries for related placements and partnerships (Action-layer guard)
5. `CompanyState::canBeDeleted()` verifies `placementCount === 0` and `partnershipCount === 0` (Entity-layer guard)
6. If both guards pass, company is soft-deleted and `CompanyDeleted` event is dispatched
7. `ClearDashboardOnCompanyChange` listener invalidates dashboard cache
8. If either guard fails, `RejectedException` is thrown with translated reason
**Postconditions:** Company deleted (or rejection shown), dashboard cache cleared

### UC-4 — Admin Batch Deletes Companies with Deletion Guards

**Actor:** Admin
**Preconditions:** Multiple companies selected; admin has `admin` role
**Flow:**
1. Admin selects multiple companies in `CompanyManager` via checkboxes
2. Clicks "Delete Selected" batch action
3. `BatchDeleteCompanyAction` iterates selected IDs
4. For each record: checks entity `canBeDeleted()` — returns false if placements or partnerships exist
5. Records that pass deletion guard are soft-deleted; records that fail are skipped
6. Returns count of deleted vs skipped records with flash message explaining reasons
**Postconditions:** Only eligible records deleted, ineligible records preserved, summary reported

### UC-5 — Admin Imports Companies via CSV

**Actor:** Admin
**Preconditions:** Admin has `admin` role; CSV file prepared (or template downloaded)
**Flow:**
1. Admin navigates to `admin/companies`
2. Clicks "Import CSV" button, selects CSV file
3. `CompanyManager` processes CSV rows via `CsvHandler` integration
4. Each row validated: required `name` column present, optional columns parsed
5. Deduplication check: rows with name matching existing company are marked as duplicates
6. Valid rows are created as Company records within a database transaction
7. Per-row results reported using `CsvRowResult` enum (created, duplicate, error)
8. Summary displayed: X created, Y duplicates, Z errors
**Postconditions:** Companies created for valid rows, duplicates/errors reported per-row

### UC-6 — Admin Exports Companies to CSV

**Actor:** Admin
**Preconditions:** Admin has `admin` role; at least one company exists
**Flow:**
1. Admin navigates to `admin/companies`
2. Clicks "Export CSV" button
3. `CompanyManager` queries all company records with all fields
4. CSV file generated with headers: `name`, `address`, `phone`, `email`, `website`, `description`, `industry_sector`
5. Browser initiates file download
**Postconditions:** CSV file downloaded containing all company records

### UC-7 — Admin Downloads CSV Import Template

**Actor:** Admin
**Preconditions:** Admin has `admin` role
**Flow:**
1. Admin navigates to `admin/companies`
2. Clicks "Download Template" button in CSV import section
3. `CompanyManager` generates CSV file with correct column headers and no data rows
4. Browser initiates file download
**Postconditions:** CSV template file downloaded with correct headers

---

## 4. Functional Requirements

### Company CRUD

| ID   | Requirement |
| ---- | ----------- |
| FR-CC1 | `CompanyManager` must be accessible at route `admin/companies` with `auth` and `role:super_admin\|admin` middleware |
| FR-CC2 | `CreateCompanyAction` must accept `CompanyData` DTO and return `ActionResponse` |
| FR-CC3 | `CompanyData` must require `name` (string); `address`, `phone`, `email`, `website`, `description`, `industrySector` must be nullable strings |
| FR-CC4 | `Company` model must use `#[Fillable]` attribute with all seven fields: `name`, `address`, `phone`, `email`, `website`, `description`, `industry_sector` |
| FR-CC5 | `CompanyPolicy::create()`, `update()`, `delete()` must require admin role via `isAdmin()` check |
| FR-CC6 | `CompanyPolicy::delete()` must additionally check `$company->placements()->exists()` and deny if true |
| FR-CC7 | `CompanyPolicy::viewAny()` and `view()` must return true for all authenticated users |
| FR-CC8 | `DeleteCompanyAction` must throw `RejectedException` if `CompanyState::canBeDeleted()` returns false |
| FR-CC9 | `BatchDeleteCompanyAction` must iterate selected IDs, check `canBeDeleted()` per record, and delete only eligible records |
| FR-CC10 | `CompanyState::canBeDeleted()` must require both `placementCount === 0` and `partnershipCount === 0` |
| FR-CC11 | `Company` model must provide `asCompanyState()` bridge method returning `CompanyState::fromModel()` |
| FR-CC12 | Company mutations must dispatch `CompanyCreated`, `CompanyUpdated`, or `CompanyDeleted` events |
| FR-CC13 | `ClearDashboardOnCompanyChange` listener must handle all three company events and invalidate dashboard cache |

### CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-CV1 | `CompanyManager` must support CSV import via `CsvHandler` integration |
| FR-CV2 | CSV import must validate required column `name` and optional columns `address`, `phone`, `email`, `website`, `description`, `industry_sector` |
| FR-CV3 | CSV import must deduplicate by company name — skip rows where name matches existing company (exact, case-sensitive match) |
| FR-CV4 | CSV import must report per-row results using `CsvRowResult` enum (`created`, `duplicate`, `error`) |
| FR-CV5 | `CompanyManager` must provide a CSV template download with correct column headers and no data rows |
| FR-CV6 | `CompanyManager` must support CSV export of all company records with all seven fields |
| FR-CV7 | CSV import operations must be wrapped in database transactions for atomicity — either all valid rows succeed or none persist |

### Dashboard Integration

| ID   | Requirement |
| ---- | ----------- |
| FR-DI1 | Company dashboard stats must expose: `total_companies`, `with_placements`, `active_partnerships`, `available_slots` |
| FR-DI2 | `available_slots` must be computed as sum of `(quota - filled_quota)` across all active partnerships |
| FR-DI3 | `with_placements` must count companies that have at least one associated placement record |
| FR-DI4 | `active_partnerships` must count companies that have at least one partnership with `ACTIVE` status |
| FR-DI5 | Dashboard cache must be invalidated on any company mutation via `ClearDashboardOnCompanyChange` listener handling `CompanyCreated`, `CompanyUpdated`, and `CompanyDeleted` events |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All company mutations must be authorized via `CompanyPolicy` — no bypass allowed |
| NFR-S2 | CSV import must sanitize all input fields to prevent XSS and SQL injection |
| NFR-S3 | Company routes must require `auth` middleware and `role:super_admin\|admin` for write operations |
| NFR-S4 | CSV import must validate file MIME type and enforce maximum row count (1,000 rows per import) |
| NFR-S5 | Company deletion must be blocked if any placement or partnership record references it — enforced at both Action and Entity layers |
| NFR-P1 | Company list page must load in < 500ms for up to 1,000 companies |
| NFR-P2 | CSV import of 100 companies must complete in < 10 seconds |
| NFR-P3 | Dashboard stats query must execute in < 200ms with up to 1,000 companies and 500 partnerships |
| NFR-P4 | CSV export must stream results for datasets exceeding 500 records to avoid memory exhaustion |
| NFR-R1 | Company deletion must be blocked if any placement or partnership record references it — no exceptions |
| NFR-R2 | Batch delete must report exactly which records were deleted and which were skipped with reasons |
| NFR-R3 | CSV import must use database transactions — partial imports must not persist |
| NFR-U1 | Admin must see real-time validation feedback on company creation and edit forms |
| NFR-U2 | CSV import errors must display per-row with row number, field name, and error message |
| NFR-U3 | Batch delete results must show exact counts: X deleted, Y skipped (with reason per skipped record) |
| NFR-A1 | Company management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | CSV import error messages must be associated with their fields via `aria-describedby` |
| NFR-A3 | Real-time validation feedback must be announced to screen readers via `aria-live` regions |
| NFR-A4 | All form inputs must have associated labels |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 coding standard |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Company Model

```
App\Partners\Company\Models\Company
  Table: companies (UUID PK)
  Fillable: name, address, phone, email, website, description, industry_sector
  Relations:
    placements() HasMany Placement
    partnerships() HasMany Partnership
  Bridge: asCompanyState() → CompanyState::fromModel()
  Factory: CompanyFactory
```

### CompanyData DTO

```
App\Partners\Company\Data\CompanyData extends BaseData
  Required: name: string
  Optional: address: ?string, phone: ?string, email: ?string, website: ?string,
            description: ?string, industrySector: ?string
```

### CompanyState Entity

```
App\Partners\Company\Entities\CompanyState extends BaseEntity (final readonly)
  Properties: placementCount: int, partnershipCount: int
  Factory: fromModel(Model) — aggregates withCount from related tables
  Methods:
    canBeDeleted(): bool  — both placementCount and partnershipCount must === 0
```

### CompanyPolicy

```
App\Partners\Company\Policies\CompanyPolicy
  viewAny(User): bool — all authenticated users
  view(User, Company): bool — all authenticated users
  create(User): bool — admin role required (isAdmin())
  update(User, Company): bool — admin role required (isAdmin())
  delete(User, Company): bool — admin role required AND no existing placements
  forceDelete(User, Company): bool — super_admin role required
```

### Actions

| Action | Base | Accepts | Returns | Events Dispatched |
| ------ | ---- | ------- | ------- | ----------------- |
| `CreateCompanyAction` | `BaseCommandAction` | `CompanyData` | `ActionResponse` | `CompanyCreated` |
| `UpdateCompanyAction` | `BaseCommandAction` | `Company, CompanyData` | `ActionResponse` | `CompanyUpdated` |
| `DeleteCompanyAction` | `BaseCommandAction` | `Company` | `ActionResponse` | `CompanyDeleted` |
| `BatchDeleteCompanyAction` | `BaseCommandAction` | `Collection` | `ActionResponse` | `CompanyDeleted` (per record) |

### Events

| Event | Dispatched By | Payload |
| ----- | ------------- | ------- |
| `CompanyCreated` | `CreateCompanyAction` | `Company` model |
| `CompanyUpdated` | `UpdateCompanyAction` | `Company` model |
| `CompanyDeleted` | `DeleteCompanyAction`, `BatchDeleteCompanyAction` | `Company` model |

### Listeners

| Listener | Event(s) | Queued | Action |
| -------- | -------- | ------ | ------ |
| `ClearDashboardOnCompanyChange` | `CompanyCreated`, `CompanyUpdated`, `CompanyDeleted` | No | Invalidates dashboard cache |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/companies` | `CompanyManager` | `partners.companies` | `auth`, `role:super_admin\|admin` |

### Database Schema — `companies` Table

```
companies:
  id:               uuid        (PK, auto-generated)
  name:             string      (indexed, required — natural unique identifier)
  address:          text        (nullable)
  phone:            string      (nullable)
  email:            string      (nullable)
  website:          string      (nullable)
  description:      text        (nullable)
  industry_sector:  string      (nullable, indexed)
  created_at:       timestamp
  updated_at:       timestamp
```

### CSV Column Mapping

| CSV Column | DB Column | Required | Type | Notes |
| ----------- | --------- | -------- | ---- | ----- |
| `name` | `name` | Yes | string | Dedup key — exact match |
| `address` | `address` | No | text | Free-form address |
| `phone` | `phone` | No | string | Phone number |
| `email` | `email` | No | string | Contact email |
| `website` | `website` | No | string | Company URL |
| `description` | `description` | No | text | Company description |
| `industry_sector` | `industry_sector` | No | string | Industry classification |

### CsvRowResult Enum

```
App\Partners\Company\Enums\CsvRowResult: string
  Cases: CREATED='created', DUPLICATE='duplicate', ERROR='error'
  Methods: label(): string (LabelEnum implementation)
```

### Livewire Components

| Component | Namespace | Key Properties |
| --------- | --------- | -------------- |
| `CompanyManager` | `App\Partners\Company\Livewire` | Handles list, CSV import/export, batch actions |
| `CompanyForm` | `App\Partners\Company\Livewire\Forms` | Handles create/edit form validation |

---

## 7. Design Decisions

### DD-1 — Dual Deletion Guard Layers

**Decision:** Enforce deletion guards at both the Action layer (query-based check before calling entity) and the Entity layer (`canBeDeleted()` using `withCount` aggregation).
**Rationale:** The Action-layer check handles the common path with an upfront query for placements and partnerships. The Entity-layer check provides a safety net against race conditions where concurrent requests may have added dependent records between the Action check and the actual delete. Both must agree before deletion proceeds.
**Trade-off:** Slightly more code per deletion path, but significantly reduces risk of orphaned records.
**Rejected alternative:** Single-layer guard at database constraint level only — cannot provide user-friendly error messages or granular skip reporting in batch operations.

### DD-2 — Entity Bridge Pattern for Business Logic Delegation

**Decision:** Models provide `asCompanyState()` bridge methods that delegate to Entity factories. Entities are `final readonly` and contain all business rule methods (e.g., `canBeDeleted()`).
**Rationale:** Keeps Models as pure persistence objects (C1 compliance). Entities provide type-safe, immutable snapshots for business logic evaluation without coupling to database state. The `fromModel()` factory method standardizes construction with `withCount` aggregation for accurate relationship counts.
**Trade-off:** Extra class per submodule, but provides clean separation of concerns.
**Rejected alternative:** Business methods on Model — violates C1 invariant (Model should not contain business logic).

### DD-3 — CSV Import Deduplication by Name

**Decision:** CSV import deduplicates companies by exact name match — rows with names matching existing companies are skipped and reported as duplicates.
**Rationale:** Company name is the natural unique identifier for business users. Exact match prevents accidental overwrites. The dedup check is a single query per row batch, keeping import performance acceptable for 100+ rows.
**Trade-off:** Case-sensitive matching may miss near-duplicates (e.g., "PT Maju" vs "PT maju").
**Rejected alternative:** Fuzzy matching — too many false positives for automated import; manual review would be required for every near-match, defeating the purpose of bulk import.

### DD-4 — CSV Import Transaction Wrapping

**Decision:** Wrap all CSV import operations in a single database transaction. Either all valid rows persist or none do.
**Rationale:** Prevents partial imports that leave the database in an inconsistent state. If any row causes an unexpected database error (e.g., constraint violation beyond dedup), the entire import rolls back cleanly.
**Trade-off:** Large imports (500+ rows) may hold database locks longer than row-by-row insertion.
**Rejected alternative:** Row-by-row commits — risks partial imports on failure; harder to roll back on unexpected errors.

### DD-5 — Dashboard Cache via Event-Listener Invalidation

**Decision:** Use domain events (`CompanyCreated`, `CompanyUpdated`, `CompanyDeleted`) dispatched from Actions, with a synchronous listener (`ClearDashboardOnCompanyChange`) that invalidates dashboard cache.
**Rationale:** Decouples cache management from business logic. Actions remain unaware of caching concerns. The listener pattern is consistent with Laravel conventions and allows adding future listeners (notifications, analytics) without modifying Actions.
**Trade-off:** Synchronous listener adds slight latency to mutations (cache clear is fast, but not zero).
**Rejected alternative:** Cache-on-read (lazy invalidation) — stale data window between mutation and next read; not acceptable for capacity planning dashboards.

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Orphaned placement records from company deletion | 0 | Dual deletion guard (Action + Entity) prevents deletion with dependent records |
| Duplicate company imports via CSV | 0 | Dedup by exact name match skips existing records |
| Partial CSV imports persisted | 0 | Transaction wrapping ensures atomicity |
| Dashboard stale data window | 0 seconds | Synchronous cache invalidation on every company mutation |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Company list page load | < 500ms | `CompanyManager` query with 1,000 companies |
| CSV import (100 companies) | < 10 seconds | `CompanyManager` import handler with validation |
| Dashboard stats query | < 200ms | Aggregate query with 1,000 companies and 500 partnerships |
| CSV export streaming | < 30 seconds | Export of 1,000 companies with all fields |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion rejection feedback | Actionable error message | `RejectedException` with translated reason and specific dependent record type |
| Batch delete summary | Exact deleted/skipped counts | `BatchDeleteCompanyAction` response with per-record reasons |
| CSV import report | Per-row status with details | `CsvRowResult` enum per row: created, duplicate, or error with field/message |
| Form validation latency | Real-time, < 200ms | Livewire validation rules on `CompanyForm` |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations delegated to Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | `CompanyState` imports no Actions or Services |
| DTO purity (C6) | 0 violations | `CompanyData` imports no Models or Entities |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |
| All user strings localized (D3) | 100% strings | `__()` helper used for all user-facing text |
| No debug calls (D2) | 0 occurrences | No `dd`, `dump`, `ray`, `var_dump`, `print_r`, or `die` |

---

## Quick References

- `app/Partners/Company/Models/Company.php` — Company model with `#[Fillable]` attribute and relationships
- `app/Partners/Company/Entities/CompanyState.php` — Entity with `canBeDeleted()` dual guard
- `app/Partners/Company/Data/CompanyData.php` — Company DTO (extends `BaseData`)
- `app/Partners/Company/Actions/CreateCompanyAction.php` — Create command action
- `app/Partners/Company/Actions/UpdateCompanyAction.php` — Update command action
- `app/Partners/Company/Actions/DeleteCompanyAction.php` — Delete with dual guard check
- `app/Partners/Company/Actions/BatchDeleteCompanyAction.php` — Batch delete with per-record guard
- `app/Partners/Company/Policies/CompanyPolicy.php` — Authorization (admin-only writes)
- `app/Partners/Company/Livewire/CompanyManager.php` — UI with CSV import/export
- `app/Partners/Company/Livewire/Forms/CompanyForm.php` — Form validation
- `app/Partners/Company/Events/CompanyCreated.php` — Created event
- `app/Partners/Company/Events/CompanyUpdated.php` — Updated event
- `app/Partners/Company/Events/CompanyDeleted.php` — Deleted event
- `app/Partners/Company/Listeners/ClearDashboardOnCompanyChange.php` — Cache invalidation
- `database/migrations/2026_01_03_000003_create_companies_table.php` — Companies schema
- `routes/web/partners.php` — Route definitions
- `docs/modules/partners.md` — Module conceptual documentation
- `docs/modules/partners-reference.md` — Module technical reference
- `docs/specs/partnership-management.md` — Partnership lifecycle spec (sibling split)
