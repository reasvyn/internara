# Partnership Management — Lifecycle, MoU Documents & Renewal

> **Last updated:** 2026-07-22 **Changes:** feat — split from partnership.md; expanded partnership
> lifecycle CRUD, status transitions, MoU document management, renewal workflow, deletion guards,
> and events/listeners into standalone spec

## Description

Complete specification of the Internara Partnership submodule: partnership agreement CRUD with
enforced status lifecycle (ACTIVE → EXPIRED/TERMINATED), MoU document management via single-file
Spatie MediaLibrary collections, renewal workflow that preserves audit history by creating new
records, terminal-state deletion guards, and domain events with dashboard cache invalidation.

This spec covers only the Partnership portion of the Partners module. Company profiles, placement
capacity tracking, and CSV import/export are defined in [company-management.md](company-management.md).

---

## 1. Problem Statements

### PS-1 — Partnership Status Lifecycle and Terminal State Enforcement

Partnerships have a controlled lifecycle (ACTIVE → EXPIRED or TERMINATED). Without enforced state
transitions, an admin could attempt to terminate an already-expired partnership, delete an active
one, or create duplicate records. The system must enforce valid transitions and block operations
that violate lifecycle rules, using both the enum's `canTransitionTo()` and entity-level checks.

Without this enforcement, data integrity degrades: ACTIVE partnerships could be silently removed,
terminal-state records could be resurrected via status edits, and downstream modules (Enrollment,
Certification) would make decisions based on stale partnership states. Every transition must be
explicit, auditable, and blocked at both the Action and Entity layers.

### PS-2 — MoU Document Management and Single-File Constraint

Each partnership must store a scanned MoU (Memorandum of Understanding) document. Without a
single-file constraint, duplicate uploads could accumulate over time, and without conversion, large
PDF/image files would slow page loads for partnership listings and detail views. The system must
enforce exactly one MoU per partnership and generate a web-optimized thumbnail for display in
tables and cards.

The MoU is a legally significant document — its presence signals that the partnership is properly
formalized. The system should warn when a partnership is created without an attached MoU, and the
thumbnail pipeline must produce results immediately without requiring a queue worker.

### PS-3 — Renewal Preserving Audit History

When a partnership expires or is terminated, the school may renew the agreement with updated terms.
If renewal simply updated the existing record's dates, the original agreement's history would be
lost — making it impossible to audit what changed between agreement periods. The system must create
a new Partnership record on renewal, preserving the old record as a historical artifact with its
original status, dates, and agreement number.

Renewal must also transfer the MoU document to the new record so the new partnership starts with a
valid document attachment. Without atomic renewal (old status change + new record creation in a
single transaction), a failure mid-operation could leave the system in an inconsistent state.

### PS-4 — Dual Deletion Guard Layers for Partnership Records

A partnership deletion guard at the Action layer (checking status before deletion) may race with
concurrent requests that transition the same partnership. The Entity layer must provide a second
guard that reflects the model's current state at decision time. Both layers must agree before
deletion proceeds. Without this dual-guard approach, a partnership could be deleted during a
concurrent termination, or an active partnership could be removed if the guard check is stale.

### PS-5 — Expiring Partnership Visibility

