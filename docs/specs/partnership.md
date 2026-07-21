# Partners — Company & Partnership Management, MoU Lifecycle, and CSV Import

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering company CRUD, partnership
> lifecycle, dual deletion guards, MoU document management, capacity tracking, and CSV import/export

## Description

Complete specification of the Internara Partners module: company profiles, partnership (MoU)
agreement management, status lifecycle with terminal-state guards, MoU document uploads via Spatie
MediaLibrary, placement capacity tracking, and CSV import/export for bulk company onboarding.

---

## 1. Problem Statements

### PS-1 — Company-Partnership-Placement Data Integrity Chain

A company may have multiple partnerships over time, and each partnership provides placement slots
for interns. Deleting a company that has active placements or partnerships would orphan enrollment
records and break the integrity chain. The system must enforce referential guards at both the Action
layer and the Entity layer to prevent destructive operations on companies with dependent data.

### PS-2 — Partnership Status Lifecycle and Terminal State Enforcement

Partnerships have a controlled lifecycle (ACTIVE → EXPIRED or TERMINATED). Without enforced state
transitions, an admin could attempt to terminate an already-expired partnership, delete an active
one, or create duplicate records. The system must enforce valid transitions and block operations
that violate lifecycle rules, using both the enum's `canTransitionTo()` and entity-level checks.

### PS-3 — MoU Document Management and Single-File Constraint

Each partnership must store a scanned MoU (Memorandum of Understanding) document. Without a
single-file constraint, duplicate uploads could accumulate, and without conversion, large PDF/image
files would slow page loads. The system must enforce one MoU per partnership and generate a
web-optimized thumbnail for display.

### PS-4 — Placement Capacity Tracking Across Companies

Administrators need visibility into total available placement slots across all companies (sum of
`quota - filled_quota` per partnership). Without real-time aggregation, capacity planning decisions
are based on stale data. The system must compute available slots accurately and surface it in
company statistics.

### PS-5 — Bulk Company Onboarding via CSV Import

Schools often have dozens of existing partner companies to onboard. Manual entry is error-prone and
time-consuming. The system must support CSV import with validation, deduplication by name, error
reporting per row, and a downloadable template for correct column formatting.

### PS-6 — Dual Deletion Guard Layers for Partnership Records

A partnership deletion guard at the Action layer (querying for related placements) may race with
concurrent requests. The Entity layer must provide a second guard via `withCount` aggregation that
reflects the model's current state at decision time. Both layers must agree before deletion
proceeds.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for company profiles with admin-only write access and public read |
| G2  | Manage partnership lifecycle (create, update, terminate, renew, delete) with enforced status transitions |
| G3  | Store MoU documents as single-file MediaLibrary collections with web-optimized thumb conversions |
| G4  | Track placement capacity (available slots) per company and aggregate across all companies |
| G5  | Support CSV import/export for companies with per-row validation, deduplication, and error reporting |
| G6  | Enforce dual deletion guards (Action query + Entity `withCount`) to prevent orphaning dependent records |
| G7  | Dispatch domain events on all mutations with cache invalidation listeners for dashboard freshness |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Student placement assignment (owned by Enrollment module) |
| NG2  | Internship program definitions and scheduling (owned by Program module) |
| NG3  | Certificate issuance for completed internships (owned by Certification module) |
| NG4  | Multi-file document uploads or document versioning beyond single-file MoU |
| NG5  | Automated partnership expiry via scheduled tasks (manual or cron-driven outside this spec) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Company Profile

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `admin/companies`
2. Clicks "Add Company" button, `CompanyManager` opens modal with `CompanyForm`
3. Admin fills in required field `name` and optional fields (address, phone, email, website, description, industry_sector)
4. `CompanyForm` validates input via Livewire real-time rules
5. On submit, `CompanyManager` calls `CreateCompanyAction::execute(CompanyData)`
6. `CreateCompanyAction` creates `Company` model, dispatches `CompanyCreated` event
7. `ClearDashboardOnCompanyChange` listener invalidates dashboard cache
**Postconditions:** Company record exists, dashboard cache cleared, success flash message shown

### UC-2 — Admin Creates a Partnership with MoU Document

