# Department Management — CRUD, Deletion Guards & Cache Invalidation

> **Spec ID:** 4HWSB

## Description

Specification of Internara's department management subsystem. Departments are the primary
organizational unit for grouping students and teachers within a school. This spec covers
the Department entity lifecycle (create, read, update, delete), the profile dependency
deletion guard that prevents orphaning assigned profiles, and event dispatch with dashboard
cache invalidation. Bulk file import/export is defined in
[department-bulk-import.md](O2KCR-csv-import-export.md).

School profile, academic year lifecycle, and settings infrastructure are separate initiatives —
see [school-profile.md](81SMS-school-profile.md),
[settings-infrastructure.md](YB22J-settings-infrastructure.md).

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
| G4  | Dispatch domain events on all department CRUD operations |
| G5  | Invalidate dashboard cache on any department change via event listener |
| G6  | Support bulk delete with per-item deletion guard checks |

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

### UC-4HWSB-1 — Admin Creates a Department

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with department management permission
**Flow:**
1. Admin navigates to Academics → Departments
2. `DepartmentManager` component loads and displays the department list
3. Admin clicks "Create" button
4. `DepartmentForm` modal opens with empty name and description fields
5. Admin fills in department name (required, max 255 chars) and optional description (max 1000)
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

### UC-4HWSB-2 — Admin Updates a Department

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

### UC-4HWSB-3 — Admin Attempts to Delete Department with Profiles

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

### UC-4HWSB-4 — Admin Deletes a Department (No Profiles)

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

### UC-4HWSB-5 — Admin Bulk Deletes Departments

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

## 4. Functional Requirements

### Department Model

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM1 | `Department` model must use `#[Fillable]` attribute with `name` and `description` |
| FR-4HWSB-DM2 | `Department` must extend `BaseModel` and use `HasFactory` trait |
| FR-4HWSB-DM3 | `Department` must have `hasMany` relationship with `Profile` model |
| FR-4HWSB-DM4 | `Department` must provide `asDepartmentState()` bridge method returning `DepartmentState` entity |
| FR-4HWSB-DM5 | `Department` must use `DepartmentFactory` for test data generation |

### Department State Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM6 | `DepartmentState` must be `final readonly` extending `BaseEntity` |
| FR-4HWSB-DM7 | `DepartmentState::fromModel()` must compute `profileCount` from loaded relation or query count |
| FR-4HWSB-DM8 | `DepartmentState::fromModel()` must compute `hasProfiles` using eager-loaded check or `exists()` |
| FR-4HWSB-DM9 | `DepartmentState::canBeDeleted()` must return `false` when `hasProfiles` is `true` |
| FR-4HWSB-DM10 | `DepartmentState::canBeDeleted()` must return `true` when `hasProfiles` is `false` |

### Department Actions

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM11 | `CreateDepartmentAction` must extend `BaseCommandAction` and validate name uniqueness |
| FR-4HWSB-DM12 | `CreateDepartmentAction` must wrap creation in a transaction |
| FR-4HWSB-DM13 | `CreateDepartmentAction` must dispatch `DepartmentCreated` event |
| FR-4HWSB-DM14 | `CreateDepartmentAction` must log creation via activity log |
| FR-4HWSB-DM15 | `UpdateDepartmentAction` must extend `BaseCommandAction` and validate name uniqueness excluding current record |
| FR-4HWSB-DM16 | `UpdateDepartmentAction` must wrap update in a transaction |
| FR-4HWSB-DM17 | `UpdateDepartmentAction` must dispatch `DepartmentUpdated` event |
| FR-4HWSB-DM18 | `UpdateDepartmentAction` must log update via activity log |
| FR-4HWSB-DM19 | `DeleteDepartmentAction` must extend `BaseCommandAction` and check `profiles()->exists()` (or `count() > 0`) before deleting — `exists()` preferred for performance (same semantics) |
| FR-4HWSB-DM20 | `DeleteDepartmentAction` must throw `RejectedException` when profiles are assigned |
| FR-4HWSB-DM21 | `DeleteDepartmentAction` must wrap deletion in a transaction |
| FR-4HWSB-DM22 | `DeleteDepartmentAction` must dispatch `DepartmentDeleted` event |
| FR-4HWSB-DM23 | `DeleteDepartmentAction` must log deletion via activity log |

