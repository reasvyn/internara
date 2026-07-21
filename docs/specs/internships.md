# Internships — Program Lifecycle, Groups & Pre-Close Readiness

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering internship CRUD, status
> lifecycle with state machine, registration window management, pre-close readiness checks,
> group member management, and CSV import/export

## Description

Complete specification of Internara's Program module covering internship program definition,
status lifecycle, registration window governance, pre-close readiness validation, cohort group
management, and bulk operations. This module is the structural backbone that connects academic
years to enrollment, daily operations, assessment, and certification.

---

## 1. Problem Statements

### PS-1 — Internship Status Lifecycle Without Guardrails

Internships progress through five states (DRAFT → PUBLISHED → ACTIVE → COMPLETED/CANCELLED), but
without enforced transition rules, an admin could mark an ACTIVE internship as DRAFT or jump from
DRAFT directly to COMPLETED. Invalid transitions would corrupt downstream data — registrations
scoped to the internship, attendance records, grade calculations — and produce inconsistent reports.
A strict state machine at the Action level is required to prevent illegal transitions.

### PS-2 — Registration Windows Require Date and Status Coordination

Students can only register during a specific window defined by `registration_start_date` and
`registration_end_date`. However, date range alone is insufficient — the internship must also be
in a status that accepts registrations (PUBLISHED or ACTIVE). Without coordinating both status
and date, registrations could be accepted during DRAFT internships or after the window closes.
The `InternshipPeriod` entity must encapsulate this compound check so every consumer gets it
right.

### PS-3 — Premature Internship Closure Produces Incomplete Data

Closing an internship before all enrolled students have finalized assessments, verified
attendance, verified supervision logs, graded submissions, and issued certificates would produce
incomplete grade cards and broken certification chains. The system must run a comprehensive
readiness check covering five domains — assessments, submissions, supervision logs, attendance,
and certificates — and present a detailed report before allowing closure.

### PS-4 — Internship Deletion Must Protect Referential Integrity

Internships are referenced by placements, registrations, and downstream records (attendance,
logbooks, assessments, certificates). Deleting an internship with active placements or
registrations would orphan these records and break foreign key relationships. The system must
detect related records and block deletion with a clear message explaining what must be resolved
first.

### PS-5 — Group Member Management Needs Role-Specific Add Flows

Internship groups contain students, school teachers, and industry supervisors — each with
different identification requirements. Students must be added via their registration ID (linking
them to a specific placement), while teachers and supervisors are added via user ID. The member
add UI must adapt its input fields based on the selected role, and enforce uniqueness constraints
(group+registration, group+user) to prevent duplicates.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Enforce a strict status state machine with validated transitions at the Action level |
| G2  | Govern registration windows through the `InternshipPeriod` entity combining status and date checks |
| G3  | Provide pre-close readiness validation across five domains (assessments, submissions, supervision logs, attendance, certificates) |
| G4  | Block internship and group deletion when related records exist |
| G5  | Support role-based group member management with adapted add flows per role |
| G6  | Provide CSV import/export for internship programs via the `InternshipManager` Livewire component |
| G7  | Support batch status updates (including batch close) across filtered internships |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Separate timeline/phases submodule — phases are stored as JSON on the Internship model |
| NG2  | Automated status transitions (e.g., auto-activate on start date) — transitions are manual/admin-driven |
| NG3  | Multi-tenant internship isolation — the system is single-tenant |
| NG4  | Student self-registration — registration is managed by admin/school |
| NG5  | Real-time notifications for status changes — events are fire-and-forget for cache/listener invalidation |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates and Publishes an Internship

**Actor:** Admin
**Preconditions:** At least one academic year exists; admin is authenticated with internship permission
**Flow:**
1. Admin navigates to Admin → Internships (`/admin/internships`)
2. `InternshipManager` shows list of existing internships with status, date range, academic year
3. Admin clicks "Create"; `InternshipForm` loads with active academic year auto-selected
4. Admin fills in: name (required), start date, end date, description (optional)
5. `CreateInternshipAction` executes:
   - Validates dates fall within the associated academic year (via `InternshipPeriod::datesSpanOutsideAcademicYear`)
   - Auto-fills active academic year if not explicitly provided
   - Creates Internship model with status DRAFT
   - Dispatches `InternshipCreated` event → `NotifyAdminsInternshipCreated` listener