**Actor:** Admin
**Preconditions:** At least one company exists; admin has `admin` role
**Flow:**
1. Admin navigates to `admin/companies/partnerships`
2. Clicks "Add Partnership", `PartnershipManager` opens modal with `PartnershipForm`
3. Admin selects company from dropdown, fills required fields (agreement_number, title, start_date, end_date)
4. Admin optionally uploads MoU document (single file)
5. On submit, `PartnershipManager` calls `CreatePartnershipAction::execute(PartnershipData)`
6. Action creates `Partnership` with default `ACTIVE` status, stores MoU via MediaLibrary
7. Dispatches `PartnershipCreated` event
**Postconditions:** Partnership record exists with `ACTIVE` status, MoU stored, dashboard cache cleared

### UC-3 — Admin Terminates an Active Partnership

**Actor:** Admin
**Preconditions:** Partnership exists with `ACTIVE` status
**Flow:**
1. Admin views partnership details in `PartnershipManager`
2. Clicks "Terminate" action button
3. `TerminatePartnershipAction::execute(Partnership)` is called
4. Entity `PartnershipState::isActive()` returns true, transition allowed
5. Status updated to `TERMINATED`, `PartnershipTerminated` event dispatched
6. `NotifyOnPartnershipTerminated` listener queues notification
**Postconditions:** Partnership status is `TERMINATED`, notification queued, existing placements unaffected

### UC-4 — Admin Renews a Partnership (Creates New Record)

**Actor:** Admin
**Preconditions:** Previous partnership is in a terminal state (EXPIRED or TERMINATED)
**Flow:**
1. Admin views expired/terminated partnership in `PartnershipManager`
2. Clicks "Renew" action button
3. `RenewPartnershipAction::execute(oldPartnership, newPartnershipData)` is called
4. Validates old partnership is NOT active (rejects if active)
5. Sets old partnership status to `EXPIRED` if not already terminal
6. Creates new `Partnership` record with new dates, copies scope/contact/signed data from old
7. Transfers MoU document from old to new record via MediaLibrary
8. Dispatches `PartnershipRenewed` event
**Postconditions:** Old partnership is `EXPIRED`, new partnership is `ACTIVE`, MoU transferred, capacity recalculated

### UC-5 — Batch Delete with Deletion Guards

**Actor:** Admin
**Preconditions:** Multiple companies or partnerships selected; admin has `admin` role
**Flow:**
1. Admin selects multiple records in `CompanyManager` or `PartnershipManager`
2. Clicks "Delete Selected" batch action
3. `BatchDeleteCompanyAction` or `BatchDeletePartnershipAction` iterates selected IDs
4. For each record: checks entity `canBeDeleted()` — returns false if placements/partnerships exist (company) or if status is ACTIVE (partnership)
5. Records that pass deletion guard are soft-deleted; records that fail are skipped
6. Returns count of deleted vs skipped records with flash message
**Postconditions:** Only eligible records deleted, ineligible records preserved, summary reported

---

## 4. Functional Requirements

### Company CRUD

| ID   | Requirement |
| ---- | ----------- |
| FR-CC1 | `CompanyManager` must be accessible at route `admin/companies` with `auth` and `role:super_admin\|admin` middleware |
| FR-CC2 | `CreateCompanyAction` must accept `CompanyData` DTO and return `ActionResponse` |
| FR-CC3 | `CompanyData` must require `name` (string); `address`, `phone`, `email`, `website`, `description`, `industrySector` must be nullable strings |
| FR-CC4 | `Company` model must use `#[Fillable]` attribute with all seven fields |
| FR-CC5 | `CompanyPolicy::create()`, `update()`, `delete()` must require admin role via `isAdmin()` |
| FR-CC6 | `CompanyPolicy::delete()` must additionally check `$company->placements()->exists()` and deny if true |
| FR-CC7 | `CompanyPolicy::viewAny()` and `view()` must return true for all authenticated users |
| FR-CC8 | `DeleteCompanyAction` must throw `RejectedException` if `CompanyState::canBeDeleted()` returns false |
| FR-CC9 | `BatchDeleteCompanyAction` must iterate selected IDs, check `canBeDeleted()` per record, and delete only eligible records |
| FR-CC10 | `CompanyState::canBeDeleted()` must require both `placementCount === 0` and `partnershipCount === 0` |
| FR-CC11 | `Company` model must provide `asCompanyState()` bridge method returning `CompanyState::fromModel()` |
| FR-CC12 | Company mutations must dispatch `CompanyCreated`, `CompanyUpdated`, or `CompanyDeleted` events |
| FR-CC13 | `ClearDashboardOnCompanyChange` listener must handle all three company events and invalidate dashboard cache |

