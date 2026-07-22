# Internship Groups — Group & Member Management

> **Last updated:** 2026-07-22 **Changes:** feat — expanded DDs, API contracts, NFRs; corrected
> member model to match code (mentor_id), added full action signatures, policy, form validation,
> and member lifecycle edge cases

## Description

Specification of the Internara Program module's group management initiative: internship cohort
groups, role-based member management (students, teachers, supervisors), and group lifecycle.
Program lifecycle, status, and readiness are a separate initiative — see
[internship-lifecycle.md](internship-lifecycle.md).

---

## 1. Problem Statements

### PS-1 — Group Member Management Needs Role-Specific Add Flows

Internship groups contain students, school teachers, and industry supervisors — each with
different identification requirements. Students must be added via their registration ID (linking
them to a specific placement), while teachers and supervisors are added via their user account
(mentor_id). The member add UI must adapt its input fields based on the selected role, and enforce
uniqueness constraints (group+registration, group+mentor) to prevent duplicates.

### PS-2 — Group Deletion Guard Against Orphaned Members

Deleting a group with active members would orphan those member records (and potentially break
registration state). The system must detect existing members and block deletion, requiring the
admin to remove all members first.

### PS-3 — Group-to-Placement Association

Groups represent a cohort at a specific company placement slot. A group can optionally be
associated with a Placement, but this association is not enforced at the group level — capacity
constraints are managed through the Placement layer. The group simply references the placement
for informational purposes.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Support role-based group member management with adapted add flows per role |
| G2  | Block group deletion when members exist |
| G3  | Enforce uniqueness constraints on (group+registration) and (group+mentor) |
| G4  | Provide CRUD for internship groups with placement association |
| G5  | Record member join timestamp on add |
| G6  | Support group deactivation without deletion |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Program lifecycle (see [internship-lifecycle.md](internship-lifecycle.md)) |
| NG2  | Group capacity enforcement — managed through Placement layer |
| NG3  | Student self-service group joining — admin-managed only |
| NG4  | Bulk member import/export (manual add only) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Group

**Actor:** Admin
**Preconditions:** Admin is authenticated; at least one internship exists
**Flow:**
1. Admin navigates to Admin → Internships → Groups (`/admin/internships/groups`)
2. `InternshipGroupManager` shows list of groups with member counts
3. Admin clicks "Create", fills in name, selects internship, optionally selects placement
4. `CreateInternshipGroupAction` validates and creates group
5. Dispatches `internship_group_created` log entry
**Postconditions:** Group created with internship and optional placement association

### UC-2 — Admin Manages Group Members

**Actor:** Admin
**Preconditions:** Internship group exists; admin is authenticated
**Flow:**
1. Admin clicks "Manage Members"; member management modal opens
2. Admin selects role to add:
   - **Student:** enters registration ID → `AddMemberToGroupAction` links registration + student
   - **Teacher/Supervisor:** enters mentor (user) ID → `AddMemberToGroupAction` creates member with mentor reference
3. System validates: role-specific required fields, uniqueness constraints
4. `AddMemberToGroupAction` creates member with `joined_at = now()`
5. Admin can remove members → `RemoveMemberFromGroupAction` deletes member record
6. Admin clicks "Delete Group" → `DeleteInternshipGroupAction` checks `canBeDeleted()` (blocks if has members)
**Postconditions:** Members added/removed; deletion guarded by member count

### UC-3 — Admin Deactivates a Group

**Actor:** Admin
**Preconditions:** Group exists, currently active
**Flow:**
1. Admin edits group, sets `is_active = false`
2. `UpdateInternshipGroupAction` updates the group
3. Group remains in the system but is marked inactive
**Postconditions:** Group deactivated, members still visible but group excluded from active views

---

## 4. Functional Requirements

### Group Management

