# Institutional & Academics — School Profile, Departments & Academic Years

> **Last updated:** 2026-07-22 **Changes:** feat — cut overlap with settings-infrastructure.md
> and branding-theme-locale.md; focused on school profile, departments, and academic years

## Description

Specification of Internara's institutional and academics subsystem. Defines the school
profile entity (stored as settings, not a standalone model), department management with profile
dependency guards, and academic year lifecycle (create → activate → delete with singleton
activation). Settings infrastructure, branding, theme, and locale are separate initiatives —
see [settings-infrastructure.md](settings-infrastructure.md) and
[branding-theme-locale.md](branding-theme-locale.md).

---

## 1. Problem Statements

### PS-1 — School Profile as Settings, Not a Model

The school profile (name, code, email, address, phone, website, principal name) is stored as
individual `Setting` records under the `school.*` namespace, not as a dedicated database table.
This is because the Settings module already provides caching, validation, and type casting —
duplicating this for a single entity would violate DRY. However, this means the school profile
has no dedicated Model, no migration, and no Eloquent relationships — it must be accessed through
the Settings service.

### PS-2 — Department Deletion with Profile Dependencies

Departments can have student/teacher profiles assigned to them. Deleting a department with
assigned profiles would orphan those profiles, breaking referential integrity. The system must
detect this dependency and prevent deletion, or require explicit reassignment first.

### PS-3 — Academic Year Activation Singleton

Only one academic year can be active at a time. When a new year is activated, the previous active
year must be deactivated. Without a singleton guard, multiple years could be marked active
simultaneously, causing confusion in internship assignments, registration periods, and reporting.

### PS-4 — Academic Year Deletion with Related Records

Academic years are referenced by internships, assessments, and registrations. Deleting a year
with active internships or completed assessments would break historical data integrity. The system
must detect related records and block deletion.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide school profile management via Settings (not a dedicated model) |
| G2  | Enforce department deletion guards (profiles must be reassigned first) |
| G3  | Enforce academic year activation singleton (only one active at a time) |
| G4  | Block academic year deletion when related records exist (internships, assessments) |
| G5  | Support department CSV import/export with template download |
| G6  | Cache school entity and invalidate on update |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-school / multi-tenant support |
| NG2  | School logo auto-generation or cropping |
| NG3  | Academic year archiving or soft-delete |
| NG4  | Department merge or transfer operations |
| NG5  | Settings infrastructure or resolution chain (see [settings-infrastructure.md](settings-infrastructure.md)) |
| NG6  | Branding, theme, or locale management (see [branding-theme-locale.md](branding-theme-locale.md)) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Updates School Profile

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with settings permission
**Flow:**
1. Admin navigates to Settings → School Profile
2. `SchoolEditor` Livewire component loads `SchoolEntity` from Settings store
3. Admin updates: name, institutional code, email, address, phone, website, principal name
4. Admin optionally uploads new school logo
5. `SaveSchoolProfileAction` executes:
   - Maps each field to `SettingEntryData` with `school.{key}` namespace
   - Calls `BatchSetSettingAction` to upsert all entries in a transaction
   - If logo provided, calls `UploadBrandAssetAction`
   - Invalidates `school_entity` cache key
6. Next request sees updated school info everywhere (settings resolution chain)
**Postconditions:** School profile updated, cache invalidated, logo uploaded

### UC-2 — Admin Creates a Department

**Actor:** Admin
**Preconditions:** Admin is authenticated with department management permission
**Flow:**
1. Admin navigates to Academics → Departments
2. `DepartmentManager` shows list of existing departments
3. Admin clicks "Create", fills in name and description
4. `CreateDepartmentAction` validates (name uniqueness, required fields)
5. Creates Department model with `#[Fillable]` attributes
6. Dispatches `DepartmentCreated` event
7. `ClearDashboardCacheOnDepartmentChange` listener invalidates dashboard cache
**Postconditions:** Department created, dashboard cache invalidated

### UC-3 — Admin Attempts to Delete Department with Profiles

**Actor:** Admin
**Preconditions:** Department has assigned profiles (students or teachers)
**Flow:**
1. Admin clicks "Delete" on a department
2. `DepartmentState` entity checks `hasProfiles` (profileCount > 0)
3. `DepartmentPolicy::delete()` returns false when `canBeDeleted()` is false
4. `DeleteUserAction` is not called; flash message shows "Cannot delete department with assigned profiles"
5. Admin must reassign profiles to another department first
**Postconditions:** Deletion blocked, admin informed of dependency

### UC-4 — Admin Activates a New Academic Year