### Partnership CRUD

| ID   | Requirement |
| ---- | ----------- |
| FR-PC1 | `PartnershipManager` must be accessible at route `admin/companies/partnerships` with `auth` and `role:super_admin\|admin` middleware |
| FR-PC2 | `CreatePartnershipAction` must accept `PartnershipData` DTO and return `ActionResponse` |
| FR-PC3 | `PartnershipData` must require `companyId`, `agreementNumber`, `title`, `startDate`, `endDate`; `scope`, `contactPersonName`, `contactPersonPhone`, `contactPersonEmail`, `signedBySchool`, `signedByCompany`, `signedAt`, `notes` must be nullable |
| FR-PC4 | `Partnership` model must use `#[Fillable]` attribute with all 15 fillable fields |
| FR-PC5 | `Partnership` model must define `company()` BelongsTo relationship to `Company` via `company_id` |
| FR-PC6 | `PartnershipPolicy::viewAny()` and `view()` must allow `super_admin`, `admin`, and `teacher` roles |
| FR-PC7 | `PartnershipPolicy::create()`, `update()`, `delete()` must require admin role |
| FR-PC8 | `DeletePartnershipAction` must throw `RejectedException` if partnership status is not terminal |
| FR-PC9 | `BatchDeletePartnershipAction` must iterate selected IDs, check `canBeDeleted()` per record, and delete only terminal-state records |
| FR-PC10 | `PartnershipState::canBeDeleted()` must return true only if `isExpired()` or `isTerminated()` |
| FR-PC11 | `Partnership` model must provide `asPartnershipState()` bridge method returning `PartnershipState::fromModel()` |
| FR-PC12 | Partnership mutations must dispatch `PartnershipCreated`, `PartnershipUpdated`, or `PartnershipDeleted` events |
| FR-PC13 | `ClearDashboardOnPartnershipChange` listener must handle all partnership CRUD events and invalidate dashboard cache |
| FR-PC14 | `PartnershipManager` must JOIN `companies` table to expose a sortable `company_name` column in the record table |

### Status Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-ST1 | `PartnershipStatus` enum must define three cases: `ACTIVE`, `EXPIRED`, `TERMINATED` |
| FR-ST2 | `PartnershipStatus::validTransitions()` must return `[EXPIRED, TERMINATED]` for ACTIVE, `[]` for both terminal states |
| FR-ST3 | `PartnershipStatus::isTerminal()` must return true for EXPIRED and TERMINATED only |
| FR-ST4 | `PartnershipStatus::canTransitionTo()` must use `validTransitions()` to determine allowed targets |
| FR-ST5 | `TerminatePartnershipAction` must validate `PartnershipState::isActive()` before transitioning to TERMINATED |
| FR-ST6 | `TerminatePartnershipAction` must dispatch `PartnershipTerminated` event |
| FR-ST7 | `NotifyOnPartnershipTerminated` listener must be queued and send notification on termination |
| FR-ST8 | `PartnershipState::isExpiringSoon()` must accept configurable `thresholdDays` (default 30) and return true for ACTIVE partnerships with `end_date` within threshold |
| FR-ST9 | New partnerships must default to `ACTIVE` status via model `$attributes` array |
| FR-ST10 | `Partnership` model must cast `status` to `PartnershipStatus` enum |

### Renewal

| ID   | Requirement |
| ---- | ----------- |
| FR-ST11 | `RenewPartnershipAction` must reject if old partnership is currently `ACTIVE` |
| FR-ST12 | `RenewPartnershipAction` must set old partnership status to `EXPIRED` if not already terminal |
| FR-ST13 | `RenewPartnershipAction` must create a new `Partnership` record with provided data |
| FR-ST14 | `RenewPartnershipAction` must copy scope, contact person, signed-by, and notes from old to new partnership |
| FR-ST15 | `RenewPartnershipAction` must transfer MoU document from old to new partnership via MediaLibrary |
| FR-ST16 | `RenewPartnershipAction` must dispatch `PartnershipRenewed` event |

### MoU Documents

