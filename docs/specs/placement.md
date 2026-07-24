# Placement — CRUD, Capacity Management & Change Requests

> **Last updated:** 2026-07-22 **Changes:** feat — split from enrollment.md; placement CRUD,
> capacity, direct placement, change requests

## Description

Specification of the Internara Enrollment module's placement initiative: company placement slot
management, CRUD operations, capacity tracking, direct placement by admin, and mid-program
placement change request workflows. Registration and guest account applications are separate
initiatives — see [registration.md](registration.md) and [account-application.md](account-application.md).

---

## 1. Problem Statements

### PS-1 — Placement Capacity Atomicity

Each company placement has a finite quota. When multiple students register simultaneously or
an admin performs direct placement at the same time a student self-registers, the system must
prevent overbooking. A naive check-then-act pattern (`if slots > 0 then increment`) leaves a
race window where two concurrent requests both see available slots and both succeed, exceeding
the quota. Capacity enforcement must be atomic within a single database transaction.

### PS-2 — Mid-Program Placement Change Requests

Students may need to change their assigned company during an internship due to workplace
conflicts, relocation, or supervisor issues. Without a formal request workflow, placement
changes are ad-hoc and error-prone — quota bookkeeping gets out of sync, old placements are
not freed, and there is no audit trail. A structured request → review → approve/reject
workflow with atomic quota transfer is required.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Enforce placement capacity atomically within a single transaction (no overbooking) |
| G2  | Support a structured placement change request → admin review → approve/reject workflow |
| G3  | Prevent duplicate placements (same company + internship)           |
| G4  | Provide admin dashboard for placement stats with available slots   |
| G5  | Provide direct placement action for admin-initiated enrollment     |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Registration workflow (see [registration.md](registration.md))   |
| NG2  | Guest-to-student account application (see [account-application.md](account-application.md)) |
| NG3  | Automated placement matching algorithms (manual selection)       |
| NG4  | Student self-service placement swap (peer-to-peer without admin) |
| NG5  | Real-time placement slot notifications (WebSocket push)          |

---

## 3. User Stories / Use Cases

### UC-1 — Student Requests Placement Change

**Actor:** Student (role: student)
**Preconditions:** Student has an active registration with a placement assigned; no existing pending change request for this registration
**Flow:**
1. Student navigates to `/student/internships/placement-change` (StudentPlacementChangeRequest)
2. System shows current placement and available placements within the same internship (excluding current)
3. Student selects target placement and provides a reason
4. `RequestPlacementChangeAction` guards: no existing pending request for this registration
5. `PlacementChangeRequest` created with status `PENDING`
6. Admin reviews at `/admin/internships/placements/changes` (PlacementChangeManager)
7. Admin approves: `ApprovePlacementChangeAction` atomically decrements old placement `filled_quota`, increments new placement `filled_quota`, updates registration's `placement_id`
8. Or admin rejects: `RejectPlacementChangeAction` records rejection reason
**Postconditions:** Either placement swapped atomically (quota transferred) or request rejected with reason

### UC-2 — Admin Manages Placements

**Actor:** Admin (role: super_admin or admin)
**Preconditions:** Admin is authenticated
**Flow:**
1. Admin navigates to `/admin/internships/placements` (PlacementIndex)
2. PlacementIndex displays stats: total placements, total quota, filled slots, available slots
3. Admin can create new placements, update existing, or delete (guarded by registrations)
4. Admin can perform direct placement via `/admin/internships/placements/direct` (DirectPlacementManager)
5. `DirectPlacementAction` atomically creates registration + increments placement filled_quota
**Postconditions:** Placement CRUD completed; direct placement creates active registration

---

## 4. Functional Requirements