6. Admin clicks "Publish" on the DRAFT internship
7. `UpdateInternshipAction` validates transition DRAFT → PUBLISHED is legal
8. Status updated to PUBLISHED; internship is now visible to teachers and supervisors
**Postconditions:** Internship created as DRAFT, published successfully, admins notified

### UC-2 — Student Registers During Open Window

**Actor:** Student (via Enrollment module, validated by Program)
**Preconditions:** Internship is in PUBLISHED or ACTIVE status; registration window dates are set; current date is within window
**Flow:**
1. Student initiates registration (via Enrollment module)
2. System resolves `InternshipPeriod` from Internship model (via `asInternshipPeriod()`)
3. `InternshipPeriod::isAcceptingRegistrations()` checks:
   - Status is PUBLISHED or ACTIVE (`InternshipStatus::isAcceptingRegistrations()`)
   - Current date is after or on `registration_start_date` (if set)
   - Current date is before or on `registration_end_date` (if set)
4. If all checks pass, registration proceeds
5. If any check fails, `OpenForRegistration` validation rule rejects with descriptive message
**Postconditions:** Registration accepted or rejected based on status + date window

### UC-3 — Admin Runs Pre-Close Readiness Check

**Actor:** Admin
**Preconditions:** Internship has active registrations; admin is authenticated
**Flow:**
1. Admin clicks "Check Readiness" or "Close" on an ACTIVE internship
2. `ReadCloseReadinessAction` executes against the internship:
   - **Assessments:** queries `Assessment` for all active registrations; checks `finalized_at IS NULL`
   - **Submissions:** queries `Submission`; checks for DRAFT, SUBMITTED, or REVISION_REQUIRED status
   - **Supervision logs:** queries `SupervisionLog`; checks `is_verified = false`
   - **Attendance:** queries `Attendance`; checks `is_verified = false`
   - **Certificates:** queries `Certificate`; checks status ≠ ISSUED and total count > 0
3. Returns structured array with per-domain `passed`, `total`, `pending`, `message`
4. UI displays readiness dashboard with pass/fail indicators per domain
5. If all five domains pass, admin can proceed with batch close
**Postconditions:** Readiness report displayed; admin informed of blockers

### UC-4 — Admin Manages Group Members

**Actor:** Admin
**Preconditions:** Internship group exists; admin is authenticated
**Flow:**
1. Admin navigates to Admin → Internships → Groups (`/admin/internships/groups`)
2. `InternshipGroupManager` shows list of groups with member counts
3. Admin clicks "Manage Members" on a group; member management modal opens
4. Admin selects role to add:
   - **Student:** enters registration ID → `AddMemberToGroupAction` links registration + user via `InternshipGroupMember`
   - **Teacher/Supervisor:** enters user ID → `AddMemberToGroupAction` creates member with user reference
5. System enforces uniqueness: unique(group_id, registration_id) and unique(group_id, user_id)
6. Admin can remove members → `RemoveMemberFromGroupAction` deletes the member record
7. Admin clicks "Delete Group" → `DeleteInternshipGroupAction` checks `InternshipGroupState::canBeDeleted()` (blocks if has members)
**Postconditions:** Members added/removed; deletion guarded by member count

### UC-5 — Admin Batch Closes Internships

**Actor:** Admin
**Preconditions:** Multiple internships are ACTIVE; admin has run readiness checks
**Flow:**
1. Admin filters internships in `InternshipManager` by status=ACTIVE
2. Admin selects multiple internships and clicks "Batch Close"
3. System runs `ReadCloseReadinessAction` for each selected internship
4. If all readiness checks pass for all selected, `BatchUpdateInternshipStatusAction` executes:
   - Accepts a filtered `Builder` query and target status COMPLETED
   - Updates all matching records in a single transaction
   - Dispatches `InternshipStatusBatchUpdated` event (fire-and-forget)
5. If any readiness check fails, admin sees per-internship breakdown of blockers
**Postconditions:** Selected internships marked COMPLETED; event dispatched

---

## 4. Functional Requirements

### Internship CRUD