| ID   | Requirement |
| ---- | ----------- |
| FR-MD1 | `Partnership` model must implement `HasMedia` interface and use `InteractsWithMedia` trait |
| FR-MD2 | `Partnership::registerMediaCollections()` must register `mou_document` as a single-file collection |
| FR-MD3 | `Partnership::registerMediaConversions()` must register `thumb` conversion at 400px width, webp format, non-queued |
| FR-MD4 | `PartnershipManager` must support MoU file upload via `WithFileUploads` trait |
| FR-MD5 | MoU upload must use `addMedia()` or `addMediaFromRequest()` to store in `mou_document` collection |
| FR-MD6 | MoU retrieval must use `getFirstMedia('mou_document')` or `getFirstMediaUrl('mou_document', 'thumb')` for thumbnail |

### CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-CV1 | `CompanyManager` must support CSV import via `CsvHandler` integration |
| FR-CV2 | CSV import must validate required column `name` and optional columns `address`, `phone`, `email`, `website`, `description`, `industry_sector` |
| FR-CV3 | CSV import must deduplicate by company name — skip rows where name matches existing company |
| FR-CV4 | CSV import must report per-row results using `CsvRowResult` enum (created, duplicate, error) |
| FR-CV5 | `CompanyManager` must provide a CSV template download with correct column headers |
| FR-CV6 | `CompanyManager` must support CSV export of all company records with all fields |
| FR-CV7 | CSV operations must be wrapped in database transactions for atomicity |

### Dashboard Integration

| ID   | Requirement |
| ---- | ----------- |
| FR-DI1 | `Company` stats must expose: `total_companies`, `with_placements`, `active_partnerships`, `available_slots` |
| FR-DI2 | `available_slots` must be computed as sum of `(quota - filled_quota)` across all active partnerships |
| FR-DI3 | `Partnership` stats must expose: `total`, `active`, `expiring_soon`, `expired` |
| FR-DI4 | `expiring_soon` count must include ACTIVE partnerships with `end_date` within 30 days |
| FR-DI5 | Dashboard cache must be invalidated on any Company or Partnership mutation via event listeners |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All partnership and company mutations must be authorized via Policy classes — no bypass allowed |
| NFR-S2 | CSV import must sanitize all input fields to prevent XSS and SQL injection |
| NFR-S3 | MoU file uploads must validate MIME type and enforce maximum file size (10 MB) |
| NFR-S4 | Company and Partnership routes must require `auth` middleware and `role:super_admin\|admin` |
| NFR-S5 | Partnership deletion must never remove records with ACTIVE status — enforced at both Action and Entity layers |
| NFR-P1 | Company list page must load in < 500ms for up to 1,000 companies |
| NFR-P2 | Partnership list page with company JOIN must load in < 500ms for up to 500 partnerships |
| NFR-P3 | MoU thumb conversion (400px webp) must be generated non-queued to avoid worker dependency |
| NFR-P4 | CSV import of 100 companies must complete in < 10 seconds |
| NFR-R1 | Company deletion must be blocked if any placement or partnership record references it |
| NFR-R2 | Partnership renewal must be atomic — old record status change and new record creation in single transaction |
| NFR-R3 | Batch delete must report exactly which records were deleted and which were skipped with reasons |
| NFR-U1 | Admin must see real-time validation feedback on company and partnership forms |
| NFR-U2 | CSV import errors must display per-row with row number, field, and error message |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 coding standard |
| NFR-A1 | All partnership and company management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | CSV import error messages must be associated with their fields via `aria-describedby` |
| NFR-A3 | Real-time validation feedback must be announced to screen readers via `aria-live` |
| NFR-A4 | All form inputs must have associated labels |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Partnership status labels must use `LabelEnum::label()` (calls `__()` internally) |

---

## 6. API / Data Contracts

### Company Model

```
App\Partners\Company\Models\Company
  Table: companies (UUID PK)
  Fillable: name, address, phone, email, website, description, industry_sector
  Relations: placements() HasMany Placement, partnerships() HasMany Partnership
  Bridge: asCompanyState() → CompanyState
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
  Factory: fromModel(Model)
  Methods: canBeDeleted(): bool  — both counts must === 0
```

### Partnership Model