### Placement — CRUD & Capacity

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-P1 | Placement model must use UUID primary key with cascade delete on `company_id` → `companies` and `internship_id` → `internships` |
| FR-P2 | Placement must enforce unique constraint on `(company_id, internship_id)`            |
| FR-P3 | Placement `quota` must default to 1; `filled_quota` must default to 0               |
| FR-P4 | `PlacementCapacity` entity must provide `isFull()`, `availableSlots()`, `hasAvailableSlots()` |
| FR-P5 | `PlacementState` entity must provide `registrationCount` and `canBeDeleted()` (only when `registrationCount === 0`) |
| FR-P6 | `DeletePlacementAction` must block deletion when registrations exist for the placement |
| FR-P7 | `CreatePlacementAction` and `UpdatePlacementAction` must handle standard CRUD with validation |
| FR-P8 | `PlacementIndex` Livewire component must display stats: total placements, total quota, filled slots, available slots |
| FR-P9 | `PlacementIndex` must support search and filter by company and internship             |

### Placement — Direct Placement

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-P10 | `DirectPlacementAction` must atomically create registration + placement in a single transaction |
| FR-P11 | `DirectPlacementAction` must guard: target placement must have available slots         |
| FR-P12 | `DirectPlacementManager` Livewire component must provide admin form: select student, select placement, assign mentors |

### Placement Change Requests — Workflow

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-C1 | `PlacementChangeStatus` enum must implement `LabelEnum` and `StatusEnum` contracts    |
| FR-C2 | Valid transitions: `PENDING` → [`APPROVED`, `REJECTED`]; `APPROVED` and `REJECTED` are terminal |
| FR-C3 | `RequestPlacementChangeAction` must guard: no existing `PENDING` request for this registration |
| FR-C4 | `ApprovePlacementChangeAction` must guard: request is not terminal, target placement has available slots |
| FR-C5 | `ApprovePlacementChangeAction` must atomically: decrement old placement `filled_quota`, increment new placement `filled_quota`, update registration `placement_id` |
| FR-C6 | `RejectPlacementChangeAction` must record `rejection_reason` and transition to `REJECTED` |
| FR-C7 | `PlacementChangeManager` Livewire component must display pending requests for admin review |
| FR-C8 | `StudentPlacementChangeRequest` must display available placements within the same internship, excluding current placement |
| FR-C9 | `PlacementChangeRequestPolicy` must govern request creation and review permissions     |

### Placement — Livewire Components & Routing

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-L1 | `PlacementIndex` must provide full CRUD at `/admin/internships/placements` with admin middleware |
| FR-L2 | `DirectPlacementManager` must be at `/admin/internships/placements/direct` with admin middleware |
| FR-L3 | `PlacementChangeManager` must be at `/admin/internships/placements/changes` with admin middleware |
| FR-L4 | `StudentPlacementChangeRequest` must be at `/student/internships/placement-change` with `role:student` middleware |

---

## 5. Non-Functional Requirements

| ID    | Requirement                                                                          |
| ----- | ------------------------------------------------------------------------------------ |
| NFR-P2 | Placement capacity check and increment must execute within a single DB transaction (< 100ms) |
| NFR-R2 | Placement quota must never go negative — `filled_quota` decrement must be guarded     |
| NFR-R3 | Concurrent placement registrations must not exceed quota — atomic check-and-increment required |
| NFR-U2 | `PlacementChangeManager` must display request reason and both source/target placements for informed admin review |
| NFR-M1 | All enrollment Actions must extend appropriate base classes (BaseCommandAction, BaseReadAction) |
| NFR-A1 | All enrollment UI (placement management) must meet WCAG 2.1 Level AA                 |
| NFR-A3 | Form inputs in placement forms must have associated labels                           |
| NFR-A5 | Color contrast must meet 4.5:1 minimum for all enrollment UI text                   |
| NFR-L1 | All user-facing strings in enrollment UI must use `__()` translation helper          |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files          |

---

## 6. API / Data Contracts

### 6.1 Placement Model

```php
// app/Enrollment/Placement/Models/Placement.php
// Table: placements
// PK: id (uuid, cascade)
// FK: company_id → companies (cascade delete)
// FK: internship_id → internships (cascade delete)
// Fillable: company_id, internship_id, name, address, quota (default 1), filled_quota (default 0), description
// Unique: (company_id, internship_id)
```