| ID   | Requirement |
| ---- | ----------- |
| FR-IC1 | `Internship` model must use `#[Fillable]` with: academic_year_id, name, start_date, end_date, description, status, phases, required_document_ids, grading_weights |
| FR-IC2 | `Internship` must cast status to `InternshipStatus` enum, start_date/end_date to date, phases/required_document_ids/grading_weights to json |
| FR-IC3 | `Internship` must have `belongsTo` AcademicYear (FK nullable, set null on delete), `hasMany` Placements, `hasMany` Registrations |
| FR-IC4 | `Internship` must provide `asInternshipPeriod()` bridge returning `InternshipPeriod` entity |
| FR-IC5 | `Internship` must provide `asInternshipState()` bridge returning `InternshipState` entity |
| FR-IC6 | `InternshipData` DTO must require: name, academicYearId, startDate, endDate; accept optional: description, status, registrationStartDate, registrationEndDate |
| FR-IC7 | `CreateInternshipAction` must auto-fill active academic year when not explicitly provided |
| FR-IC8 | `CreateInternshipAction` must create internship with DRAFT status |
| FR-IC9 | `UpdateInternshipAction` must validate status transition via `InternshipStatus::canTransitionTo()` |
| FR-IC10 | `UpdateInternshipAction` must reject illegal transitions with `RejectedException` |
| FR-IC11 | `DeleteInternshipAction` must check `InternshipState::canBeDeleted()` before deleting |
| FR-IC12 | `DeleteInternshipAction` must block deletion when placementCount > 0 or registrationCount > 0 |
| FR-IC13 | `InternshipManager` must display: name, academic year, date range, status, action buttons |
| FR-IC14 | `InternshipManager` must support search by name |
| FR-IC15 | `InternshipManager` must support filter by: status, academic_year_id, date_from, date_to |
| FR-IC16 | `InternshipPolicy` must grant view/viewAny to all 5 roles (super_admin, admin, teacher, supervisor, student) |
| FR-IC17 | `InternshipPolicy` must grant create/update/delete to admin roles only |
| FR-IC18 | `InternshipPolicy::delete()` must check both placements and registrations existence |

### Status Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-SL1 | `InternshipStatus` enum must define 5 cases: DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED |
| FR-SL2 | Valid transitions: DRAFT → [PUBLISHED, CANCELLED], PUBLISHED → [ACTIVE, CANCELLED], ACTIVE → [COMPLETED, CANCELLED] |
| FR-SL3 | COMPLETED and CANCELLED must be terminal (no valid transitions out) |
| FR-SL4 | `isAcceptingRegistrations()` must return true for PUBLISHED and ACTIVE only |
| FR-SL5 | `isTerminal()` must return true for COMPLETED and CANCELLED only |
| FR-SL6 | `validTransitions()` must return the allowed target statuses for each source status |
| FR-SL7 | `canTransitionTo()` must validate against `validTransitions()` |
| FR-SL8 | `UpdateInternshipAction` must enforce the state machine for single-record status changes |
| FR-SL9 | `BatchUpdateInternshipStatusAction` must apply the target status to all matching records without per-record transition validation |
| FR-SL10 | `InternshipCreated` event must be dispatched after creation, notifying admins |
| FR-SL11 | `InternshipStatusBatchUpdated` event must be dispatched after batch update (fire-and-forget) |

### Registration Windows

| ID   | Requirement |
| ---- | ----------- |
| FR-RW1 | `InternshipPeriod` entity must encapsulate: status, registrationStartDate, registrationEndDate, academicYearStart, academicYearEnd |
| FR-RW2 | `InternshipPeriod::isAcceptingRegistrations()` must check status `isAcceptingRegistrations()` AND date window |
| FR-RW3 | `InternshipPeriod::isRegistrationWindowOpen()` must check only date range (ignore status) |
| FR-RW4 | `InternshipPeriod::isBeforeRegistrationWindow()` must return true when current date is before registrationStartDate |
| FR-RW5 | `InternshipPeriod::isAfterRegistrationWindow()` must return true when current date is after registrationEndDate |
| FR-RW6 | `InternshipPeriod::isWithinAcademicYear()` must verify date falls within academic year bounds |
| FR-RW7 | `InternshipPeriod::datesSpanOutsideAcademicYear()` must detect internship dates exceeding academic year |
| FR-RW8 | `OpenForRegistration` validation rule must use `InternshipPeriod` to gate registration attempts |
| FR-RW9 | `InternshipForm` must include registrationStartDate and registrationEndDate fields |

### Pre-Close Readiness