Partnerships approaching their end date need proactive visibility so administrators can plan
renewals before placements are disrupted. Without a configurable threshold and dashboard surfacing,
expiring partnerships go unnoticed until they silently transition to EXPIRED, at which point no
new placements can be created under that agreement.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for partnership records with admin-only write access and multi-role read |
| G2  | Enforce controlled status transitions via `PartnershipStatus` enum and `PartnershipState` entity |
| G3  | Store MoU documents as single-file MediaLibrary collections with web-optimized thumb conversions |
| G4  | Support partnership renewal that creates a new record while preserving audit history of the old |
| G5  | Enforce terminal-state deletion guards at both Action and Entity layers |
| G6  | Dispatch domain events on all partnership mutations with dashboard cache invalidation |
| G7  | Surface expiring partnership counts for proactive renewal planning |
| G8  | Support batch delete with per-record guard checks and detailed skip reporting |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Company profile management (covered in [company-management.md](company-management.md) §Company CRUD) |
| NG2  | Student placement assignment (owned by Enrollment module) |
| NG3  | Internship program definitions and scheduling (owned by Program module) |
| NG4  | Certificate issuance for completed internships (owned by Certification module) |
| NG5  | Multi-file document uploads or document versioning beyond single-file MoU |
| NG6  | Automated partnership expiry via scheduled tasks (manual or cron-driven outside this spec) |
| NG7  | Placement capacity tracking (covered in [company-management.md](company-management.md) §Dashboard Integration) |
| NG8  | CSV import/export for companies (covered in [csv-import-export.md](csv-import-export.md) §Company CSV) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Partnership with MoU Document

**Actor:** Admin
**Preconditions:** At least one company exists; admin has `admin` role
**Flow:**
1. Admin navigates to `admin/companies/partnerships`
2. Clicks "Add Partnership", `PartnershipManager` opens modal with `PartnershipForm`
3. Admin selects company from dropdown, fills required fields (`agreement_number`, `title`, `start_date`, `end_date`)
4. Admin optionally fills optional fields (`scope`, `contact_person_*`, `signed_by_*`, `notes`)
5. Admin optionally uploads MoU document (single file, max 10 MB)
6. `PartnershipForm` validates input via Livewire real-time rules
7. On submit, `PartnershipManager` calls `CreatePartnershipAction::execute(PartnershipData)`
8. Action creates `Partnership` with default `ACTIVE` status, stores MoU via MediaLibrary
9. Dispatches `PartnershipCreated` event
**Postconditions:** Partnership record exists with `ACTIVE` status, MoU stored (if provided), dashboard cache cleared

### UC-2 — Admin Terminates an Active Partnership

**Actor:** Admin
**Preconditions:** Partnership exists with `ACTIVE` status; admin has `admin` role
**Flow:**
1. Admin views partnership details in `PartnershipManager`
2. Clicks "Terminate" action button
3. `TerminatePartnershipAction::execute(Partnership)` is called
4. Entity `PartnershipState::isActive()` returns true — transition allowed
5. Status updated to `TERMINATED` via `PartnershipStatus::canTransitionTo(TERMINATED)`
6. Dispatches `PartnershipTerminated` event
7. `NotifyOnPartnershipTerminated` listener queues notification
8. `ClearDashboardOnPartnershipChange` listener invalidates dashboard cache
**Postconditions:** Partnership status is `TERMINATED`, notification queued, existing placements unaffected

### UC-3 — Admin Renews a Partnership (Creates New Record)

**Actor:** Admin
**Preconditions:** Previous partnership is in a terminal state (EXPIRED or TERMINATED); admin has `admin` role
**Flow:**
1. Admin views expired/terminated partnership in `PartnershipManager`
2. Clicks "Renew" action button
3. `RenewPartnershipAction::execute(oldPartnership, newPartnershipData)` is called
4. Validates old partnership is NOT active — rejects with `RejectedException` if still active
5. If old partnership is not already terminal, sets status to `EXPIRED`
6. Creates new `Partnership` record with new agreement data
7. Copies scope, contact person, signed-by, and notes from old to new partnership
8. Transfers MoU document from old to new record via MediaLibrary `registerMediaConversions`
9. Dispatches `PartnershipRenewed` event
10. `ClearDashboardOnPartnershipChange` listener invalidates dashboard cache
**Postconditions:** Old partnership is `EXPIRED`, new partnership is `ACTIVE`, MoU transferred, capacity recalculated

### UC-4 — Batch Delete with Deletion Guards