**Actor:** Admin
**Preconditions:** Previous academic year exists and is active
**Flow:**
1. Admin navigates to Academics → Academic Years
2. `AcademicYearManager` shows list with active year highlighted
3. Admin clicks "Activate" on the new year
4. `ActivateAcademicYearAction` executes:
   - Validates: target year must not already be active
   - Deactivates current active year (sets `is_active = false`)
   - Activates target year (sets `is_active = true`)
   - Dispatches `AcademicYearActivated` event
5. `ClearDashboardCacheOnYearChange` listener invalidates dashboard cache
**Postconditions:** Only one year active, dashboard cache refreshed

### UC-5 — Admin Creates First Academic Year

**Actor:** Admin (during setup or after reset)
**Preconditions:** No academic years exist
**Flow:**
1. Admin navigates to Academic Years, sees empty list
2. Clicks "Create", fills in name, start date, end date
3. `CreateAcademicYearAction` validates (dates, uniqueness)
4. Creates AcademicYear model with `is_active = true` (first year auto-activates)
5. Dispatches `AcademicYearCreated` event
**Postconditions:** First academic year created and active

---

## 4. Functional Requirements

### School Profile

| ID   | Requirement |
| ---- | ----------- |
| FR-SP1 | `SchoolEntity` must read from Settings store with 7 keys: school.name, school.institutional_code, school.email, school.address, school.phone, school.website, school.principal_name |
| FR-SP2 | `SchoolEntity` must provide typed accessors for each property |
| FR-SP3 | `SaveSchoolProfileAction` must map data to `SettingEntryData` entries with `school.*` namespace |
| FR-SP4 | `SaveSchoolProfileAction` must call `BatchSetSettingAction` for atomic upsert |
| FR-SP5 | `SaveSchoolProfileAction` must optionally upload logo via `UploadBrandAssetAction` |
| FR-SP6 | `SaveSchoolProfileAction` must invalidate `school_entity` cache key after save |
| FR-SP7 | `SchoolEditor` Livewire component must support logo upload and removal |
| FR-SP8 | `SchoolForm` Form Object must load from `SchoolEntity` and convert to payload |

### Department Management

| ID   | Requirement |
| ---- | ----------- |
| FR-DM1 | `Department` model must use `#[Fillable]` with name and description |
| FR-DM2 | `Department` must have `hasMany` relationship with Profile model |
| FR-DM3 | `DepartmentState` entity must track `profileCount` and `hasProfiles` |
| FR-DM4 | `DepartmentState::canBeDeleted()` must return false when `hasProfiles` is true |
| FR-DM5 | `DepartmentPolicy::delete()` must check `canBeDeleted()` |
| FR-DM6 | `CreateDepartmentAction` must validate name uniqueness |
| FR-DM7 | `UpdateDepartmentAction` must validate name uniqueness (excluding current) |
| FR-DM8 | `DeleteDepartmentAction` must check profile dependency before deleting |
| FR-DM9 | `DepartmentManager` must support CSV import/export with `CsvHandler` |
| FR-DM10 | `DepartmentManager` must display columns: name, description, profile count, actions |
| FR-DM11 | Department CRUD events must dispatch `DepartmentCreated`/`Updated`/`Deleted` |
| FR-DM12 | Department events must trigger dashboard cache invalidation |

### Academic Year Management

| ID   | Requirement |
| ---- | ----------- |
| FR-AY1 | `AcademicYear` model must use `#[Fillable]` with name, start_date, end_date, is_active |
| FR-AY2 | `AcademicYear` must have `hasMany` relationship with Internship and Assessment models |
| FR-AY3 | `AcademicYearState` entity must track `hasRelatedRecords` (internships, assessments) |
| FR-AY4 | `AcademicYearState::canBeActivated()` must validate activation eligibility |
| FR-AY5 | `AcademicYearState::canBeDeleted()` must return false when `hasRelatedRecords` is true |
| FR-AY6 | `ActivateAcademicYearAction` must deactivate current active year before activating new one |
| FR-AY7 | `ActivateAcademicYearAction` must enforce singleton: only one year active at a time |
| FR-AY8 | `CreateAcademicYearAction` must auto-activate the first year created |
| FR-AY9 | `DeleteAcademicYearAction` must block deletion of active year |
| FR-AY10 | `BulkDeleteAcademicYearsAction` must skip active and protected years |
| FR-AY11 | `AcademicYearManager` must show active year with visual indicator |
| FR-AY12 | `AcademicYearManager` must support activate/delete/bulk-delete confirm dialogs |
| FR-AY13 | AcademicYear CRUD events must dispatch `AcademicYearCreated`/`Updated`/`Deleted`/`Activated` |
| FR-AY14 | AcademicYear events must trigger dashboard cache invalidation |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | School entity resolution must complete in < 50ms (cache hit) |
| NFR-P2 | Academic year activation must complete in < 1s (two model updates + events) |
| NFR-S1 | Setting key validation must enforce `^[a-z][a-z0-9_.]*$` pattern |
| NFR-R1 | Department deletion must be atomic with profile dependency check |
| NFR-R2 | Academic year activation must be atomic (deactivate old + activate new) |
| NFR-U1 | Active academic year must be visually indicated in the manager |
| NFR-U2 | Department deletion blocked message must explain why and how to resolve |
| NFR-M1 | School profile must be accessed via Settings service, not a dedicated model |
| NFR-A1 | All institutional and academic management UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Academic year status indicators must include text labels alongside color |
| NFR-A3 | Department deletion blocked messages must be accessible to screen readers |
| NFR-A4 | All form inputs must have associated labels |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Academic year status labels must use `LabelEnum::label()` (calls `__()` internally) |