| ID   | Requirement |
| ---- | ----------- |
| FR-PC1 | `ReadCloseReadinessAction` must accept an `Internship` and return an array of 5 readiness domains |
| FR-PC2 | Assessments check: all active registrations must have `finalized_at` set (no pending) |
| FR-PC3 | Submissions check: no submissions in DRAFT, SUBMITTED, or REVISION_REQUIRED status |
| FR-PC4 | Supervision logs check: all logs for active registrations must have `is_verified = true` |
| FR-PC5 | Attendance check: all attendance records for active registrations must have `is_verified = true` |
| FR-PC6 | Certificates check: all certificates must be ISSUED status, and at least one certificate must exist |
| FR-PC7 | Each domain must return: `passed` (bool), `total` (int), `pending` (int), `message` (string) |
| FR-PC8 | `InternshipManager` must display readiness check results with pass/fail indicators |

### Group Management

| ID   | Requirement |
| ---- | ----------- |
| FR-GM1 | `InternshipGroup` model must use `#[Fillable]` with: name, internship_id, placement_id, description, is_active |
| FR-GM2 | `InternshipGroup` must have `belongsTo` Internship (FK cascade on delete), `belongsTo` Placement (FK nullable), `hasMany` InternshipGroupMember |
| FR-GM3 | `InternshipGroupMember` must use `#[Fillable]` with: internship_group_id, registration_id, user_id, role, joined_at |
| FR-GM4 | `InternshipGroupMember` must enforce unique constraint on (group_id, registration_id) and (group_id, user_id) |
| FR-GM5 | `InternshipGroupRole` enum must define 3 cases: STUDENT, SCHOOL_TEACHER, INDUSTRY_SUPERVISOR |
| FR-GM6 | `InternshipGroupState` must track memberCount and isActive |
| FR-GM7 | `InternshipGroupState::canBeDeleted()` must return false when `hasMembers()` is true |
| FR-GM8 | `CreateInternshipGroupAction` must create group with provided internship_id, name, and optional placement_id |
| FR-GM9 | `DeleteInternshipGroupAction` must check `InternshipGroupState::canBeDeleted()` before deleting |
| FR-GM10 | `AddMemberToGroupAction` must handle student role (requires registration_id) and teacher/supervisor role (requires user_id) |
| FR-GM11 | `AddMemberToGroupAction` must enforce uniqueness constraints on (group+registration) and (group+user) |
| FR-GM12 | `RemoveMemberFromGroupAction` must delete the member record by ID |
| FR-GM13 | `InternshipGroupManager` must display groups with name, internship reference, member count, active status |
| FR-GM14 | `InternshipGroupManager` must provide a member management modal with role-based add form |
| FR-GM15 | `InternshipGroupData` DTO must require: internshipId, name; accept optional: placementId, isActive |
| FR-GM16 | `InternshipGroupPolicy` must grant view/viewAny to all users; create/update/delete to admin only |

### CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-CE1 | `InternshipManager` must support CSV import with columns: name, description |
| FR-CE2 | CSV import must create DRAFT internships with active academic year start/end dates as defaults |
| FR-CE3 | `InternshipManager` must support CSV export of filtered internships |
| FR-CE4 | CSV export must include: name, description, status, start_date, end_date, academic_year |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | Status transition validation must reject illegal transitions with `RejectedException`, not `RuntimeException` |
| NFR-S2 | `InternshipPolicy` must enforce authorization at every CRUD operation — no unprotected routes |
| NFR-S3 | Group member uniqueness constraints must be enforced at both database and application level |
| NFR-P1 | `ReadCloseReadinessAction` must complete within 2s for internships with up to 200 active registrations |
| NFR-P2 | `BatchUpdateInternshipStatusAction` must process up to 50 internships in a single transaction under 3s |
| NFR-P3 | `InternshipPeriod` entity instantiation must complete in < 5ms (read-only, no queries) |
| NFR-R1 | `CreateInternshipAction` must wrap creation in a database transaction |
| NFR-R2 | `BatchUpdateInternshipStatusAction` must wrap batch update in a database transaction |
| NFR-R3 | `DeleteInternshipAction` must verify related records within the same transaction as deletion |
| NFR-U1 | `InternshipManager` must show status badges with distinct colors per status |
| NFR-U2 | Pre-close readiness UI must display per-domain pass/fail with actionable pending counts |
| NFR-U3 | Group member modal must dynamically adapt input fields based on selected role |
| NFR-U4 | Deletion blocked messages must explain which related records prevent deletion |
| NFR-M1 | All Program module classes must use `declare(strict_types=1)` |
| NFR-M2 | All Internship/Group/Member models must use `#[Fillable]` attribute (no `$fillable` property) |