**Actor:** Admin
**Preconditions:** Multiple partnerships selected; admin has `admin` role
**Flow:**
1. Admin selects multiple partnership records in `PartnershipManager`
2. Clicks "Delete Selected" batch action
3. `BatchDeletePartnershipAction` iterates selected IDs
4. For each record: checks entity `canBeDeleted()` — returns false if status is ACTIVE
5. Records that pass the deletion guard are deleted; records that fail are skipped
6. Returns count of deleted vs skipped records with flash message
**Postconditions:** Only terminal-state records deleted, active records preserved, summary reported

### UC-5 — Admin Updates Partnership Details

**Actor:** Admin
**Preconditions:** Partnership exists; admin has `admin` role
**Flow:**
1. Admin navigates to partnership details in `PartnershipManager`
2. Clicks "Edit" action button, `PartnershipForm` opens pre-filled with current data
3. Admin modifies fields (title, scope, contact info, notes, etc.)
4. `PartnershipForm` validates updated input via Livewire real-time rules
5. On submit, `PartnershipManager` calls `UpdatePartnershipAction::execute(Partnership, PartnershipData)`
6. Action updates partnership model, dispatches `PartnershipUpdated` event
7. `ClearDashboardOnPartnershipChange` listener invalidates dashboard cache
**Postconditions:** Partnership record updated, dashboard cache cleared, success flash message shown

### UC-6 — Admin Replaces MoU Document on Existing Partnership

**Actor:** Admin
**Preconditions:** Partnership exists; admin has `admin` role
**Flow:**
1. Admin navigates to partnership details in `PartnershipManager`
2. Views current MoU thumbnail (if any) in the detail view
3. Clicks "Replace MoU" or "Upload MoU" action
4. Admin selects new file (single file, max 10 MB)
5. MediaLibrary `singleFile()` constraint automatically clears the old document
6. New document stored with `thumb` conversion generated non-queued
7. `ClearDashboardOnPartnershipChange` listener invalidates dashboard cache
**Postconditions:** Old MoU removed, new MoU stored, fresh thumbnail available

---

## 4. Functional Requirements

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
| FR-RN1 | `RenewPartnershipAction` must reject if old partnership is currently `ACTIVE` |
| FR-RN2 | `RenewPartnershipAction` must set old partnership status to `EXPIRED` if not already terminal |
| FR-RN3 | `RenewPartnershipAction` must create a new `Partnership` record with provided data |
| FR-RN4 | `RenewPartnershipAction` must copy scope, contact person, signed-by, and notes from old to new partnership |
| FR-RN5 | `RenewPartnershipAction` must transfer MoU document from old to new partnership via MediaLibrary |
| FR-RN6 | `RenewPartnershipAction` must dispatch `PartnershipRenewed` event |

### MoU Documents

| ID   | Requirement |
| ---- | ----------- |
| FR-MD1 | `Partnership` model must implement `HasMedia` interface and use `InteractsWithMedia` trait |
| FR-MD2 | `Partnership::registerMediaCollections()` must register `mou_document` as a single-file collection |
| FR-MD3 | `Partnership::registerMediaConversions()` must register `thumb` conversion at 400px width, webp format, non-queued |
| FR-MD4 | `PartnershipManager` must support MoU file upload via `WithFileUploads` trait |
| FR-MD5 | MoU upload must use `addMedia()` or `addMediaFromRequest()` to store in `mou_document` collection |
| FR-MD6 | MoU retrieval must use `getFirstMedia('mou_document')` or `getFirstMediaUrl('mou_document', 'thumb')` for thumbnail |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All partnership mutations must be authorized via `PartnershipPolicy` — no bypass allowed |
| NFR-S2 | MoU file uploads must validate MIME type and enforce maximum file size (10 MB) |
| NFR-S3 | Partnership routes must require `auth` middleware and `role:super_admin\|admin` |
| NFR-S4 | Partnership deletion must never remove records with ACTIVE status — enforced at both Action and Entity layers |
| NFR-S5 | MoU file storage must be outside the public web root — served through MediaLibrary URL generation |
| NFR-P1 | Partnership list page with company JOIN must load in < 500ms for up to 500 partnerships |
| NFR-P2 | MoU thumb conversion (400px webp) must be generated non-queued to avoid worker dependency |
| NFR-P3 | MoU thumbnail display must load in < 2s from MediaLibrary URL generation |
| NFR-P4 | Partnership detail page including MoU metadata must load in < 300ms |
| NFR-R1 | Partnership renewal must be atomic — old record status change and new record creation in a single transaction |
| NFR-R2 | Batch delete must report exactly which records were deleted and which were skipped with reasons |
| NFR-R3 | MoU transfer during renewal must be atomic — if transfer fails, neither old nor new record should persist the partial state |
| NFR-U1 | Admin must see real-time validation feedback on partnership forms |
| NFR-U2 | Deletion guard rejections must display actionable error messages with the specific reason |
| NFR-U3 | Partnership status badge must use the enum's `label()` method for consistent, translatable display |
| NFR-A1 | Partnership management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Form inputs must have associated labels |
| NFR-A3 | Real-time validation feedback must be announced to screen readers via `aria-live` |
| NFR-A4 | Status badges must convey meaning beyond color alone (text label + icon) |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 coding standard |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Partnership status labels must use `LabelEnum::label()` which calls `__()` internally |