---

## 6. API / Data Contracts

### 6.1 SchoolEntity

```php
// app/Academics/School/Entities/SchoolEntity.php
final readonly class SchoolEntity
{
    // Reads from Settings store:
    // school.name, school.institutional_code, school.email,
    // school.address, school.phone, school.website, school.principal_name
    // No standalone Model — uses Settings::get() with school.* keys
}
```

### 6.2 SaveSchoolProfileAction

```php
// app/Academics/School/Actions/SaveSchoolProfileAction.php
final class SaveSchoolProfileAction extends BaseCommandAction
{
    public function execute(array $data, ?UploadedFile $logoFile = null): void;
    // Maps data to SettingEntryData('school.{key}', value)
    // Calls BatchSetSettingAction for atomic upsert
    // Optionally uploads logo via UploadBrandAssetAction
    // Invalidates school_entity cache key
}
```

### 6.3 Department Model & State

```php
// app/Academics/Department/Models/Department.php
class Department extends BaseModel
{
    // #[Fillable]: name, description
    // hasMany: Profile
    // Bridge: asDepartmentState() → DepartmentState entity
}

// app/Academics/Department/Entities/DepartmentState.php
final readonly class DepartmentState
{
    public int $profileCount;
    public bool $hasProfiles;
    public bool canBeDeleted(): bool;  // false when hasProfiles
}
```

### 6.4 AcademicYear Model & State

```php
// app/Academics/AcademicYear/Models/AcademicYear.php
class AcademicYear extends BaseModel
{
    // #[Fillable]: name, start_date, end_date, is_active
    // casts: start_date → date, end_date → date, is_active → boolean
    // hasMany: Internship, Assessment
    // Bridge: asAcademicYearState() → AcademicYearState entity
}

// app/Academics/AcademicYear/Entities/AcademicYearState.php
final readonly class AcademicYearState
{
    public bool $isActive;
    public bool $hasRelatedRecords;  // internships + assessments count > 0
    public bool canBeActivated(): bool;
    public bool canBeDeleted(): bool;  // false when hasRelatedRecords or isActive
}
```

### 6.5 AcademicYear DTO

```php
// app/Academics/AcademicYear/Data/AcademicYearData.php
final readonly class AcademicYearData extends BaseData
{
    public function __construct(
        public ?string $id,
        public string $name,
        public string $startDate,
        public string $endDate,
        public bool $isActive,
    ) {}
}
```

### 6.6 Routes

```php
// routes/web/academics.php
Route::prefix('admin')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/school', SchoolEditor::class)->name('academics.school');
    Route::get('/departments', DepartmentManager::class)->name('academics.departments');
    Route::get('/academic-years', AcademicYearManager::class)->name('academics.academic-years');
});
```

---

## 7. Design Decisions

### DD-1 — School Profile via Settings, Not a Dedicated Model

**Decision:** School profile stored as individual `Setting` records under `school.*` namespace,
not a dedicated `School` model/table.
**Rationale:** The Settings module already provides caching, validation, type casting, and
observer-based invalidation. A dedicated School model would duplicate this infrastructure for
7 fields. The `SchoolEntity` class provides typed access without Eloquent overhead.
**Trade-off:** No Eloquent relationships (school → departments). Acceptable because school is a
singleton entity with no relational queries needed.

### DD-2 — Academic Year Activation Singleton

**Decision:** Only one academic year can be active at a time. `ActivateAcademicYearAction`
deactivates the current year before activating the new one.
**Rationale:** Internship assignments, registration periods, and reporting all reference the
"active" academic year. Multiple active years would cause ambiguous queries and incorrect
statistics. The singleton is enforced at the Action level, not the database level (no unique
constraint on `is_active`).
**Trade-off:** Concurrent activation requests could race. Mitigated by transaction wrapping in
the Action.

