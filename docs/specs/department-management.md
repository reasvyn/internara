# Department Management — CRUD, Deletion Guards & CSV Import

> **Last updated:** 2026-07-22 **Changes:** feat — split from institutional-and-academics.md;
> dedicated spec covering Department CRUD, profile dependency deletion guard, CSV import/export,
> and event-driven cache invalidation

## Description

Specification of Internara's department management subsystem. Departments are the primary
organizational unit for grouping students and teachers within a school. This spec covers
the Department entity lifecycle (create, read, update, delete), the profile dependency
deletion guard that prevents orphaning assigned profiles, bulk CSV import/export for
migration and setup, and event dispatch with dashboard cache invalidation.

School profile, academic year lifecycle, and settings infrastructure are separate initiatives —
see [school-profile.md](school-profile.md),
[settings-infrastructure.md](settings-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Department CRUD for Organizational Grouping

Schools organize students and teachers into departments (e.g., "Computer Science",
"Accounting", "Mechanical Engineering"). Without a Department entity, the system has no way
to group profiles, filter internship assignments, or produce department-level reports.
Manual tracking via spreadsheets is error-prone and breaks referential integrity when profiles
are reassigned.

### PS-2 — Department Deletion with Profile Dependencies

Departments can have student and teacher profiles assigned to them via a `hasMany` relationship.
Deleting a department that still has assigned profiles would orphan those profiles, setting their
`department_id` foreign key to NULL or violating integrity constraints. The system must detect
this dependency and prevent deletion, requiring explicit reassignment first.

### PS-3 — Bulk Department Setup via CSV Import

During initial system setup, schools typically have 10–50 departments already defined in
spreadsheets or other systems. Manual entry of each department is tedious and error-prone.
The system must support CSV import for bulk creation, with duplicate detection and error
reporting. Export is equally important for reporting, migration, and backup.

### PS-4 — Dashboard Cache Invalidation on Department Changes

The admin dashboard displays aggregate statistics that include department counts and
profile-per-department distributions. When departments are created, updated, or deleted,
these statistics become stale. Without cache invalidation, the dashboard shows outdated
numbers until the cache TTL expires, causing confusion for administrators making decisions
based on current data.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full Department CRUD (create, read, update, soft-delete) with validation |
| G2  | Enforce profile dependency deletion guard (departments with profiles cannot be deleted) |
| G3  | Support CSV import/export for bulk department creation and migration |
| G4  | Dispatch domain events on all department CRUD operations |
| G5  | Invalidate dashboard cache on any department change via event listener |
| G6  | Support bulk delete with per-item deletion guard checks |
| G7  | Provide CSV template download for import guidance |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Department merge or transfer operations (reassign profiles individually instead) |
| NG2  | Department hierarchy / nesting (flat structure only) |
| NG3  | Department-level user management (managed via User module) |
| NG4  | Department archiving or soft-delete (hard delete only) |
| NG5  | Cross-school department sharing (single-tenant) |
| NG6  | Department-level permissions or RBAC (managed via Roles module) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Department

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with department management permission
**Flow:**
1. Admin navigates to Academics → Departments
2. `DepartmentManager` component loads and displays the department list
3. Admin clicks "Create" button
4. `DepartmentForm` modal opens with empty name and description fields
5. Admin fills in department name (required, max 100 chars) and optional description
6. Admin submits the form
7. `DepartmentForm` validates input (name uniqueness, required fields)
8. `CreateDepartmentAction` executes:
   - Validates name uniqueness against `departments` table
   - Creates `Department` model with `#[Fillable]` attributes
   - Dispatches `DepartmentCreated` event
   - Logs the creation via activity log
9. `ClearDashboardCacheOnDepartmentChange` listener receives `DepartmentCreated`
10. Listener calls `Cache::forget(config('cache-keys.admin_dashboard_stats'))`
11. Flash success message: "Department created successfully"
**Postconditions:** Department created, dashboard cache invalidated, activity logged

### UC-2 — Admin Updates a Department

**Actor:** Admin / Super Admin
**Preconditions:** Department exists; admin is authenticated with update permission
**Flow:**
1. Admin clicks "Edit" on a department row
2. `DepartmentForm` modal opens with pre-filled name and description
3. Admin modifies fields and submits
4. `DepartmentForm` validates (name uniqueness excluding current department)
5. `UpdateDepartmentAction` executes:
   - Validates name uniqueness with `unique:departments,name,{current_id}`
   - Updates `Department` model attributes
   - Dispatches `DepartmentUpdated` event
   - Logs the update via activity log
6. `ClearDashboardCacheOnDepartmentChange` listener invalidates dashboard cache
7. Flash success message: "Department updated successfully"
**Postconditions:** Department updated, cache invalidated, activity logged

### UC-3 — Admin Attempts to Delete Department with Profiles

**Actor:** Admin / Super Admin
**Preconditions:** Department has assigned profiles (students or teachers)
**Flow:**
1. Admin clicks "Delete" on a department row
2. Confirmation dialog appears: "Are you sure you want to delete {name}?"
3. Admin confirms deletion
4. `executeDelete()` checks `DepartmentState::canBeDeleted()`:
   - Loads `profileCount` from relationship
   - `hasProfiles` is true (profileCount > 0)
   - `canBeDeleted()` returns false
5. Flash error message: "Cannot delete department with {n} assigned profile(s)"
6. `DeleteDepartmentAction` is NOT called
7. Admin must reassign profiles to another department before deletion
**Postconditions:** Deletion blocked, admin informed of dependency, department unchanged

### UC-4 — Admin Deletes a Department (No Profiles)

**Actor:** Admin / Super Admin
**Preconditions:** Department has zero assigned profiles
**Flow:**
1. Admin clicks "Delete" on a department row
2. Confirmation dialog appears
3. Admin confirms deletion
4. `executeDelete()` checks `DepartmentState::canBeDeleted()` → returns true
5. `DeleteDepartmentAction` executes:
   - Verifies profile dependency via `profiles()->count() > 0` (redundant guard)
   - Wraps deletion in transaction
   - Deletes `Department` model
   - Dispatches `DepartmentDeleted` event
   - Logs the deletion via activity log
6. `ClearDashboardCacheOnDepartmentChange` listener invalidates dashboard cache
7. Flash success message: "Department deleted successfully"
**Postconditions:** Department removed, cache invalidated, activity logged

### UC-5 — Admin Bulk Deletes Departments

**Actor:** Admin / Super Admin
**Preconditions:** Multiple departments selected via checkbox
**Flow:**
1. Admin selects departments via row checkboxes
2. Admin clicks "Delete Selected"
3. Confirmation dialog: "Are you sure you want to delete selected departments?"
4. Admin confirms
5. `executeDeleteSelected()` iterates selected IDs:
   - For each department, checks `canBeDeleted()` before calling `DeleteDepartmentAction`
   - Departments with profiles are skipped (counted as `blocked`)
   - Departments without profiles are deleted (counted as `deleted`)
6. Flash success with count: "{n} department(s) deleted"
7. If any were blocked, flash warning: "{n} department(s) could not be deleted (have profiles)"
**Postconditions:** Eligible departments deleted, blocked ones preserved, counts reported

### UC-6 — Admin Imports Departments from CSV

**Actor:** Admin / Super Admin
**Preconditions:** Admin has a CSV file with department names and descriptions
**Flow:**
1. Admin navigates to Academics → Departments
2. Admin clicks "Import" or drags a CSV file onto the import zone
3. `DepartmentManager` validates file: required `mimes:csv,txt`, max 2MB
4. `import()` method calls `CsvHandler::import()` with row processor:
   - Row format: `[name, description]` (2 columns)
   - Empty name rows → skipped (null return)
   - Duplicate name rows → `CsvRowResult::SKIPPED`
   - Valid rows → `CreateDepartmentAction::execute()` → `CsvRowResult::CREATED`
5. `CsvHandler` returns summary: `{created, skipped, invalid}`
6. If invalid (wrong headers), flash error: "Import file has invalid format"
7. If valid, flash summary: "{n} created, {m} skipped (duplicates)"
**Postconditions:** Departments created from CSV, duplicates skipped, summary shown

### UC-7 — Admin Downloads CSV Template

**Actor:** Admin / Super Admin
**Preconditions:** None
**Flow:**
1. Admin clicks "Download Template" on department manager
2. `downloadTemplate()` calls `CsvHandler::downloadTemplate()`
3. Returns streamed CSV with headers `name,description` and one example row
**Postconditions:** Template downloaded, ready for editing

### UC-8 — Admin Exports Departments to CSV

**Actor:** Admin / Super Admin
**Preconditions:** At least one department exists
**Flow:**
1. Admin clicks "Export" on department manager
2. `export()` queries departments (filtered by search if active), ordered by name
3. `CsvHandler::export()` streams CSV with columns: name, description
4. File downloads as `departments.csv`
**Postconditions:** CSV file downloaded with all matching departments

---

## 4. Functional Requirements

### Department Model

| ID   | Requirement |
| ---- | ----------- |
| FR-DM1 | `Department` model must use `#[Fillable]` attribute with `name` and `description` |
| FR-DM2 | `Department` must extend `BaseModel` and use `HasFactory` trait |
| FR-DM3 | `Department` must have `hasMany` relationship with `Profile` model |
| FR-DM4 | `Department` must provide `asDepartmentState()` bridge method returning `DepartmentState` entity |
| FR-DM5 | `Department` must use `DepartmentFactory` for test data generation |

### Department State Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-DM6 | `DepartmentState` must be `final readonly` extending `BaseEntity` |
| FR-DM7 | `DepartmentState::fromModel()` must compute `profileCount` from loaded relation or query count |
| FR-DM8 | `DepartmentState::fromModel()` must compute `hasProfiles` using eager-loaded check or `exists()` |
| FR-DM9 | `DepartmentState::canBeDeleted()` must return `false` when `hasProfiles` is `true` |
| FR-DM10 | `DepartmentState::canBeDeleted()` must return `true` when `hasProfiles` is `false` |

### Department Actions

| ID   | Requirement |
| ---- | ----------- |
| FR-DM11 | `CreateDepartmentAction` must extend `BaseCommandAction` and validate name uniqueness |
| FR-DM12 | `CreateDepartmentAction` must wrap creation in a transaction |
| FR-DM13 | `CreateDepartmentAction` must dispatch `DepartmentCreated` event |
| FR-DM14 | `CreateDepartmentAction` must log creation via activity log |
| FR-DM15 | `UpdateDepartmentAction` must extend `BaseCommandAction` and validate name uniqueness excluding current record |
| FR-DM16 | `UpdateDepartmentAction` must wrap update in a transaction |
| FR-DM17 | `UpdateDepartmentAction` must dispatch `DepartmentUpdated` event |
| FR-DM18 | `UpdateDepartmentAction` must log update via activity log |
| FR-DM19 | `DeleteDepartmentAction` must extend `BaseCommandAction` and check `profiles()->count() > 0` before deleting |
| FR-DM20 | `DeleteDepartmentAction` must throw `RejectedException` when profiles are assigned |
| FR-DM21 | `DeleteDepartmentAction` must wrap deletion in a transaction |
| FR-DM22 | `DeleteDepartmentAction` must dispatch `DepartmentDeleted` event |
| FR-DM23 | `DeleteDepartmentAction` must log deletion via activity log |

### Department Policy

| ID   | Requirement |
| ---- | ----------- |
| FR-DM24 | `DepartmentPolicy::viewAny()` must return `true` for all authenticated users |
| FR-DM25 | `DepartmentPolicy::view()` must return `true` for all authenticated users |
| FR-DM26 | `DepartmentPolicy::create()` must require admin role |
| FR-DM27 | `DepartmentPolicy::update()` must require admin role |
| FR-DM28 | `DepartmentPolicy::delete()` must require admin role AND `canBeDeleted()` to return true |
| FR-DM29 | `DepartmentPolicy::forceDelete()` must always return `false` |

### Department Data Transfer Object

| ID   | Requirement |
| ---- | ----------- |
| FR-DM30 | `DepartmentData` must be `final readonly` extending `BaseData` |
| FR-DM31 | `DepartmentData` must contain `name` (string), `description` (?string), `id` (?string) |

### Department Form Object

| ID   | Requirement |
| ---- | ----------- |
| FR-DM32 | `DepartmentForm` must extend Livewire `Form` with `id`, `name`, `description` properties |
| FR-DM33 | `DepartmentForm::rules()` must validate name as required, string, max 255, unique excluding current ID |
| FR-DM34 | `DepartmentForm::rules()` must validate description as nullable, string, max 1000 |
| FR-DM35 | `DepartmentForm::toArray()` must return array with `id`, `name`, `description` |

### Department Manager (Livewire)

| ID   | Requirement |
| ---- | ----------- |
| FR-DM36 | `DepartmentManager` must extend `BaseRecordManager` and use `WithFileUploads` |
| FR-DM37 | `DepartmentManager::headers()` must define columns: name, description, created_at, actions |
| FR-DM38 | `DepartmentManager::query()` must return `Department::query()` |
| FR-DM39 | `DepartmentManager::applySearch()` must filter by name using `LIKE` |
| FR-DM40 | `DepartmentManager::create()` must authorize via `create` policy and reset form |
| FR-DM41 | `DepartmentManager::edit()` must load department, authorize via `update` policy, populate form |
| FR-DM42 | `DepartmentManager::save()` must dispatch to `CreateDepartmentAction` or `UpdateDepartmentAction` based on form ID |
| FR-DM43 | `DepartmentManager::askDelete()` must show confirmation dialog with department name |
| FR-DM44 | `DepartmentManager::askDeleteSelected()` must show bulk delete confirmation |
| FR-DM45 | `DepartmentManager::confirmAction()` must handle `RejectedException` with flash error |

### CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-DM46 | `DepartmentManager::import()` must authorize `create` policy before processing |
| FR-DM47 | `DepartmentManager::import()` must validate file as `mimes:csv,txt` with max 2MB |
| FR-DM48 | `CsvHandler::import()` must parse CSV with columns `[name, description]` |
| FR-DM49 | Rows with empty name must be skipped (return null) |
| FR-DM50 | Rows with duplicate names must return `CsvRowResult::SKIPPED` |
| FR-DM51 | Valid rows must call `CreateDepartmentAction::execute()` and return `CsvRowResult::CREATED` |
| FR-DM52 | Import summary must report created count and skipped count via flash message |
| FR-DM53 | Invalid CSV format (wrong headers) must flash error: import_invalid |
| FR-DM54 | `DepartmentManager::export()` must stream CSV with headers `[name, description]` |
| FR-DM55 | `DepartmentManager::export()` must apply search filter when active |
| FR-DM56 | `DepartmentManager::exportSelected()` must export only selected department IDs |
| FR-DM57 | `DepartmentManager::downloadTemplate()` must stream CSV with headers and one example row |

### Events & Listeners

| ID   | Requirement |
| ---- | ----------- |
| FR-DM58 | `DepartmentCreated` event must extend `BaseEvent` and carry the `Department` model |
| FR-DM59 | `DepartmentUpdated` event must extend `BaseEvent` and carry the `Department` model |
| FR-DM60 | `DepartmentDeleted` event must extend `BaseEvent` and carry the `Department` model |
| FR-DM61 | All three events must provide `eventName()` returning `department.created`, `department.updated`, `department.deleted` |
| FR-DM62 | `ClearDashboardCacheOnDepartmentChange` listener must handle all three department events |
| FR-DM63 | Listener must call `Cache::forget(config('cache-keys.admin_dashboard_stats'))` |
| FR-DM64 | Event-to-listener mapping must be registered in `config/event.php` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Department CRUD operations must complete in < 1s (create, update, delete) |
| NFR-P2 | CSV import of 50 departments must complete in < 15s |
| NFR-P3 | Department list page must load in < 500ms for up to 200 departments |
| NFR-P4 | CSV export must stream without buffering the entire dataset in memory |
| NFR-S1 | All department mutations must be authorized via `DepartmentPolicy` |
| NFR-S2 | CSV import file must be validated for MIME type and max 2MB size |
| NFR-S3 | Department name must be unique (enforced at DB and Action level) |
| NFR-S4 | Force delete must always be forbidden (`DepartmentPolicy::forceDelete()` returns false) |
| NFR-R1 | Department CRUD operations must be wrapped in database transactions |
| NFR-R2 | Bulk delete must handle partial failures gracefully (delete eligible, skip blocked) |
| NFR-R3 | CSV import must be idempotent — duplicate names are skipped, not errored |
| NFR-U1 | Department deletion blocked message must explain how many profiles are assigned |
| NFR-U2 | Bulk delete feedback must separately report deleted count and blocked count |
| NFR-U3 | CSV import summary must show created and skipped counts |
| NFR-U4 | Department form must show inline validation errors on name uniqueness violation |
| NFR-A1 | Department management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Deletion blocked messages must be accessible to screen readers |
| NFR-A3 | All form inputs must have associated labels |
| NFR-M1 | All PHP files must declare `strict_types=1` |
| NFR-M2 | All entities must be `final readonly` with no framework imports |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### 6.1 Department Model

```php
// app/Academics/Department/Models/Department.php
class Department extends BaseModel
{
    use HasFactory;

    // #[Fillable(['name', 'description'])]

    public function profiles(): HasMany;  // → Profile::class
    public function asDepartmentState(): DepartmentState;
    protected static function newFactory(): DepartmentFactory;
}
```

### 6.2 DepartmentState Entity

```php
// app/Academics/Department/Entities/DepartmentState.php
final readonly class DepartmentState extends BaseEntity
{
    public function __construct(
        private int $profileCount,
        private bool $hasProfiles,
    ) {}

    public static function fromModel(Model $model): static;
    // Computes profileCount from loaded relation count or query
    // Computes hasProfiles from eager-loaded check or exists()

    public function canBeDeleted(): bool;
    // Returns !$this->hasProfiles
}
```

### 6.3 DepartmentData DTO

```php
// app/Academics/Department/Data/DepartmentData.php
final readonly class DepartmentData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $id = null,
    ) {}
}
```

### 6.4 CreateDepartmentAction

```php
// app/Academics/Department/Actions/CreateDepartmentAction.php
final class CreateDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department;
    // Validates: name (required, string, max:100, unique:departments,name)
    //            description (nullable, string, max:500)
    // Transaction: Department::create → dispatch DepartmentCreated → log
}
```

### 6.5 UpdateDepartmentAction

```php
// app/Academics/Department/Actions/UpdateDepartmentAction.php
final class UpdateDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department, array $data): Department;
    // Validates: name (required, string, max:100, unique:departments,name,{id})
    //            description (nullable, string, max:500)
    // Transaction: department->update → dispatch DepartmentUpdated → log
}
```

### 6.6 DeleteDepartmentAction

```php
// app/Academics/Department/Actions/DeleteDepartmentAction.php
final class DeleteDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department): void;
    // Guard: profiles()->count() > 0 → throw RejectedException
    // Transaction: department->delete → dispatch DepartmentDeleted → log
}
```

### 6.7 DepartmentPolicy

```php
// app/Academics/Department/Policies/DepartmentPolicy.php
class DepartmentPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool;    // true (all users)
    public function view(?User $user, Department $department): bool; // true
    public function create(User $user): bool;      // isAdmin($user)
    public function update(User $user, Department $department): bool; // isAdmin($user)
    public function delete(User $user, Department $department): bool;
        // isAdmin($user) && $department->asDepartmentState()->canBeDeleted()
    public function forceDelete(User $user, Department $department): bool; // false (always)
}
```

### 6.8 DepartmentForm (Livewire)

```php
// app/Academics/Department/Livewire/Forms/DepartmentForm.php
class DepartmentForm extends Form
{
    public ?string $id = null;
    public string $name = '';
    public string $description = '';

    public function rules(): array;
    // name: required, string, max:255, unique:departments,name,{id}
    // description: nullable, string, max:1000

    public function toArray(): array;
    // ['id' => ..., 'name' => ..., 'description' => ...]
}
```

### 6.9 Events

```php
// app/Academics/Department/Events/DepartmentCreated.php
final class DepartmentCreated extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.created'; }
}

// app/Academics/Department/Events/DepartmentUpdated.php
final class DepartmentUpdated extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.updated'; }
}

// app/Academics/Department/Events/DepartmentDeleted.php
final class DepartmentDeleted extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.deleted'; }
}
```

### 6.10 Listener

```php
// app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php
final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentDeleted|DepartmentUpdated $event): void;
    // Calls Cache::forget(config('cache-keys.admin_dashboard_stats'))
}
```

### 6.11 Event Registration

```php
// config/event.php
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Events\DepartmentUpdated;
use App\User\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;

DepartmentCreated::class => [ClearDashboardCacheOnDepartmentChange::class],
DepartmentDeleted::class => [ClearDashboardCacheOnDepartmentChange::class],
DepartmentUpdated::class => [ClearDashboardCacheOnDepartmentChange::class],
```

### 6.12 Routes

```php
// routes/web/academics.php
Route::prefix('admin')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/departments', DepartmentManager::class)->name('departments');
});
```

### 6.13 Database Schema

```
departments:
  id:          uuid (PK)
  name:        varchar (unique, not null)
  description: text (nullable)
  created_at:  timestamp (nullable)
  updated_at:  timestamp (nullable)

Migration: database/migrations/2026_01_03_000002_create_departments_table.php
```

### 6.14 CsvHandler Contract

```php
// app/Core/Support/CsvHandler.php
final class CsvHandler
{
    // Import: parses CSV, calls rowProcessor for each row
    public function import(
        string $filePath,
        callable $rowProcessor,
        ?array $expectedHeaders = null,
    ): array;  // ['created' => int, 'skipped' => int, 'invalid' => bool]

    // Export: streams CSV from Collection
    public function export(
        Collection $items,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
    ): StreamedResponse;

    // Template: streams single-row CSV
    public function downloadTemplate(
        array $headers,
        array $exampleRow,
        string $filename = 'template.csv',
    ): StreamedResponse;
}
```

---

## 7. Design Decisions

### DD-1 — Department Deletion Guard via Entity, Not Policy

**Decision:** Department deletion guard uses `DepartmentState::canBeDeleted()` (Entity layer),
checked by both `DepartmentPolicy::delete()` (authorization) and `DeleteDepartmentAction`
(business logic).

**Rationale:** Separating the business rule (has profiles → cannot delete) from authorization
(is admin → can delete) makes both independently testable and follows the project's architecture
invariant C1. The entity encapsulates domain logic; the policy enforces access control; the
action enforces the rule at the mutation boundary. This triple-guard pattern ensures deletion
is blocked regardless of whether the caller goes through the policy gate (UI) or calls the
action directly (CLI, test, API).

**Trade-off:** Extra class (`DepartmentState`) for what is essentially a boolean check.
Mitigated by the entity being reusable for dashboard statistics (profile count) and future
business rule queries. Rejected alternative: putting the check in the Policy alone (violates
separation of concerns; policies should not contain domain queries).

### DD-2 — Hard Delete Without Soft Deletes

**Decision:** Department deletion is a hard delete (`$department->delete()`), not a soft delete.
`forceDelete()` is always forbidden.

**Rationale:** Departments are simple organizational units with no historical data dependencies.
Unlike academic years (which are referenced by internships and assessments), departments have
no cascading historical records that need to be preserved. Soft deletes add complexity (global
scopes, deleted-at filtering, restore logic) without benefit for this entity. If historical
tracking is needed, the activity log already captures department creation/deletion events.

**Trade-off:** Deleted departments cannot be restored. Mitigated by the activity log containing
the department name and data for recreation. Rejected alternative: soft deletes (unnecessary
complexity for a simple entity).

### DD-3 — CSV Import via Shared CsvHandler Service

**Decision:** Department CSV import/export uses the shared `App\Core\Support\CsvHandler`
service, not a module-specific implementation.

**Rationale:** CSV parsing, streaming, and template generation are cross-cutting concerns.
`CsvHandler` already handles file opening, header validation, row iteration, and streamed
responses. Duplicating this for departments would violate DRY. The department module only
provides the row processor callback (column mapping and `CreateDepartmentAction` invocation).

**Trade-off:** The shared service is generic — department-specific validation (e.g., column
count, name format) happens in the row processor, not the handler. Mitigated by clear error
reporting via `CsvRowResult` enum and flash message summaries.

### DD-4 — Dashboard Cache Invalidation via Event Listener

**Decision:** Dashboard cache is invalidated by a single listener class
(`ClearDashboardCacheOnDepartmentChange`) that handles all three department events (Created,
Updated, Deleted).

**Rationale:** All three events have the same effect on dashboard statistics — the cached
counts become stale. A single listener with a union type handle method (`DepartmentCreated|
DepartmentDeleted|DepartmentUpdated`) is simpler than three separate listeners. The listener
is registered in `config/event.php` (Laravel convention) rather than using attribute-based
event discovery, providing explicit control over event-to-listener mapping.

**Trade-off:** The listener is coupled to all department events. Mitigated by the events
being stable (CRUD operations unlikely to change). Rejected alternative: three separate
listeners (unnecessary duplication for identical logic).

### DD-5 — Bulk Delete with Per-Item Guard Checks

**Decision:** `executeDeleteSelected()` checks `canBeDeleted()` for each department individually,
deleting eligible ones and skipping blocked ones, then reporting both counts.

**Rationale:** Bulk operations should not fail entirely when one item is blocked. The user
needs to know which departments were deleted and which were preserved (and why). This partial
success pattern is more user-friendly than an all-or-nothing approach. The iteration happens
inside the component method, not in the Action, because the Action operates on a single
department.

**Trade-off:** Iterative execution is slower than a single bulk query. Mitigated by the typical
batch size being small (10–50 departments). Rejected alternative: bulk delete query (cannot
check per-item guard or dispatch individual events).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion guard | 100% of departments with profiles blocked | `DepartmentState::canBeDeleted()` unit tests |
| Name uniqueness | 0 duplicate departments after CSV import | `CsvRowResult::SKIPPED` for duplicates |
| Event dispatch coverage | Every CRUD → event dispatched | Listener integration tests for all 3 events |
| Force delete always blocked | 0 force deletes possible | `DepartmentPolicy::forceDelete()` returns false |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| CSV import 50 departments | < 15s | `CsvHandler` chunk processing time |
| Department list page | < 500ms for 200 departments | Livewire component render time |
| Single CRUD operation | < 1s | Action execution + event dispatch |
| Cache invalidation | < 100ms | `Cache::forget()` call time |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion blocked feedback | Shows assigned profile count | Flash message includes `{count}` |
| Bulk delete feedback | Separate deleted/blocked counts | Flash messages for both outcomes |
| CSV import feedback | Shows created/skipped counts | Flash summary after import |
| Template download | Available in one click | `downloadTemplate()` method |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | `DepartmentState` imports no Actions |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |
| Debug calls absent (D2) | 0 violations | No dd/dump/ray/var_dump/print_r/die |

---

## Quick References

- `app/Academics/Department/Models/Department.php` — Department model with `#[Fillable]` and `hasMany` Profile
- `app/Academics/Department/Entities/DepartmentState.php` — Deletion guard entity (`canBeDeleted()`)
- `app/Academics/Department/Data/DepartmentData.php` — Department DTO extending `BaseData`
- `app/Academics/Department/Actions/CreateDepartmentAction.php` — Department creation with validation
- `app/Academics/Department/Actions/UpdateDepartmentAction.php` — Department update with name uniqueness
- `app/Academics/Department/Actions/DeleteDepartmentAction.php` — Department deletion with profile guard
- `app/Academics/Department/Livewire/DepartmentManager.php` — Department CRUD UI + CSV import/export
- `app/Academics/Department/Livewire/Forms/DepartmentForm.php` — Livewire form validation
- `app/Academics/Department/Policies/DepartmentPolicy.php` — Authorization with deletion guard
- `app/Academics/Department/Events/DepartmentCreated.php` — Create event extending `BaseEvent`
- `app/Academics/Department/Events/DepartmentUpdated.php` — Update event extending `BaseEvent`
- `app/Academics/Department/Events/DepartmentDeleted.php` — Delete event extending `BaseEvent`
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` — Dashboard cache invalidation
- `app/Core/Support/CsvHandler.php` — Shared CSV import/export service
- `config/event.php` — Event-to-listener registration
- `database/migrations/2026_01_03_000002_create_departments_table.php` — Departments table schema
- `routes/web/academics.php` — Department route registration
- `docs/modules/academics.md` — Academics module overview
- `docs/modules/academics-reference.md` — Academics module technical reference
- **Related specs:** [school-profile.md](school-profile.md) — School entity; [academic-year-management.md](academic-year-management.md) — Academic year lifecycle