| ID   | Requirement |
| ---- | ----------- |
| FR-GM1 | `InternshipGroup` model must use `#[Fillable]` with: name, internship_id, placement_id, description, is_active |
| FR-GM2 | `InternshipGroup` must have `belongsTo` Internship (FK cascadeOnDelete), `belongsTo` Placement (nullable), `hasMany` InternshipGroupMember |
| FR-GM3 | `InternshipGroup` must provide `asInternshipGroupState()` bridge to `InternshipGroupState` entity |
| FR-GM4 | `InternshipGroupState` must track memberCount (via `members()->count()`) and isActive |
| FR-GM5 | `InternshipGroupState::canBeDeleted()` must return false when `hasMembers()` is true |
| FR-GM6 | `InternshipGroupState::isActive()` must return the `is_active` boolean |
| FR-GM7 | `CreateInternshipGroupAction` must create group with internship_id, name, and optional placement_id/description |
| FR-GM8 | `UpdateInternshipGroupAction` must accept group instance and data array, update within transaction |
| FR-GM9 | `DeleteInternshipGroupAction` must check `canBeDeleted()` before deleting; throw `RejectedException` if not deletable |
| FR-GM10 | `InternshipGroupManager` must display groups with columns: name, internship title, member count, actions |
| FR-GM11 | `InternshipGroupManager` must extend `BaseRecordManager` with search (name), query with `withCount('members')` |
| FR-GM12 | `InternshipGroupData` DTO must require: internshipId, name; accept optional: placementId, isActive |

### Member Management

| ID   | Requirement |
| ---- | ----------- |
| FR-MM1 | `InternshipGroupMember` model must use `#[Fillable]` with: internship_group_id, registration_id, user_id (mentor), role, joined_at |
| FR-MM2 | `InternshipGroupMember` must have `belongsTo` InternshipGroup, `belongsTo` Registration, `belongsTo` User (mentor) |
| FR-MM3 | `InternshipGroupRole` enum must define 3 cases: STUDENT, SCHOOL_TEACHER, INDUSTRY_SUPERVISOR — all implement `LabelEnum` |
| FR-MM4 | `AddMemberToGroupAction` must accept group + data array (role, registration_id, mentor_id); create member with `joined_at = now()` |
| FR-MM5 | `AddMemberToGroupAction` must execute within a transaction |
| FR-MM6 | `RemoveMemberFromGroupAction` must accept member instance, log removal, then delete within transaction |
| FR-MM7 | `InternshipGroupManager::addMember()` must validate: role (required, valid enum), registration_id (required_if:role=student, exists:registrations,id), mentor_id (required_if:role=school_teacher OR industry_supervisor, exists:users,id) |
| FR-MM8 | `InternshipGroupManager::removeMember()` must authorize update on the parent group |

### Policies

| ID   | Requirement |
| ---- | ----------- |
| FR-P1 | `InternshipGroupPolicy` must extend `BasePolicy` |
| FR-P2 | `viewAny` and `view` must allow all users (public read access) |
| FR-P3 | `create`, `update`, `delete` must require admin role |

### Livewire Components & Routing

| ID   | Requirement |
| ---- | ----------- |
| FR-L1 | `InternshipGroupManager` must be at `/admin/internships/groups` with admin middleware |
| FR-L2 | `InternshipGroupManager` must extend `BaseRecordManager` |
| FR-L3 | `InternshipGroupManager` must provide: showModal (group CRUD), showMemberModal (member add), showConfirm (delete confirm) |
| FR-L4 | `InternshipGroupManager` must provide computed `internships()` and `roleOptions()` |
| FR-L5 | `InternshipGroupForm` must validate group fields before save |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | Group member uniqueness constraints must be enforced at both database and application level |
| NFR-S2 | Member add must authorize update permission on the parent group |
| NFR-U1 | Group member modal must dynamically adapt input fields based on selected role (student → registration_id; teacher/supervisor → mentor_id) |
| NFR-U2 | Deletion blocked messages must explain which related records prevent deletion |
| NFR-U3 | Member count must update in real-time after add/remove |
| NFR-U4 | Confirm dialog must show group name before deletion |
| NFR-A1 | Group member modal inputs must have associated labels |
| NFR-A2 | All interactive elements must have visible focus indicators |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Role labels must use `LabelEnum::label()` (calls `__()` internally) |

---

## 6. API / Data Contracts

### 6.1 InternshipGroupRole Enum

```php
// app/Program/InternshipGroup/Enums/InternshipGroupRole.php
enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string;
    // STUDENT → __('Student')
    // SCHOOL_TEACHER → __('School Teacher')
    // INDUSTRY_SUPERVISOR → __('Industry Supervisor')
}
```