```
App\Partners\Partnership\Models\Partnership
  Table: partnerships (UUID PK, FK company_id cascadeOnDelete)
  Fillable: company_id, agreement_number (unique), title, start_date, end_date, status,
            scope, contact_person_name, contact_person_phone, contact_person_email,
            signed_by_school, signed_by_company, signed_at, notes
  Casts: start_date→date, end_date→date, signed_at→date, status→PartnershipStatus
  Relations: company() BelongsTo Company
  Media: mou_document (singleFile), thumb conversion (400px webp, non-queued)
  Bridge: asPartnershipState() → PartnershipState
  Default: status = ACTIVE
  Factory: PartnershipFactory
```

### PartnershipData DTO

```
App\Partners\Partnership\Data\PartnershipData extends BaseData
  Required: companyId: string, agreementNumber: string, title: string,
            startDate: string, endDate: string
  Optional: scope: ?string, contactPersonName: ?string, contactPersonPhone: ?string,
            contactPersonEmail: ?string, signedBySchool: ?string, signedByCompany: ?string,
            signedAt: ?string, notes: ?string
```

### PartnershipState Entity

```
App\Partners\Partnership\Entities\PartnershipState extends BaseEntity (final readonly)
  Properties: status: PartnershipStatus, endDate: ?string
  Factory: fromModel(Model)
  Methods: isActive(): bool, isExpired(): bool, isTerminated(): bool,
           isExpiringSoon(thresholdDays = 30): bool, canBeDeleted(): bool
```

### PartnershipStatus Enum

```
App\Partners\Partnership\Enums\PartnershipStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: ACTIVE='active', EXPIRED='expired', TERMINATED='terminated'
  Methods: label(): string, isTerminal(): bool, validTransitions(): array,
           canTransitionTo(StatusEnum): bool
  Transitions: ACTIVE→[EXPIRED, TERMINATED], EXPIRED→[], TERMINATED→[]
  Terminal: EXPIRED, TERMINATED
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateCompanyAction` | `BaseCommandAction` | `CompanyData` | `ActionResponse` |
| `UpdateCompanyAction` | `BaseCommandAction` | `Company, CompanyData` | `ActionResponse` |
| `DeleteCompanyAction` | `BaseCommandAction` | `Company` | `ActionResponse` |
| `BatchDeleteCompanyAction` | `BaseCommandAction` | `Collection` | `ActionResponse` |
| `CreatePartnershipAction` | `BaseCommandAction` | `PartnershipData` | `ActionResponse` |
| `UpdatePartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` |
| `DeletePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` |
| `TerminatePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` |
| `RenewPartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` |
| `BatchDeletePartnershipAction` | `BaseCommandAction` | `Collection` | `ActionResponse` |

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `CompanyCreated` | `CreateCompanyAction` |
| `CompanyUpdated` | `UpdateCompanyAction` |
| `CompanyDeleted` | `DeleteCompanyAction`, `BatchDeleteCompanyAction` |
| `PartnershipCreated` | `CreatePartnershipAction` |
| `PartnershipUpdated` | `UpdatePartnershipAction` |
| `PartnershipDeleted` | `DeletePartnershipAction`, `BatchDeletePartnershipAction` |
| `PartnershipTerminated` | `TerminatePartnershipAction` |
| `PartnershipRenewed` | `RenewPartnershipAction` |

### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `ClearDashboardOnCompanyChange` | CompanyCreated, CompanyUpdated, CompanyDeleted | No |
| `ClearDashboardOnPartnershipChange` | PartnershipCreated, PartnershipUpdated, PartnershipDeleted, PartnershipTerminated, PartnershipRenewed | No |
| `NotifyOnPartnershipTerminated` | PartnershipTerminated | Yes |

### Policies

| Policy | Abilities |
| ------ | --------- |
| `CompanyPolicy` | viewAny: all, view: all, create: admin, update: admin, delete: admin + no placements, forceDelete: super_admin |
| `PartnershipPolicy` | viewAny: super_admin/admin/teacher, view: admin/teacher, create: admin, update: admin, delete: admin |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/companies` | `CompanyManager` | `partners.companies` | `auth`, `role:super_admin\|admin` |
| `GET /admin/companies/partnerships` | `PartnershipManager` | `partners.partnerships` | `auth`, `role:super_admin\|admin` |

### Database Schema

```
companies:
  id: uuid (PK)
  name: string (indexed)
  address: text (nullable)
  phone: string (nullable)
  email: string (nullable)
  website: string (nullable)
  description: text (nullable)
  industry_sector: string (nullable, indexed)
  timestamps