---

## 6. API / Data Contracts

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
  canBeDeleted(): true only if isExpired() || isTerminated()
  isExpiringSoon(): true if isActive() && endDate within thresholdDays of today
```

### PartnershipStatus Enum

```
App\Partners\Partnership\Enums\PartnershipStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: ACTIVE='active', EXPIRED='expired', TERMINATED='terminated'
  Methods: label(): string, isTerminal(): bool, validTransitions(): array,
           canTransitionTo(StatusEnum): bool
  Transitions: ACTIVE→[EXPIRED, TERMINATED], EXPIRED→[], TERMINATED→[]
  Terminal states: EXPIRED, TERMINATED (isTerminal() returns true for both)
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreatePartnershipAction` | `BaseCommandAction` | `PartnershipData` | `ActionResponse` |
| `UpdatePartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` |
| `DeletePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` |
| `TerminatePartnershipAction` | `BaseCommandAction` | `Partnership` | `ActionResponse` |
| `RenewPartnershipAction` | `BaseCommandAction` | `Partnership, PartnershipData` | `ActionResponse` |
| `BatchDeletePartnershipAction` | `BaseCommandAction` | `Collection` | `ActionResponse` |

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `PartnershipCreated` | `CreatePartnershipAction` |
| `PartnershipUpdated` | `UpdatePartnershipAction` |
| `PartnershipDeleted` | `DeletePartnershipAction`, `BatchDeletePartnershipAction` |
| `PartnershipTerminated` | `TerminatePartnershipAction` |
| `PartnershipRenewed` | `RenewPartnershipAction` |

### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `ClearDashboardOnPartnershipChange` | PartnershipCreated, PartnershipUpdated, PartnershipDeleted, PartnershipTerminated, PartnershipRenewed | No |
| `NotifyOnPartnershipTerminated` | PartnershipTerminated | Yes |

### Policy

| Policy | Abilities |
| ------ | --------- |
| `PartnershipPolicy` | viewAny: super_admin/admin/teacher, view: admin/teacher, create: admin, update: admin, delete: admin |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/companies/partnerships` | `PartnershipManager` | `partners.partnerships` | `auth`, `role:super_admin\|admin` |

### Database Schema