### 6.2 InternshipGroup Model

```php
// app/Program/InternshipGroup/Models/InternshipGroup.php
#[Fillable(['name', 'internship_id', 'placement_id', 'description', 'is_active'])]
class InternshipGroup extends BaseModel
{
    // Casts: is_active → boolean
    // Relations: belongsTo Internship (cascadeOnDelete), belongsTo Placement (nullable),
    //            hasMany InternshipGroupMember
    // Bridge: asInternshipGroupState() → InternshipGroupState
    // Factory: InternshipGroupFactory
}
```

### 6.3 InternshipGroupMember Model

```php
// app/Program/InternshipGroup/Models/InternshipGroupMember.php
#[Fillable(['internship_group_id', 'registration_id', 'user_id', 'role', 'joined_at'])]
class InternshipGroupMember extends BaseModel
{
    // Casts: joined_at → datetime
    // Relations: belongsTo InternshipGroup (internship_group_id),
    //            belongsTo Registration,
    //            belongsTo User (user_id — mentor)
    // Factory: InternshipGroupMemberFactory
}
```

### 6.4 InternshipGroupState Entity

```php
// app/Program/InternshipGroup/Entities/InternshipGroupState.php
final readonly class InternshipGroupState extends BaseEntity
{
    public function __construct(private int $memberCount, private bool $isActive) {}

    public static function fromModel(Model $model): static;
    // Reads members relation (loaded or counts), reads is_active

    public function isActive(): bool;
    public function hasMembers(): bool;       // memberCount > 0
    public function canBeDeleted(): bool;     // !hasMembers()
}
```

### 6.5 InternshipGroupData DTO

```php
// app/Program/InternshipGroup/Data/InternshipGroupData.php
final readonly class InternshipGroupData extends BaseData
{
    public function __construct(
        public string $internshipId,
        public string $name,
        public ?string $placementId = null,
        public ?bool $isActive = null,
    ) {}
}
```

### 6.6 Action Signatures

```php
// app/Program/InternshipGroup/Actions/CreateInternshipGroupAction.php
final class CreateInternshipGroupAction extends BaseCommandAction
{
    public function execute(array $data): InternshipGroup;
    // Creates group within transaction, logs creation
}

// app/Program/InternshipGroup/Actions/UpdateInternshipGroupAction.php
final class UpdateInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroup;
    // Updates group within transaction, logs update
}

// app/Program/InternshipGroup/Actions/DeleteInternshipGroupAction.php
final class DeleteInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group): void;
    // Checks canBeDeleted(), throws RejectedException if not
    // Deletes within transaction, logs deletion
}

// app/Program/InternshipGroup/Actions/AddMemberToGroupAction.php
final class AddMemberToGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroupMember;
    // Creates member with role, registration_id/mentor_id, joined_at = now()
    // Executes within transaction, logs addition
}

// app/Program/InternshipGroup/Actions/RemoveMemberFromGroupAction.php
final class RemoveMemberFromGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroupMember $member): void;
    // Logs removal, deletes member within transaction
}
```

### 6.7 Policy

```php
// app/Program/InternshipGroup/Policies/InternshipGroupPolicy.php
class InternshipGroupPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool;  // all users
    public function view(?User $user, InternshipGroup $group): bool;  // all users
    public function create(User $user): bool;    // admin only
    public function update(User $user, InternshipGroup $group): bool; // admin only
    public function delete(User $user, InternshipGroup $group): bool; // admin only
}
```

### 6.8 Routes

```php
// routes/web/program.php
Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
    });
```

---

## 7. Design Decisions

### DD-1 — Group Capacity via Placement Layer

**Decision:** Group member capacity is not enforced at the `InternshipGroup` level. Capacity
constraints are managed through the Placement layer (company slot quotas).
**Rationale:** The `InternshipGroup` represents a cohort at a company slot, and the slot's
capacity is defined in the Partners/Enrollment module. Enforcing capacity at the group level
would duplicate the quota logic.
**Trade-off:** Groups can technically exceed capacity if `AddMemberToGroupAction` doesn't check
the placement quota. Mitigated by the Enrollment module enforcing placement-level constraints.