partnerships:
  id: uuid (PK)
  company_id: foreignUuid → companies.id (cascadeOnDelete, indexed)
  agreement_number: string (unique)
  title: string
  start_date: date
  end_date: date
  status: string (default 'active', indexed)
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

### DD-1 — Dual Deletion Guard Layers

**Decision:** Enforce deletion guards at both the Action layer (query-based check before calling entity) and the Entity layer (`canBeDeleted()` using `withCount` aggregation).
**Rationale:** The Action-layer check handles the common path. The Entity-layer check provides a safety net against race conditions where concurrent requests may have added dependent records between the Action check and the actual delete. Both must agree.
**Trade-off:** Slightly more code per deletion path, but significantly reduces risk of orphaned records. Rejected alternative: single-layer guard at database constraint level only (cannot provide user-friendly error messages).

### DD-2 — Renewal Creates New Record Instead of Updating In-Place

**Decision:** Partnership renewal creates a brand-new `Partnership` record rather than updating dates on the existing record.
**Rationale:** Preserves full audit history — the old partnership retains its original agreement number, dates, and status. The new partnership gets a new UUID and can have different terms. MoU document is transferred via MediaLibrary.
**Trade-off:** Old record becomes a historical artifact. The `agreement_number` uniqueness constraint requires either a new number or a versioned format. Rejected alternative: soft-delete + recreate (loses foreign key references from placements).

### DD-3 — Entity Bridge Pattern for Business Logic Delegation

**Decision:** Models provide `as{State}()` bridge methods (e.g., `asCompanyState()`, `asPartnershipState()`) that delegate to Entity factories. Entities are `final readonly` and contain all business rule methods.
**Rationale:** Keeps Models as pure persistence objects (C1 compliance). Entities provide type-safe, immutable snapshots for business logic evaluation without coupling to database state. `fromModel()` factory method standardizes construction.
**Trade-off:** Extra class per submodule. Rejected alternative: business methods on Model (violates C1 — Model should not contain business logic).

### DD-4 — MoU via Spatie MediaLibrary Single-File Collection

**Decision:** Store MoU documents using Spatie MediaLibrary with `singleFile()` constraint on the `mou_document` collection and a non-queued `thumb` conversion (400px webp).
**Rationale:** `singleFile()` enforces one MoU per partnership at the framework level. Non-queued conversion ensures the thumbnail is immediately available without queue worker dependency. WebP format reduces storage and load times.
**Trade-off:** Single-file constraint prevents storing multiple MoU versions (amendments). Rejected alternative: manual file storage with `Storage::put()` (loses conversion pipeline and clean API).

### DD-5 — Partnership Query JOIN for Sortable Company Name

**Decision:** `PartnershipManager` JOINs the `companies` table in its query builder to expose a `company_name` column that supports sorting and filtering.
**Rationale:** Partnerships are meaningless without company context. Sorting by company name is a primary UX need. Eager loading (`with('company')`) cannot participate in sortable database-level ORDER BY. The JOIN approach keeps sorting in the database layer.
**Trade-off:** Slightly more complex query. Rejected alternative: eager load + collection sort (does not scale with pagination).

### DD-6 — CSV Import Deduplication by Name

**Decision:** CSV import deduplicates companies by exact name match — rows with names matching existing companies are skipped and reported as duplicates.
**Rationale:** Company name is the natural unique identifier for business users. Exact match prevents accidental overwrites. The dedup check is a single query per row batch.
**Trade-off:** Case-sensitive matching may miss near-duplicates (e.g., "PT Maju" vs "PT maju"). Rejected alternative: fuzzy matching (too many false positives for automated import).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Orphaned placement records | 0 | Deletion guard prevents company/partnership delete with dependent records |
| Invalid status transitions | 0 | `PartnershipStatus::canTransitionTo()` blocks illegal transitions |
| Duplicate MoU uploads | 0 | MediaLibrary `singleFile()` constraint enforced |
| Duplicate company imports | 0 | CSV dedup by name skips existing records |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Company list load | < 500ms | `CompanyManager` query with 1,000 records |
| Partnership list load | < 500ms | `PartnershipManager` query with company JOIN, 500 records |
| CSV import (100 companies) | < 10 seconds | `CompanyManager` import handler |
| MoU thumb generation | < 2s | Non-queued MediaLibrary conversion |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion feedback | Actionable error message | `RejectedException` with translated reason |
| Batch delete summary | Exact deleted/skipped counts | `BatchDeleteCompanyAction` / `BatchDeletePartnershipAction` response |
| CSV import report | Per-row status (created/duplicate/error) | `CsvRowResult` enum per row |
| Form validation | Real-time, < 200ms feedback | Livewire validation rules on `CompanyForm` / `PartnershipForm` |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | Entities import no Actions/Services |
| DTO purity (C6) | 0 violations | DTOs import no Models/Entities |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## Quick References