---

## 6. API / Data Contracts

### 6.1 InternshipStatus Enum

```php
// app/Program/Internship/Enums/InternshipStatus.php
enum InternshipStatus: string implements LabelEnum, StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isAcceptingRegistrations(): bool;  // PUBLISHED, ACTIVE
    public function isTerminal(): bool;                 // COMPLETED, CANCELLED
    public function validTransitions(): array;          // allowed targets per source
    public function canTransitionTo(StatusEnum $target): bool;
    public function label(): string;                    // localized label via __()
}
```

### 6.2 InternshipGroupRole Enum

```php
// app/Program/InternshipGroup/Enums/InternshipGroupRole.php
enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string;  // localized via __()
}
```

### 6.3 Internship Model

```php
// app/Program/Internship/Models/Internship.php
class Internship extends BaseModel
{
    // #[Fillable]: academic_year_id, name, start_date, end_date, description,
    //              status, phases, required_document_ids, grading_weights
    // Casts: start_date → date, end_date → date, status → InternshipStatus,
    //        phases → json, required_document_ids → json, grading_weights → json
    // Relations: belongsTo AcademicYear, hasMany Placements, hasMany Registrations
    // Bridges: asInternshipPeriod() → InternshipPeriod, asInternshipState() → InternshipState
}
```

### 6.4 InternshipState Entity

```php
// app/Program/Internship/Entities/InternshipState.php
final readonly class InternshipState extends BaseEntity
{
    public int $placementCount;
    public int $registrationCount;

    public static function fromModel(Model $model): static;
    public function canBeDeleted(): bool;  // placementCount === 0 && registrationCount === 0
}
```

### 6.5 InternshipPeriod Entity

```php
// app/Program/Internship/Entities/InternshipPeriod.php
final readonly class InternshipPeriod extends BaseEntity
{
    public ?InternshipStatus $status;
    public ?Carbon $registrationStartDate;
    public ?Carbon $registrationEndDate;
    public ?Carbon $academicYearStart;
    public ?Carbon $academicYearEnd;

    public static function fromModel(Model $model): static;
    public function isAcceptingRegistrations(?Carbon $now = null): bool;
    public function isRegistrationWindowOpen(?Carbon $now = null): bool;
    public function isBeforeRegistrationWindow(?Carbon $now = null): bool;
    public function isAfterRegistrationWindow(?Carbon $now = null): bool;
    public function hasAcademicYear(): bool;
    public function isWithinAcademicYear(?Carbon $date = null): bool;
    public function datesSpanOutsideAcademicYear(?Carbon $start = null, ?Carbon $end = null): bool;
}
```

### 6.6 InternshipData DTO

```php
// app/Program/Internship/Data/InternshipData.php
final readonly class InternshipData extends BaseData
{
    public function __construct(
        public string $name,
        public string $academicYearId,
        public string $startDate,
        public string $endDate,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $registrationStartDate = null,
        public ?string $registrationEndDate = null,
    ) {}
}
```

### 6.7 InternshipGroup Model

```php
// app/Program/InternshipGroup/Models/InternshipGroup.php
class InternshipGroup extends BaseModel
{
    // #[Fillable]: name, internship_id, placement_id, description, is_active
    // Casts: is_active → boolean
    // Relations: belongsTo Internship (cascadeOnDelete), belongsTo Placement (nullable),
    //            hasMany InternshipGroupMember
    // Bridge: asInternshipGroupState() → InternshipGroupState
}
```

### 6.8 InternshipGroupMember Model

```php
// app/Program/InternshipGroup/Models/InternshipGroupMember.php
class InternshipGroupMember extends BaseModel
{
    // #[Fillable]: internship_group_id, registration_id, user_id, role, joined_at
    // Casts: joined_at → datetime
    // Unique constraints: (internship_group_id, registration_id), (internship_group_id, user_id)
    // Relations: belongsTo InternshipGroup, belongsTo Registration, belongsTo User
}
```

### 6.9 InternshipGroupState Entity