### 6.2 Placement Entities

```php
// app/Enrollment/Placement/Entities/PlacementCapacity.php
final readonly class PlacementCapacity extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function isFull(): bool;           // filledQuota >= quota
    public function availableSlots(): int;    // max(0, quota - filledQuota)
    public function hasAvailableSlots(): bool; // availableSlots() > 0
}

// app/Enrollment/Placement/Entities/PlacementState.php
final readonly class PlacementState extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function canBeDeleted(): bool;     // registrationCount === 0
}
```

### 6.3 Placement Actions

```php
// app/Enrollment/Placement/Actions/CreatePlacementAction.php
// app/Enrollment/Placement/Actions/UpdatePlacementAction.php
// app/Enrollment/Placement/Actions/DeletePlacementAction.php
//   Guards: canBeDeleted() — blocks if registrations exist

// app/Enrollment/Placement/Actions/DirectPlacementAction.php
final class DirectPlacementAction extends BaseCommandAction
{
    public function execute(User $student, Placement $placement, array $mentors = []): Registration;
    // Atomic: create Registration + increment placement filled_quota
    // Guards: placement must have available slots
}
```

### 6.4 PlacementChangeRequest Model

```php
// app/Enrollment/Placement/Models/PlacementChangeRequest.php
// Table: placement_change_requests
// Fillable: registration_id, from_placement_id, to_placement_id, reason, requested_by, status, processed_by, processed_at, rejection_reason
```

### 6.5 PlacementChangeStatus Enum

```php
// app/Enrollment/Placement/Enums/PlacementChangeStatus.php
enum PlacementChangeStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    // Transitions: PENDING → [APPROVED, REJECTED]
    // Terminal: APPROVED, REJECTED
}
```

### 6.6 Placement Change Actions

```php
// app/Enrollment/Placement/Actions/RequestPlacementChangeAction.php
// Guards: no existing PENDING request for this registration

// app/Enrollment/Placement/Actions/ApprovePlacementChangeAction.php
// Guards: not terminal, target has available slots
// Atomic: decrement old filled_quota, increment new filled_quota, update registration placement_id

// app/Enrollment/Placement/Actions/RejectPlacementChangeAction.php
// Records rejection_reason
```

### 6.7 Routes

```php
// routes/web/enrollment.php (placement portion)

// Student
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::livewire('/internships/placement-change', StudentPlacementChangeRequest::class)->name('internships.placement-change');
});

// Admin
Route::prefix('admin')->name('enrollment.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
    Route::livewire('/internships/placements/changes', PlacementChangeManager::class)->name('internships.placements.changes');
});
```

### 6.8 Database Migrations

| Migration | Table |
| --------- | ----- |
| `2026_01_04_000002_create_placements_table.php` | `placements` |
| `2026_01_05_000003_create_placement_change_requests_table.php` | `placement_change_requests` |

---

## 7. Design Decisions

### DD-1 — Atomic Capacity Enforcement in Application Transaction

**Decision:** Placement capacity (`filled_quota`) is checked and incremented within the same
PHP transaction using `DB::transaction()`, not at the database level with `SELECT ... FOR UPDATE`.
**Rationale:** SQLite (development database) has limited locking support. Application-level
transaction wrapping is sufficient for single-tenant, low-concurrency deployment.
**Trade-off:** Under extreme concurrent load, the application-level check could theoretically
race. Mitigated by the single-tenant deployment model.

### DD-2 — Placement Change as Separate Model

**Decision:** Placement changes use a dedicated `PlacementChangeRequest` model with its own
status enum (`PlacementChangeStatus`), rather than modifying the Registration's `placement_id`
directly.
**Rationale:** A separate model provides a complete audit trail (who requested, when, reason,
who approved, when). It enables the request → review → approve/reject workflow with proper
authorization checks.
**Trade-off:** Additional model, migration, and enum to maintain. Mitigated by the clear
separation of concerns.

---

## 8. Success Metrics