### DD-2 — Role-Specific Member Add Flow

**Decision:** `AddMemberToGroupAction` accepts a data array with role parameter and requires
different fields depending on the role (student needs registration_id, others need mentor_id).
**Rationale:** Students are identified by their registration (which links to a specific
placement), while teachers and supervisors are identified by their existing user account.
A single unified action with role-based branching keeps the API surface small while adapting
to the real-world identification needs.
**Trade-off:** Action has conditional logic. Mitigated by clear separation: student path vs
teacher/supervisor path, and validation at the Livewire level before calling the action.

### DD-3 — Member Record vs Direct Relationship

**Decision:** Group membership is stored in a dedicated `InternshipGroupMember` pivot model
(with its own UUID PK, timestamps, and role field), not a simple many-to-many pivot.
**Rationale:** Members have a role attribute and a `joined_at` timestamp. A simple pivot
would require a separate lookup to determine the member's role. The dedicated model also
allows future extension (e.g., member status, notes, evaluation links) without schema changes.
**Trade-off:** Extra table and model for what could be a pivot. Mitigated by the member model
being lightweight and the clear business need for role tracking.

### DD-4 — Public Read Access for Groups

**Decision:** `InternshipGroupPolicy` allows `viewAny` and `view` for all users, regardless
of role.
**Rationale:** Group information (names, internship assignments) is not sensitive. Students,
teachers, and supervisors need to see group compositions for coordination. Restricting read
access would create unnecessary friction.
**Trade-off:** No privacy for group compositions. Acceptable because groups are
school-internal organizational units, not private data.

### DD-5 — Soft Deactivation vs Hard Deletion

**Decision:** Groups support `is_active` flag for soft deactivation, but deletion is a hard
delete (with guard).
**Rationale:** Deactivating a group preserves historical data (members, logs) while removing
the group from active views. Hard deletion is only allowed when no members exist, ensuring
no orphaned records.
**Trade-off:** Inactive groups still occupy database space. Mitigated by eventual archival
process (future scope).

---

## 8. Success Metrics

### 8.1 Group Management

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion guard | 100% blocked when members exist | `InternshipGroupState::canBeDeleted()` tests |
| Uniqueness enforcement | 0 duplicate pairs | Database constraint + `AddMemberToGroupAction` tests |
| Role-based add | Correct field per role | `AddMemberToGroupAction` unit tests per role |

### 8.2 Member Lifecycle

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Join timestamp | 100% of members have joined_at | `AddMemberToGroupAction` sets `now()` |
| Removal logging | Every removal logged | `RemoveMemberFromGroupAction` audit trail |
| Authorization | Only admins can add/remove members | Policy + Livewire authorization tests |

---

## Quick References

- `app/Program/InternshipGroup/Models/InternshipGroup.php` — Group model with placement FK
- `app/Program/InternshipGroup/Models/InternshipGroupMember.php` — Member model with role, mentor_id
- `app/Program/InternshipGroup/Enums/InternshipGroupRole.php` — 3-role enum
- `app/Program/InternshipGroup/Entities/InternshipGroupState.php` — Deletion guard entity
- `app/Program/InternshipGroup/Data/InternshipGroupData.php` — DTO
- `app/Program/InternshipGroup/Actions/CreateInternshipGroupAction.php` — Group creation
- `app/Program/InternshipGroup/Actions/UpdateInternshipGroupAction.php` — Group update
- `app/Program/InternshipGroup/Actions/DeleteInternshipGroupAction.php` — Deletion with guard
- `app/Program/InternshipGroup/Actions/AddMemberToGroupAction.php` — Role-based member add
- `app/Program/InternshipGroup/Actions/RemoveMemberFromGroupAction.php` — Member removal
- `app/Program/InternshipGroup/Policies/InternshipGroupPolicy.php` — Authorization
- `app/Program/InternshipGroup/Livewire/InternshipGroupManager.php` — CRUD + member modal
- `app/Program/InternshipGroup/Livewire/Forms/InternshipGroupForm.php` — Form validation
- `routes/web/program.php` — Route definitions
- `docs/modules/program.md` — Program module overview
- **Related specs:** [internship-lifecycle.md](internship-lifecycle.md) — Program lifecycle & readiness