```php
// app/Program/InternshipGroup/Entities/InternshipGroupState.php
final readonly class InternshipGroupState extends BaseEntity
{
    public int $memberCount;
    public bool $isActive;

    public static function fromModel(Model $model): static;
    public function isActive(): bool;
    public function hasMembers(): bool;
    public function canBeDeleted(): bool;  // !hasMembers()
}
```

### 6.10 InternshipGroupData DTO

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

### 6.11 Actions

```php
// app/Program/Internship/Actions/CreateInternshipAction.php
final class CreateInternshipAction extends BaseCommandAction
{
    public function execute(InternshipData $data): Internship;
    // Auto-fills active academic year if not provided
}

// app/Program/Internship/Actions/UpdateInternshipAction.php
final class UpdateInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship, InternshipData $data): Internship;
    // Enforces status state machine
}

// app/Program/Internship/Actions/DeleteInternshipAction.php
final class DeleteInternshipAction extends BaseCommandAction
{
    public function execute(Internship $internship): void;
    // Blocks if placements/registrations exist
}

// app/Program/Internship/Actions/BatchUpdateInternshipStatusAction.php
final class BatchUpdateInternshipStatusAction extends BaseCommandAction
{
    public function execute(Builder $query, InternshipStatus $status): int;
    // Applies status to all matching records; no per-record transition validation
}

// app/Program/Internship/Actions/ReadCloseReadinessAction.php
final class ReadCloseReadinessAction extends BaseReadAction
{
    public function execute(Internship $internship): array;
    // Returns 5-domain readiness report
}

// app/Program/InternshipGroup/Actions/CreateInternshipGroupAction.php
final class CreateInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroupData $data): InternshipGroup;
}

// app/Program/InternshipGroup/Actions/UpdateInternshipGroupAction.php
final class UpdateInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, InternshipGroupData $data): InternshipGroup;
}

// app/Program/InternshipGroup/Actions/DeleteInternshipGroupAction.php
final class DeleteInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group): void;
    // Blocks if has members
}

// app/Program/InternshipGroup/Actions/AddMemberToGroupAction.php
final class AddMemberToGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, InternshipGroupRole $role, ...): InternshipGroupMember;
    // Student: requires registration_id; Teacher/Supervisor: requires user_id
}

// app/Program/InternshipGroup/Actions/RemoveMemberFromGroupAction.php
final class RemoveMemberFromGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroupMember $member): void;
}
```

### 6.12 Validation Rule

```php
// app/Program/Internship/Rules/OpenForRegistration.php
final class OpenForRegistration implements ValidationRule
{
    // Uses InternshipPeriod entity to validate registration eligibility
    // Combines status check + date window check
}
```

### 6.13 Events

```php
// app/Program/Internship/Events/InternshipCreated.php
class InternshipCreated
{
    public function __construct(public Internship $internship) {}
}

// app/Program/Internship/Events/InternshipStatusBatchUpdated.php
class InternshipStatusBatchUpdated
{
    public function __construct(public int $count, public string $newStatus) {}
}
```

### 6.14 Listener

```php
// app/Program/Internship/Listeners/NotifyAdminsInternshipCreated.php
class NotifyAdminsInternshipCreated
{
    // Handles InternshipCreated event; sends notification to admin users
}
```

### 6.15 Policies

```php
// app/Program/Internship/Policies/InternshipPolicy.php
class InternshipPolicy extends BasePolicy
{
    viewAny/User: all 5 roles (super_admin, admin, teacher, supervisor, student)
    create: admin only
    update: admin only
    delete: admin only + no placements + no registrations
    forceDelete: super_admin only
}

// app/Program/InternshipGroup/Policies/InternshipGroupPolicy.php
class InternshipGroupPolicy extends BasePolicy
    viewAny/view: all users
    create/update/delete: admin only
```

### 6.16 Routes

```php
// routes/web/program.php
Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
    });
```

### 6.17 Livewire Components

```php
// app/Program/Internship/Livewire/InternshipManager.php
// Features: CRUD, search, filter (status, academic_year_id, date_from, date_to),
//           CSV import (name+description → DRAFT with active year defaults),
//           CSV export, batch close, pre-close readiness check UI

// app/Program/InternshipGroup/Livewire/InternshipGroupManager.php
// Features: CRUD, member management modal, role-based member add
```

---

## 7. Design Decisions

### DD-1 — JSON Phases Over Separate Table