```
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

### DD-1 — Renewal Creates New Record Instead of Updating In-Place

**Decision:** Partnership renewal creates a brand-new `Partnership` record rather than updating dates on the existing record.
**Rationale:** Preserves full audit history — the old partnership retains its original agreement number, dates, and status. The new partnership gets a new UUID and can have different terms, a new agreement number, and different signing parties. MoU document is transferred via MediaLibrary.
**Trade-off:** Old record becomes a historical artifact that consumes storage but provides complete audit trail. The `agreement_number` uniqueness constraint requires either a new number or a versioned format on renewal. Rejected alternative: soft-delete + recreate (loses foreign key references from placements — the Enrollment module references `partnership_id`). Rejected alternative: in-place update with history table (adds complexity and a second table to maintain for marginal benefit over the simpler new-record approach).

### DD-2 — MoU via MediaLibrary Single-File Collection

**Decision:** Store MoU documents using Spatie MediaLibrary with `singleFile()` constraint on the `mou_document` collection and a non-queued `thumb` conversion (400px webp).
**Rationale:** `singleFile()` enforces one MoU per partnership at the framework level — no custom guard needed in Action or Entity code. Non-queued conversion ensures the thumbnail is immediately available without queue worker dependency. WebP format reduces storage and load times. MediaLibrary provides clean API for add/get/delete/transfer operations.
**Trade-off:** Single-file constraint prevents storing multiple MoU versions (e.g., amendments, addenda) within the same partnership record. This is acceptable because amendments should be captured as new partnerships via the renewal workflow. Rejected alternative: manual file storage with `Storage::put()` (loses conversion pipeline, clean API, and single-file enforcement). Rejected alternative: multi-file collection (violates the single-MoU-per-partnership business rule and adds UI complexity).

### DD-3 — Partnership Query JOIN for Sortable Company Name

**Decision:** `PartnershipManager` JOINs the `companies` table in its query builder to expose a `company_name` column that supports sorting and filtering.
**Rationale:** Partnerships are meaningless without company context. Sorting by company name is a primary UX need in the partnership listing. Eager loading (`with('company')`) cannot participate in sortable database-level ORDER BY clauses. The JOIN approach keeps sorting in the database layer, supports pagination correctly, and avoids loading entire company models into memory for collection-level sorting.
**Trade-off:** Slightly more complex query with an explicit JOIN clause. Rejected alternative: eager load + collection sort (does not scale with pagination — only sorts the current page, not the full dataset).

### DD-4 — Entity Bridge Pattern for Business Logic Delegation

**Decision:** Models provide `asPartnershipState()` bridge methods that delegate to Entity factories. Entities are `final readonly` and contain all business rule methods (`isActive()`, `isExpired()`, `isTerminated()`, `isExpiringSoon()`, `canBeDeleted()`).
**Rationale:** Keeps Models as pure persistence objects (C1 compliance — no business logic in models). Entities provide type-safe, immutable snapshots for business logic evaluation without coupling to database state. `fromModel()` factory method standardizes construction across all entity types.
**Trade-off:** Extra class per submodule (PartnershipState entity). Rejected alternative: business methods on Model (violates C1 — Model should not contain business logic like status checks or deletion guards).

### DD-5 — Dual Deletion Guard Layers

**Decision:** Enforce deletion guards at both the Action layer (checking status before calling delete) and the Entity layer (`canBeDeleted()` using status checks).
**Rationale:** The Action-layer check handles the common path with a clear error message. The Entity-layer check provides a safety net against race conditions where concurrent requests may have transitioned the partnership status between the Action check and the actual delete. Both must agree before deletion proceeds.
**Trade-off:** Slightly more code per deletion path (both Action and Entity contain guard logic), but significantly reduces risk of deleting active partnerships. Rejected alternative: single-layer guard at database constraint level only (cannot provide user-friendly error messages or distinguish between ACTIVE and other non-deletable states).

### DD-6 — Atomic Renewal Transaction

**Decision:** `RenewPartnershipAction` wraps the old partnership status update and new partnership creation in a single database transaction.
**Rationale:** If the old partnership is marked EXPIRED but the new record creation fails (e.g., unique constraint on agreement_number), the system would be in an inconsistent state — old partnership expired with no replacement. Atomicity ensures either both succeed or neither persists.
**Trade-off:** Longer transaction hold during MoU transfer. Rejected alternative: sequential operations without transaction (risk of partial state on failure).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Orphaned placement records from partnership deletion | 0 | Deletion guard prevents delete of non-terminal partnerships |
| Invalid status transitions | 0 | `PartnershipStatus::canTransitionTo()` blocks illegal transitions at enum level |
| Duplicate MoU uploads per partnership | 0 | MediaLibrary `singleFile()` constraint enforced at framework level |
| Inconsistent state from partial renewal failure | 0 | Atomic transaction ensures old + new record persist together or not at all |
| Active partnerships deleted via batch | 0 | `canBeDeleted()` guard skips non-terminal records in batch operations |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Partnership list load with company JOIN | < 500ms | `PartnershipManager` query with 500 partnerships |
| MoU thumbnail display | < 2s | MediaLibrary URL generation for 400px webp thumb |
| Partnership detail page with MoU metadata | < 300ms | Single partnership view including media metadata |
| MoU thumb generation on upload | < 2s | Non-queued MediaLibrary conversion |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion rejection feedback | Actionable error with specific reason | `RejectedException` message on terminal-state guard failure |
| Batch delete summary | Exact deleted/skipped counts with reasons | `BatchDeletePartnershipAction` response payload |
| Form validation feedback | Real-time, < 200ms | Livewire validation rules on `PartnershipForm` |
| Status badge display | Consistent text + icon via `label()` | `PartnershipStatus::label()` with `__()` translation |
| Renewal flow | Pre-populated form with old partnership data | `RenewPartnershipAction` copies fields to new record |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | PartnershipState imports no Actions/Services |
| DTO purity (C6) | 0 violations | PartnershipData imports no Models/Entities |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all Partnership PHP files |
| RejectedException not RuntimeException (C8) | 0 violations | Business rule failures use `RejectedException` |

---

## Quick References

- `app/Partners/Partnership/Models/Partnership.php` — Partnership model with MediaLibrary, Fillable, relationships
- `app/Partners/Partnership/Entities/PartnershipState.php` — Entity with lifecycle queries and deletion guard
- `app/Partners/Partnership/Data/PartnershipData.php` — Partnership DTO (BaseData)
- `app/Partners/Partnership/Enums/PartnershipStatus.php` — Status enum with transitions and labels
- `app/Partners/Partnership/Actions/CreatePartnershipAction.php` — Create command action
- `app/Partners/Partnership/Actions/UpdatePartnershipAction.php` — Update command action
- `app/Partners/Partnership/Actions/DeletePartnershipAction.php` — Delete with terminal-state guard
- `app/Partners/Partnership/Actions/BatchDeletePartnershipAction.php` — Batch delete with per-record guard
- `app/Partners/Partnership/Actions/TerminatePartnershipAction.php` — Active → Terminated transition
- `app/Partners/Partnership/Actions/RenewPartnershipAction.php` — Renewal creating new record with MoU transfer
- `app/Partners/Partnership/Policies/PartnershipPolicy.php` — Authorization (admin writes, teacher read)
- `app/Partners/Partnership/Livewire/PartnershipManager.php` — UI with company JOIN query
- `app/Partners/Partnership/Livewire/Forms/PartnershipForm.php` — Form validation
- `app/Partners/Partnership/Events/PartnershipCreated.php` — Created event
- `app/Partners/Partnership/Events/PartnershipUpdated.php` — Updated event
- `app/Partners/Partnership/Events/PartnershipDeleted.php` — Deleted event
- `app/Partners/Partnership/Events/PartnershipTerminated.php` — Terminated event
- `app/Partners/Partnership/Events/PartnershipRenewed.php` — Renewed event
- `app/Partners/Partnership/Listeners/ClearDashboardOnPartnershipChange.php` — Cache invalidation listener
- `app/Partners/Partnership/Listeners/NotifyOnPartnershipTerminated.php` — Queued termination notification
- `database/migrations/2026_01_03_000007_create_partnerships_table.php` — Partnerships schema
- `routes/web/partners.php` — Route definitions
- `docs/modules/partners.md` — Module conceptual documentation
- `docs/modules/partners-reference.md` — Module technical reference