### Department Policy

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM24 | `DepartmentPolicy::viewAny()` must return `true` for all authenticated users |
| FR-4HWSB-DM25 | `DepartmentPolicy::view()` must return `true` for all authenticated users |
| FR-4HWSB-DM26 | `DepartmentPolicy::create()` must require admin role |
| FR-4HWSB-DM27 | `DepartmentPolicy::update()` must require admin role |
| FR-4HWSB-DM28 | `DepartmentPolicy::delete()` must require admin role AND `canBeDeleted()` to return true |
| FR-4HWSB-DM29 | `DepartmentPolicy::forceDelete()` must always return `false` |

### Department Data Transfer Object

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM30 | `DepartmentData` must be `final readonly` extending `BaseData` |
| FR-4HWSB-DM31 | `DepartmentData` must contain `name` (string), `description` (?string), `id` (?string) |

### Department Form Object

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM32 | `DepartmentForm` must extend Livewire `Form` with `id`, `name`, `description` properties |
| FR-4HWSB-DM33 | `DepartmentForm::rules()` must validate name as required, string, max 255, unique excluding current ID |
| FR-4HWSB-DM34 | `DepartmentForm::rules()` must validate description as nullable, string, max 1000 |
| FR-4HWSB-DM35 | `DepartmentForm::toArray()` must return array with `id`, `name`, `description` |

### Department Manager (Livewire)

| ID   | Requirement |
| ---- | ----------- |
| FR-4HWSB-DM36 | `DepartmentManager` must extend `BaseRecordManager` and use `WithFileUploads` |
| FR-4HWSB-DM37 | `DepartmentManager::headers()` must define columns: name, description, created_at, actions |
| FR-4HWSB-DM38 | `DepartmentManager::query()` must return `Department::query()` |
| FR-4HWSB-DM39 | `DepartmentManager::applySearch()` must filter by name using `LIKE` |
| FR-4HWSB-DM40 | `DepartmentManager::create()` must authorize via `create` policy and reset form |
| FR-4HWSB-DM41 | `DepartmentManager::edit()` must load department, authorize via `update` policy, populate form |
| FR-4HWSB-DM42 | `DepartmentManager::save()` must dispatch to `CreateDepartmentAction` or `UpdateDepartmentAction` based on form ID |
| FR-4HWSB-DM43 | `DepartmentManager::askDelete()` must show confirmation dialog with department name |
| FR-4HWSB-DM44 | `DepartmentManager::askDeleteSelected()` must show bulk delete confirmation |
| FR-4HWSB-DM45 | `DepartmentManager::confirmAction()` must handle `RejectedException` with flash error |

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-4HWSB-S1 | All department mutations must be authorized via `DepartmentPolicy` |
| NFR-4HWSB-S3 | Department name must be unique (enforced at DB and Action level) |
| NFR-4HWSB-S4 | Force delete must always be forbidden (`DepartmentPolicy::forceDelete()` returns false) |
| NFR-4HWSB-R1 | Department CRUD operations must be wrapped in database transactions |
| NFR-4HWSB-R2 | Bulk delete must handle partial failures gracefully (delete eligible, skip blocked) |
| NFR-4HWSB-U1 | Department deletion blocked message must explain how many profiles are assigned |
| NFR-4HWSB-U2 | Bulk delete feedback must separately report deleted count and blocked count |
| NFR-4HWSB-U4 | Department form must show inline validation errors on name uniqueness violation |
| NFR-4HWSB-A1 | Department management UI must meet WCAG 2.1 Level AA |
| NFR-4HWSB-A2 | Deletion blocked messages must be accessible to screen readers |
| NFR-4HWSB-A3 | All form inputs must have associated labels |
| NFR-4HWSB-M1 | All PHP files must declare `strict_types=1` |
| NFR-4HWSB-M2 | All entities must be `final readonly` with no framework imports |
| NFR-4HWSB-L1 | All user-facing strings must use `__()` translation helper |
| NFR-4HWSB-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

## 6. API / Data Contracts

### 6.1 Department Model