**Decision:** Internship phases are stored as a JSON column on the `internships` table rather
than a separate `internship_phases` table.
**Rationale:** Phases are configuration data (name, weight, date range) that are always read
and written as a complete set with their parent internship. They don't need independent queries,
don't have their own lifecycle, and are always displayed together. A separate table would add
JOIN overhead and migration complexity for no query benefit.
**Trade-off:** Cannot query individual phases across internships (e.g., "find all internships
with a phase named X"). This is acceptable because phase queries are always scoped to a single
internship. The global `internship_phases` setting provides the default phase template.

### DD-2 — Entity Bridge Pattern for Business Rules

**Decision:** Business rule queries (`canBeDeleted`, `isAcceptingRegistrations`) are encapsulated
in `final readonly` entity classes (`InternshipState`, `InternshipPeriod`, `InternshipGroupState`)
rather than placed on the Model or in the Action.
**Rationale:** Models should be data holders; Actions should be orchestration. Business rule
queries that depend on model state but aren't CRUD operations belong in entities. The entity
bridge pattern (`asInternshipPeriod()`, `asInternshipState()`) keeps the contract explicit and
testable without polluting the Model with business logic.
**Trade-off:** Extra classes for simple boolean checks. Mitigated by entities being reusable
across multiple consumers (Policy, Action, Livewire, Rule).

### DD-3 — Status State Machine at Action Level, Not Database

**Decision:** Status transition validation is enforced in `UpdateInternshipAction` via the
enum's `canTransitionTo()` method, not by database constraints or triggers.
**Rationale:** Database-level constraints cannot express complex state machine rules (multiple
valid transitions per source state). The enum encodes the complete state machine, and the Action
validates before updating. `BatchUpdateInternshipStatusAction` intentionally skips per-record
validation because batch operations apply a known-safe target status (e.g., COMPLETED after
readiness checks pass).
**Trade-off:** A direct database update (bypassing the Action) could violate the state machine.
Mitigated by architectural convention (C1: no Model mutations in Livewire) and the arch-guard
scanner.

### DD-4 — Pre-Close Readiness as a Dedicated Read Action

**Decision:** `ReadCloseReadinessAction` is a standalone `BaseReadAction` rather than being
inlined in the close/batch-close flow or placed on the Model.
**Rationale:** Readiness checking involves 5 cross-module queries (Assessment, Submission,
SupervisionLog, Attendance, Certificate). Extracting it as a Read Action makes it independently
testable, reusable (single close, batch close, UI preview), and keeps the close Actions focused
on status transitions. The Action returns a structured report that the UI can render directly.
**Trade-off:** 5 separate queries per readiness check. Acceptable because checks are infrequent
(admin-initiated) and scoped to active registrations only.

### DD-5 — Group Capacity via Placement Layer

**Decision:** Group member capacity is not enforced at the `InternshipGroup` level. Capacity
constraints are managed through the Placement layer (company slot quotas).
**Rationale:** The `InternshipGroup` represents a cohort at a company slot, and the slot's
capacity is defined in the Partners/Enrollment module. Enforcing capacity at the group level
would duplicate the quota logic. The group's `placement_id` FK links to the Placement which
carries the capacity limit.
**Trade-off:** Groups can technically exceed capacity if `AddMemberToGroupAction` doesn't check
the placement quota. Mitigated by the Enrollment module enforcing placement-level constraints
before group assignment.

### DD-6 — Batch Close Bypasses Per-Record Transition Validation

**Decision:** `BatchUpdateInternshipStatusAction` applies the target status directly via
`Builder::update()` without checking `canTransitionTo()` for each record.
**Rationale:** Batch close is only invoked after `ReadCloseReadinessAction` passes for all
selected internships. The readiness check is the real gate; the batch update is the mechanical
status change. Validating transitions for 50+ records individually would be wasteful and slow.
The batch action accepts a pre-filtered query builder that should already target only eligible
records.
**Trade-off:** A bug in the caller could pass an unfiltered query and mark wrong statuses.
Mitigated by the `InternshipManager` Livewire component controlling which records are selected
and the admin confirming the batch operation.

---

## 8. Success Metrics

### 8.1 Status Lifecycle

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Illegal transition rejection | 100% of invalid transitions rejected | `UpdateInternshipAction` unit tests covering all 5 states × all targets |
| Terminal state enforcement | No transitions from COMPLETED or CANCELLED | `InternshipStatus::validTransitions()` returns [] for terminal states |
| Event dispatch on creation | Every create → `InternshipCreated` dispatched | Listener coverage in tests |

### 8.2 Registration Windows

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Compound check accuracy | 100% correct accept/reject for all status × date combinations | `InternshipPeriod` unit tests covering all branches |
| Date span validation | Internship dates must fall within academic year | `datesSpanOutsideAcademicYear` unit tests |

### 8.3 Pre-Close Readiness

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Check completeness | All 5 domains checked every time | `ReadCloseReadinessAction` returns all 5 keys |
| Pending count accuracy | Pending counts match actual DB records | Integration tests with seeded data |
| Completion time | < 2s for 200 active registrations | Performance test |

### 8.4 Group Management

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deletion guard | 100% of groups with members blocked from deletion | `InternshipGroupState::canBeDeleted()` unit tests |
| Uniqueness enforcement | Zero duplicate (group+user) or (group+registration) pairs | Database constraint + `AddMemberToGroupAction` validation tests |
| Role-based add | Student adds require registration_id; teacher/supervisor require user_id | `AddMemberToGroupAction` unit tests per role |

---

## Quick References

### Internship Submodule

- `app/Program/Internship/Models/Internship.php` — Internship model with JSON columns and entity bridges
- `app/Program/Internship/Enums/InternshipStatus.php` — 5-state enum with state machine
- `app/Program/Internship/Entities/InternshipState.php` — deletion guard (placement + registration counts)
- `app/Program/Internship/Entities/InternshipPeriod.php` — registration window + academic year checks
- `app/Program/Internship/Data/InternshipData.php` — DTO for create/update
- `app/Program/Internship/Actions/CreateInternshipAction.php` — creation with auto-fill academic year
- `app/Program/Internship/Actions/UpdateInternshipAction.php` — update with state machine enforcement
- `app/Program/Internship/Actions/DeleteInternshipAction.php` — deletion with guard
- `app/Program/Internship/Actions/BatchUpdateInternshipStatusAction.php` — batch status update
- `app/Program/Internship/Actions/ReadCloseReadinessAction.php` — 5-domain readiness check
- `app/Program/Internship/Rules/OpenForRegistration.php` — validation rule for registration eligibility
- `app/Program/Internship/Policies/InternshipPolicy.php` — authorization with deletion guard
- `app/Program/Internship/Livewire/InternshipManager.php` — CRUD, search, filter, CSV, batch, readiness UI
- `app/Program/Internship/Livewire/Forms/InternshipForm.php` — form validation
- `app/Program/Internship/Events/InternshipCreated.php` — creation event
- `app/Program/Internship/Events/InternshipStatusBatchUpdated.php` — batch update event
- `app/Program/Internship/Listeners/NotifyAdminsInternshipCreated.php` — admin notification listener
- `app/Program/Internship/Notifications/InternshipCreatedNotification.php` — notification class

### InternshipGroup Submodule

- `app/Program/InternshipGroup/Models/InternshipGroup.php` — group model with placement FK
- `app/Program/InternshipGroup/Models/InternshipGroupMember.php` — member model with role + uniqueness
- `app/Program/InternshipGroup/Enums/InternshipGroupRole.php` — 3-role enum (student, teacher, supervisor)
- `app/Program/InternshipGroup/Entities/InternshipGroupState.php` — deletion guard (member count)
- `app/Program/InternshipGroup/Data/InternshipGroupData.php` — DTO for create/update
- `app/Program/InternshipGroup/Actions/CreateInternshipGroupAction.php` — group creation
- `app/Program/InternshipGroup/Actions/UpdateInternshipGroupAction.php` — group update
- `app/Program/InternshipGroup/Actions/DeleteInternshipGroupAction.php` — deletion with guard
- `app/Program/InternshipGroup/Actions/AddMemberToGroupAction.php` — role-based member add
- `app/Program/InternshipGroup/Actions/RemoveMemberFromGroupAction.php` — member removal
- `app/Program/InternshipGroup/Policies/InternshipGroupPolicy.php` — authorization
- `app/Program/InternshipGroup/Livewire/InternshipGroupManager.php` — CRUD + member modal
- `app/Program/InternshipGroup/Livewire/Forms/InternshipGroupForm.php` — form validation

### Routes & Module Docs

- `routes/web/program.php` — /admin/internships, /admin/internships/groups
- `docs/modules/program.md` — Program module overview