- `app/Partners/Company/Models/Company.php` — Company model with Fillable attribute and relationships
- `app/Partners/Company/Entities/CompanyState.php` — Entity with `canBeDeleted()` dual guard
- `app/Partners/Company/Data/CompanyData.php` — Company DTO (BaseData)
- `app/Partners/Company/Actions/CreateCompanyAction.php` — Create command action
- `app/Partners/Company/Actions/UpdateCompanyAction.php` — Update command action
- `app/Partners/Company/Actions/DeleteCompanyAction.php` — Delete with guard check
- `app/Partners/Company/Actions/BatchDeleteCompanyAction.php` — Batch delete with per-record guard
- `app/Partners/Company/Policies/CompanyPolicy.php` — Authorization (admin-only writes)
- `app/Partners/Company/Livewire/CompanyManager.php` — UI with CSV import/export
- `app/Partners/Company/Livewire/Forms/CompanyForm.php` — Form validation
- `app/Partners/Company/Events/CompanyCreated.php` — Created event
- `app/Partners/Company/Events/CompanyUpdated.php` — Updated event
- `app/Partners/Company/Events/CompanyDeleted.php` — Deleted event
- `app/Partners/Company/Listeners/ClearDashboardOnCompanyChange.php` — Cache invalidation
- `app/Partners/Partnership/Models/Partnership.php` — Partnership model with MediaLibrary
- `app/Partners/Partnership/Entities/PartnershipState.php` — Entity with lifecycle queries
- `app/Partners/Partnership/Data/PartnershipData.php` — Partnership DTO (BaseData)
- `app/Partners/Partnership/Enums/PartnershipStatus.php` — Status enum with transitions
- `app/Partners/Partnership/Actions/CreatePartnershipAction.php` — Create command action
- `app/Partners/Partnership/Actions/UpdatePartnershipAction.php` — Update command action
- `app/Partners/Partnership/Actions/DeletePartnershipAction.php` — Delete with terminal-state guard
- `app/Partners/Partnership/Actions/BatchDeletePartnershipAction.php` — Batch delete with per-record guard
- `app/Partners/Partnership/Actions/TerminatePartnershipAction.php` — Active → Terminated transition
- `app/Partners/Partnership/Actions/RenewPartnershipAction.php` — Renewal creating new record
- `app/Partners/Partnership/Policies/PartnershipPolicy.php` — Authorization (admin + teacher read)
- `app/Partners/Partnership/Livewire/PartnershipManager.php` — UI with company JOIN query
- `app/Partners/Partnership/Livewire/Forms/PartnershipForm.php` — Form validation
- `app/Partners/Partnership/Events/PartnershipCreated.php` — Created event
- `app/Partners/Partnership/Events/PartnershipUpdated.php` — Updated event
- `app/Partners/Partnership/Events/PartnershipDeleted.php` — Deleted event
- `app/Partners/Partnership/Events/PartnershipTerminated.php` — Terminated event
- `app/Partners/Partnership/Events/PartnershipRenewed.php` — Renewed event
- `app/Partners/Partnership/Listeners/ClearDashboardOnPartnershipChange.php` — Cache invalidation
- `app/Partners/Partnership/Listeners/NotifyOnPartnershipTerminated.php` — Queued notification
- `database/migrations/2026_01_03_000003_create_companies_table.php` — Companies schema
- `database/migrations/2026_01_03_000007_create_partnerships_table.php` — Partnerships schema
- `routes/web/partners.php` — Route definitions
- `docs/modules/partners.md` — Module conceptual documentation
- `docs/modules/partners-reference.md` — Module technical reference