### 8.1 Capacity Integrity

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Overbooking incidents           | 0 (quota never exceeded) | Atomic `DB::transaction()` in VerifyRegistrationAction and ApprovePlacementChangeAction |
| Quota accuracy                  | `filled_quota` matches actual registrations | Periodic audit: `count(registrations where placement_id=X)` vs `placements.filled_quota` |
| Concurrent safety               | No race condition under normal load | Transaction isolation within single-tenant deployment |

### 8.2 Placement Change Workflow

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Quota transfer accuracy         | `filled_quota` correct on both old and new placement after approval | Atomic decrement+increment in `ApprovePlacementChangeAction` |
| Orphan request prevention       | 0 pending requests for already-processed registrations | `RequestPlacementChangeAction` guard              |
| Admin review completeness       | All pending requests reviewed within 48 hours | `PlacementChangeManager` admin workflow tracking  |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [registration.md](registration.md) | Enrollment records — placement matches these to companies |
| [partnership-management.md](partnership-management.md) | Partnership entities — placement assigns students to company partnerships |

### Build Guide
After implementing this spec, the system can match enrolled students to company partnerships, creating active placement records. Placement is the trigger for daily operations — once a student is placed, they can log activities, attend, and be assessed. The next steps are to build daily activity logging and the assessment framework.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [daily-activity.md](daily-activity.md) | Reads active placement records for logbook and attendance tracking |
| 2 | [assessment.md](assessment.md) | Scores student work against rubrics using placement context |
| 3 | [evaluation.md](evaluation.md) | Gathers feedback using placement context |

## Quick References

- `app/Enrollment/Placement/Models/Placement.php` — Placement model (UUID PK, quota/filled_quota)
- `app/Enrollment/Placement/Entities/PlacementCapacity.php` — Capacity entity (isFull, availableSlots)
- `app/Enrollment/Placement/Entities/PlacementState.php` — Placement state (canBeDeleted)
- `app/Enrollment/Placement/Actions/CreatePlacementAction.php` — Placement CRUD
- `app/Enrollment/Placement/Actions/UpdatePlacementAction.php` — Placement update
- `app/Enrollment/Placement/Actions/DeletePlacementAction.php` — Placement deletion with guard
- `app/Enrollment/Placement/Actions/DirectPlacementAction.php` — Atomic registration+placement creation
- `app/Enrollment/Placement/Policies/PlacementPolicy.php` — Placement authorization
- `app/Enrollment/Placement/Livewire/PlacementIndex.php` — Placement CRUD with stats
- `app/Enrollment/Placement/Livewire/DirectPlacementManager.php` — Admin direct placement form
- `app/Enrollment/Placement/Livewire/Forms/PlacementForm.php` — Placement form validation
- `app/Enrollment/Placement/Livewire/Forms/DirectPlacementForm.php` — Direct placement form validation
- `app/Enrollment/Placement/Models/PlacementChangeRequest.php` — Change request model
- `app/Enrollment/Placement/Enums/PlacementChangeStatus.php` — Change status enum (PENDING/APPROVED/REJECTED)
- `app/Enrollment/Placement/Actions/RequestPlacementChangeAction.php` — Student change request
- `app/Enrollment/Placement/Actions/ApprovePlacementChangeAction.php` — Atomic quota swap on approval
- `app/Enrollment/Placement/Actions/RejectPlacementChangeAction.php` — Change rejection
- `app/Enrollment/Placement/Policies/PlacementChangeRequestPolicy.php` — Change request authorization
- `app/Enrollment/Placement/Livewire/PlacementChangeManager.php` — Admin change review
- `app/Enrollment/Placement/Livewire/StudentPlacementChangeRequest.php` — Student change request form
- `app/Enrollment/Placement/Livewire/Forms/PlacementChangeForm.php` — Change form validation
- `routes/web/enrollment.php` — All enrollment route definitions
- `docs/modules/enrollment.md` — Enrollment module overview
- **Related specs:** [registration.md](registration.md) — Registration workflow & documents
- **Related specs:** [account-application.md](account-application.md) — Guest-to-student account pipeline