### DD-3 — Department Deletion Guard via Entity, Not Policy

**Decision:** Department deletion guard uses `DepartmentState::canBeDeleted()` (entity), checked
by `DepartmentPolicy::delete()`.
**Rationale:** The entity encapsulates the business rule (has profiles → cannot delete). The
policy enforces authorization (is admin → can delete). Separating business rules from
authorization makes both independently testable and follow their respective patterns.
**Trade-off:** Extra class (DepartmentState) for a simple boolean check. Mitigated by the entity
being reusable for other business rule queries.

### DD-4 — CSV Import for Departments

**Decision:** Department management includes CSV import/export via `CsvHandler`.
**Rationale:** Schools may have 20-50 departments. Manual entry is tedious. CSV import allows
bulk creation from existing school data (spreadsheets, other systems). Export supports reporting
and migration.
**Trade-off:** CSV import adds complexity (file upload, row parsing, error handling). Mitigated
by reusing the shared `CsvHandler` service.

---

## 8. Success Metrics

### 8.1 School Profile

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Profile load | < 50ms (cache hit) | `SchoolEntity` resolution time |
| Save atomicity | All fields saved or none | `BatchSetSettingAction` transaction |
| Cache invalidation | Immediate on save | `school_entity` cache key forgotten |

### 8.2 Department Management

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion guard | 100% of blocked deletions rejected | `DepartmentState::canBeDeleted()` unit tests |
| CSV import 50 departments | < 15s | CsvHandler chunk processing |
| Event dispatch | Every CRUD → event dispatched | Department events listener coverage |

### 8.3 Academic Year

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Activation singleton | Only one year active at any time | `ActivateAcademicYearAction` transaction |
| Deletion guard | Blocked when related records exist | `AcademicYearState::canBeDeleted()` unit tests |
| First year auto-activate | `is_active = true` on first creation | `CreateAcademicYearAction` logic |

---

## Quick References

- `app/Academics/School/Actions/SaveSchoolProfileAction.php` — school profile save via Settings
- `app/Academics/School/Entities/SchoolEntity.php` — school profile entity (settings-based)
- `app/Academics/School/Livewire/SchoolEditor.php` — school profile editor component
- `app/Academics/School/Livewire/Forms/SchoolForm.php` — school form validation
- `app/Academics/Department/Models/Department.php` — department model
- `app/Academics/Department/Entities/DepartmentState.php` — deletion guard entity
- `app/Academics/Department/Actions/CreateDepartmentAction.php` — department creation
- `app/Academics/Department/Actions/UpdateDepartmentAction.php` — department update
- `app/Academics/Department/Actions/DeleteDepartmentAction.php` — department deletion with guard
- `app/Academics/Department/Livewire/DepartmentManager.php` — department CRUD + CSV import/export
- `app/Academics/Department/Livewire/Forms/DepartmentForm.php` — department form validation
- `app/Academics/Department/Policies/DepartmentPolicy.php` — authorization with deletion guard
- `app/Academics/AcademicYear/Models/AcademicYear.php` — academic year model
- `app/Academics/AcademicYear/Entities/AcademicYearState.php` — activation/deletion guard entity
- `app/Academics/AcademicYear/Data/AcademicYearData.php` — academic year DTO
- `app/Academics/AcademicYear/Actions/CreateAcademicYearAction.php` — year creation (auto-activate first)
- `app/Academics/AcademicYear/Actions/ActivateAcademicYearAction.php` — singleton activation
- `app/Academics/AcademicYear/Actions/UpdateAcademicYearAction.php` — year update
- `app/Academics/AcademicYear/Actions/DeleteAcademicYearAction.php` — year deletion with guard
- `app/Academics/AcademicYear/Actions/BulkDeleteAcademicYearsAction.php` — batch deletion
- `app/Academics/AcademicYear/Livewire/AcademicYearManager.php` — year CRUD + confirm dialogs
- `app/Academics/AcademicYear/Livewire/Forms/AcademicYearForm.php` — year form validation
- `app/Academics/AcademicYear/Policies/AcademicYearPolicy.php` — authorization
- `app/Academics/AcademicYear/Events/` — AcademicYearActivated, Created, Deleted, Updated
- `routes/web/academics.php` — school, departments, academic years routes
- `docs/modules/academics.md` — Academics module overview
- `docs/modules/academics-reference.md` — Academics module technical reference
- **Related specs:** [settings-infrastructure.md](settings-infrastructure.md) — Settings infrastructure & resolution chain
- **Related specs:** [branding-theme-locale.md](branding-theme-locale.md) — Branding, theme & locale management