```php
// app/Modules/Academics/Department/Models/Department.php
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
// app/Modules/Academics/Department/Entities/DepartmentState.php
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
// app/Modules/Academics/Department/Data/DepartmentData.php
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
// app/Modules/Academics/Department/Actions/CreateDepartmentAction.php
final class CreateDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department;
    // Validates: name (required, string, max:255, unique:departments,name)
    //            description (nullable, string, max:1000)
    // Transaction: Department::create → dispatch DepartmentCreated → log
}

```

> **Max-length canonical values:** `name` max 255, `description` max 1000 — matching the DB
> schema (`string('name')` = varchar 255, `text('description')`) and `DepartmentForm` rules
> (FR-4HWSB-DM33/34). Earlier draft values (max 100 / max 500) were superseded; see DD-8.

### 6.5 UpdateDepartmentAction

```php
// app/Modules/Academics/Department/Actions/UpdateDepartmentAction.php
final class UpdateDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department, array $data): Department;
    // Validates: name (required, string, max:255, unique:departments,name,{id})
    //            description (nullable, string, max:1000)
    // Transaction: department->update → dispatch DepartmentUpdated → log
}

```

### 6.6 DeleteDepartmentAction

```php
// app/Modules/Academics/Department/Actions/DeleteDepartmentAction.php
final class DeleteDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department): void;
    // Guard: profiles()->exists() → throw RejectedException (exists() preferred over count() >0 for performance, same semantics)
    // Transaction: department->delete → dispatch DepartmentDeleted → log
}

```

### 6.7 DepartmentPolicy

```php
// app/Modules/Academics/Department/Policies/DepartmentPolicy.php
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
// app/Modules/Academics/Department/Livewire/Forms/DepartmentForm.php
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
// app/Modules/Academics/Department/Events/DepartmentCreated.php
final class DepartmentCreated extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.created'; }
}

// app/Modules/Academics/Department/Events/DepartmentUpdated.php
final class DepartmentUpdated extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.updated'; }
}

// app/Modules/Academics/Department/Events/DepartmentDeleted.php
final class DepartmentDeleted extends BaseEvent
{
    public function __construct(public Department $department) {}
    public function eventName(): string { return 'department.deleted'; }
}

```

### 6.10 Listener

```php
// app/Modules/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php
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

### DD-8 — Canonical Max-Length Values for Name & Description

**Decision:** Department `name` is capped at 255 chars and `description` at 1000 chars across all
layers (Actions, `DepartmentForm`, and DB).

**Rationale:** These values match the DB schema (`string('name')` → varchar 255,
`text('description')`) and the Livewire form rules (FR-4HWSB-DM33/34). Earlier draft values in the
Create/Update Action contracts (max 100 / max 500) contradicted the form and the column types;
aligning everything to the largest schema-compatible bound removes the contradiction and avoids
silent truncation where a 255-char name would be rejected by an overly strict Action rule.

**Trade-off:** Longer names are permitted at the input layer. Acceptable — the DB column already
supports them, and 255 chars is well above realistic department-name length.

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion guard | 100% of departments with profiles blocked | `DepartmentState::canBeDeleted()` unit tests |
| Name uniqueness | 0 duplicate departments after bulk import | `CsvRowResult::SKIPPED` for duplicates |
| Event dispatch coverage | Every CRUD → event dispatched | Listener integration tests for all 3 events |
| Force delete always blocked | 0 force deletes possible | `DepartmentPolicy::forceDelete()` returns false |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion blocked feedback | Shows assigned profile count | Flash message includes `{count}` |
| Bulk delete feedback | Separate deleted/blocked counts | Flash messages for both outcomes |
| Template download | Available in one click | `downloadTemplate()` method |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [school-profile.md](81SMS-school-profile.md) | School entity (name, NPSN) — departments belong to this school |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | Settings store for department-related configuration |

### Build Guide
After implementing this spec, the system has academic department CRUD with name, code, and description. Departments are the organizational unit for grouping internship programs. The next step is to build academic year management, which defines the calendar periods that programs run within.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [academic-year-management.md](XW6F5-academic-year-management.md) | Academic years span departments; programs are scoped to year + department |
| 2 | [company-management.md](XI3LB-company-management.md) | Companies offer internships to specific departments |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
