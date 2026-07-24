# Internship Lifecycle — Program CRUD, Status Machine, Registration Windows & Pre-Close Readiness

> **Last updated:** 2026-07-22 **Changes:** feat — split from internships.md; program lifecycle,
> status, registration windows, readiness, CSV

## Description

Specification of the Internara Program module's internship lifecycle initiative: internship
program definition, status lifecycle with state machine, registration window governance,
pre-close readiness validation, and CSV import/export. Group management is a separate
initiative — see [internship-groups.md](internship-groups.md).

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
readiness check covering five domains and present a detailed report before allowing closure.

### PS-4 — Internship Deletion Must Protect Referential Integrity

Internships are referenced by placements, registrations, and downstream records (attendance,
logbooks, assessments, certificates). Deleting an internship with active placements or
registrations would orphan these records and break foreign key relationships.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Enforce a strict status state machine with validated transitions at the Action level |
| G2  | Govern registration windows through the `InternshipPeriod` entity  |
| G3  | Provide pre-close readiness validation across five domains        |
| G4  | Block internship deletion when related records exist              |
| G5  | Support CSV import/export for internship programs                 |
| G6  | Support batch status updates (including batch close)              |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Group management (see [internship-groups.md](internship-groups.md)) |
| NG2  | Separate timeline/phases submodule — phases are JSON on the model |
| NG3  | Automated status transitions — transitions are manual/admin-driven |
| NG4  | Multi-tenant internship isolation — single-tenant                |
| NG5  | Real-time notifications for status changes — events are fire-and-forget |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates and Publishes an Internship

**Actor:** Admin
**Preconditions:** At least one academic year exists; admin has internship permission
**Flow:**
1. Admin navigates to Admin → Internships (`/admin/internships`)
2. `InternshipManager` shows list of existing internships
3. Admin clicks "Create"; `InternshipForm` loads with active academic year auto-selected
4. Admin fills in: name (required), start date, end date, description (optional)
5. `CreateInternshipAction` executes: validates dates within academic year, auto-fills active year, creates with DRAFT status
6. Admin clicks "Publish" → validates DRAFT → PUBLISHED transition
**Postconditions:** Internship created as DRAFT, published successfully

### UC-2 — Student Registers During Open Window

**Actor:** Student (via Enrollment module, validated by Program)
**Preconditions:** Internship is PUBLISHED or ACTIVE; registration window dates set; current date within window
**Flow:**
1. Student initiates registration (via Enrollment module)
2. System resolves `InternshipPeriod` from Internship model
3. `InternshipPeriod::isAcceptingRegistrations()` checks status + date window
4. If all checks pass, registration proceeds; if any fail, validation rejects
**Postconditions:** Registration accepted or rejected based on compound status+date check

### UC-3 — Admin Runs Pre-Close Readiness Check

**Actor:** Admin
**Preconditions:** Internship has active registrations
**Flow:**
1. Admin clicks "Check Readiness" or "Close" on an ACTIVE internship
2. `ReadCloseReadinessAction` executes: checks assessments, submissions, supervision logs, attendance, certificates
3. Returns structured array with per-domain `passed`, `total`, `pending`, `message`
4. If all five domains pass, admin can proceed with batch close
**Postconditions:** Readiness report displayed; admin informed of blockers

### UC-4 — Admin Batch Closes Internships

**Actor:** Admin
**Preconditions:** Multiple internships are ACTIVE
**Flow:**
1. Admin filters internships by status=ACTIVE, selects multiple, clicks "Batch Close"
2. System runs readiness checks for each selected
3. If all pass, `BatchUpdateInternshipStatusAction` updates all matching records
4. If any fails, admin sees per-internship breakdown of blockers
**Postconditions:** Selected internships marked COMPLETED; event dispatched

---

## 4. Functional Requirements

### Internship CRUD

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-IC1 | `Internship` model must use `#[Fillable]` with: academic_year_id, name, start_date, end_date, description, status, phases, required_document_ids, grading_weights |
| FR-IC2 | `Internship` must cast status to `InternshipStatus` enum, dates to date, JSON columns to json |
| FR-IC3 | `Internship` must have `belongsTo` AcademicYear (FK nullable, set null), `hasMany` Placements, `hasMany` Registrations |
| FR-IC4 | `Internship` must provide `asInternshipPeriod()` and `asInternshipState()` bridge methods |
| FR-IC5 | `InternshipData` DTO must require: name, academicYearId, startDate, endDate; accept optional: description, status, registrationStartDate, registrationEndDate |
| FR-IC6 | `CreateInternshipAction` must auto-fill active academic year and create with DRAFT status |
| FR-IC7 | `UpdateInternshipAction` must validate status transition via `InternshipStatus::canTransitionTo()` |
| FR-IC8 | `UpdateInternshipAction` must reject illegal transitions with `RejectedException` |
| FR-IC9 | `DeleteInternshipAction` must check `InternshipState::canBeDeleted()` — blocks when placementCount > 0 or registrationCount > 0 |
| FR-IC10 | `InternshipManager` must display: name, academic year, date range, status, action buttons |
| FR-IC11 | `InternshipManager` must support search by name and filter by: status, academic_year_id, date_from, date_to |
| FR-IC12 | `InternshipPolicy` must grant view/viewAny to all 5 roles; create/update/delete to admin only; delete must check placements and registrations |

### Status Lifecycle

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-SL1 | `InternshipStatus` enum must define 5 cases: DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED |
| FR-SL2 | Valid transitions: DRAFT → [PUBLISHED, CANCELLED], PUBLISHED → [ACTIVE, CANCELLED], ACTIVE → [COMPLETED, CANCELLED] |
| FR-SL3 | COMPLETED and CANCELLED must be terminal (no valid transitions out)                  |
| FR-SL4 | `isAcceptingRegistrations()` returns true for PUBLISHED and ACTIVE only              |
| FR-SL5 | `isTerminal()` returns true for COMPLETED and CANCELLED only                        |
| FR-SL6 | `UpdateInternshipAction` must enforce the state machine for single-record changes    |
| FR-SL7 | `BatchUpdateInternshipStatusAction` must apply target status without per-record transition validation |
| FR-SL8 | `InternshipCreated` event dispatched after creation; `InternshipStatusBatchUpdated` after batch update |

### Registration Windows

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-RW1 | `InternshipPeriod` entity must encapsulate: status, registrationStartDate, registrationEndDate, academicYearStart, academicYearEnd |
| FR-RW2 | `InternshipPeriod::isAcceptingRegistrations()` checks status + date window           |
| FR-RW3 | `InternshipPeriod::isRegistrationWindowOpen()` checks only date range (ignore status) |
| FR-RW4 | `InternshipPeriod::isBeforeRegistrationWindow()` / `isAfterRegistrationWindow()` for status messaging |
| FR-RW5 | `InternshipPeriod::isWithinAcademicYear()` / `datesSpanOutsideAcademicYear()` for validation |
| FR-RW6 | `OpenForRegistration` validation rule must use `InternshipPeriod` to gate registration attempts |
| FR-RW7 | `InternshipForm` must include registrationStartDate and registrationEndDate fields    |

### Pre-Close Readiness

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-PC1 | `ReadCloseReadinessAction` must accept an `Internship` and return an array of 5 readiness domains |
| FR-PC2 | Assessments check: all active registrations must have `finalized_at` set             |
| FR-PC3 | Submissions check: no submissions in DRAFT, SUBMITTED, or REVISION_REQUIRED status   |
| FR-PC4 | Supervision logs check: all logs must have `is_verified = true`                      |
| FR-PC5 | Attendance check: all records must have `is_verified = true`                         |
| FR-PC6 | Certificates check: all must be ISSUED status, and at least one must exist          |
| FR-PC7 | Each domain returns: `passed` (bool), `total` (int), `pending` (int), `message` (string) |
| FR-PC8 | `InternshipManager` must display readiness check results with pass/fail indicators   |

### CSV Import/Export

| ID   | Requirement                                                                          |
| ---- | ------------------------------------------------------------------------------------ |
| FR-CE1 | `InternshipManager` must support CSV import with columns: name, description          |
| FR-CE2 | CSV import must create DRAFT internships with active academic year start/end dates   |
| FR-CE3 | `InternshipManager` must support CSV export of filtered internships                   |
| FR-CE4 | CSV export must include: name, description, status, start_date, end_date, academic_year |

---

## 5. Non-Functional Requirements

| ID    | Requirement                                                                          |
| ----- | ------------------------------------------------------------------------------------ |
| NFR-S1 | Status transition validation must reject illegal transitions with `RejectedException` |
| NFR-S2 | `InternshipPolicy` must enforce authorization at every CRUD operation                |
| NFR-P1 | `ReadCloseReadinessAction` must complete within 2s for up to 200 active registrations |
| NFR-P2 | `BatchUpdateInternshipStatusAction` must process up to 50 internships in < 3s        |
| NFR-P3 | `InternshipPeriod` entity instantiation must complete in < 5ms (read-only, no queries) |
| NFR-R1 | `CreateInternshipAction` must wrap creation in a database transaction                |
| NFR-R2 | `BatchUpdateInternshipStatusAction` must wrap batch update in a database transaction  |
| NFR-R3 | `DeleteInternshipAction` must verify related records within the same transaction     |
| NFR-U1 | `InternshipManager` must show status badges with distinct colors per status          |
| NFR-U2 | Pre-close readiness UI must display per-domain pass/fail with actionable pending counts |
| NFR-U4 | Deletion blocked messages must explain which related records prevent deletion         |
| NFR-M1 | All Program module classes must use `declare(strict_types=1)`                       |
| NFR-M2 | All models must use `#[Fillable]` attribute                                         |
| NFR-A1 | All internship management UI must meet WCAG 2.1 Level AA                            |
| NFR-A2 | Status badges must include text labels alongside color                              |
| NFR-A3 | Pre-close readiness modal must trap focus and be keyboard-navigable                  |
| NFR-A5 | All interactive elements must have visible focus indicators                          |
| NFR-L1 | All user-facing strings must use `__()` translation helper                          |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files          |
| NFR-L3 | Internship status labels must use `InternshipStatus::label()`                       |

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
    public function validTransitions(): array;
    public function canTransitionTo(StatusEnum $target): bool;
    public function label(): string;
}
```

### 6.2 Internship Model

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

### 6.3 InternshipState Entity

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

### 6.4 InternshipPeriod Entity

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

### 6.5 InternshipData DTO

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

### 6.6 Actions

```php
// app/Program/Internship/Actions/CreateInternshipAction.php
final class CreateInternshipAction extends BaseCommandAction
{
    public function execute(InternshipData $data): Internship;
    // Auto-fills active academic year; creates with DRAFT status
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
```

### 6.7 Validation Rule

```php
// app/Program/Internship/Rules/OpenForRegistration.php
final class OpenForRegistration implements ValidationRule
{
    // Uses InternshipPeriod entity to validate registration eligibility
}
```

### 6.8 Events & Listeners

```php
// app/Program/Internship/Events/InternshipCreated.php
// app/Program/Internship/Events/InternshipStatusBatchUpdated.php
// app/Program/Internship/Listeners/NotifyAdminsInternshipCreated.php
```

### 6.9 Routes

```php
// routes/web/program.php
Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
    });
```

### 6.10 Livewire Components

```php
// app/Program/Internship/Livewire/InternshipManager.php
// Features: CRUD, search, filter, CSV import/export, batch close, pre-close readiness check UI

// app/Program/Internship/Livewire/Forms/InternshipForm.php
```

---

## 7. Design Decisions

### DD-1 — JSON Phases Over Separate Table

**Decision:** Internship phases stored as JSON on the `internships` table.
**Rationale:** Phases are configuration data always read/written as a complete set. No independent
queries or lifecycle. A separate table would add JOIN overhead for no query benefit.
**Trade-off:** Cannot query individual phases across internships. Acceptable.

### DD-2 — Entity Bridge Pattern for Business Rules

**Decision:** Business rule queries encapsulated in `final readonly` entity classes.
**Rationale:** Models are data holders; Actions are orchestration. Business rules belong in
entities. The bridge pattern keeps contracts explicit and testable.
**Trade-off:** Extra classes for simple boolean checks. Mitigated by reusability.

### DD-3 — Status State Machine at Action Level

**Decision:** Transition validation enforced in `UpdateInternshipAction`, not DB constraints.
**Rationale:** Database constraints cannot express complex state machine rules. `BatchUpdate`
intentionally skips per-record validation.
**Trade-off:** Direct DB update could bypass. Mitigated by C1 invariant.

### DD-4 — Pre-Close Readiness as Dedicated Read Action

**Decision:** `ReadCloseReadinessAction` is a standalone `BaseReadAction`.
**Rationale:** 5 cross-module queries. Extracted for independent testing, reusability (single close,
batch close, UI preview), keeping close Actions focused.
**Trade-off:** 5 queries per check. Acceptable (admin-initiated, infrequent).

### DD-5 — Batch Close Bypasses Per-Record Validation

**Decision:** `BatchUpdateInternshipStatusAction` applies target status directly.
**Rationale:** Only invoked after readiness checks pass. The check is the real gate.
**Trade-off:** Caller bug could pass unfiltered query. Mitigated by InternshipManager controlling selection.

---

## 8. Success Metrics

### 8.1 Status Lifecycle

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Illegal transition rejection    | 100%        | Unit tests covering all 5 states × all targets    |
| Terminal state enforcement      | No transitions from COMPLETED/CANCELLED | `validTransitions()` returns [] for terminal |

### 8.2 Registration Windows

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Compound check accuracy         | 100% correct for all status × date combinations | `InternshipPeriod` unit tests                      |
| Date span validation            | Always enforced | `datesSpanOutsideAcademicYear` unit tests         |

### 8.3 Pre-Close Readiness

| Metric                          | Target      | Measurement                                       |
| ------------------------------- | ----------- | ------------------------------------------------- |
| Check completeness              | All 5 domains checked every time | Returns all 5 keys                               |
| Pending count accuracy          | Matches actual DB records | Integration tests with seeded data               |
| Completion time                 | < 2s for 200 active registrations | Performance test                                 |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [academic-year-management.md](academic-year-management.md) | Academic year entities — programs run within year date ranges |
| [partnership-management.md](partnership-management.md) | Partnership entities — programs are scoped to partnerships |

### Build Guide
After implementing this spec, the system has internship program CRUD with phases (preparation, active, evaluation, closing), date ranges, and status tracking. Programs define the structure that students enroll in. The next step is to build internship groups, which divide students within a program into manageable cohorts with assigned mentors.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [internship-groups.md](internship-groups.md) | Groups belong to programs; group members reference program phases for scheduling |

## Quick References

- `app/Program/Internship/Models/Internship.php` — Internship model with JSON columns and entity bridges
- `app/Program/Internship/Enums/InternshipStatus.php` — 5-state enum with state machine
- `app/Program/Internship/Entities/InternshipState.php` — deletion guard
- `app/Program/Internship/Entities/InternshipPeriod.php` — registration window + academic year checks
- `app/Program/Internship/Data/InternshipData.php` — DTO for create/update
- `app/Program/Internship/Actions/CreateInternshipAction.php` — creation with auto-fill
- `app/Program/Internship/Actions/UpdateInternshipAction.php` — update with state machine
- `app/Program/Internship/Actions/DeleteInternshipAction.php` — deletion with guard
- `app/Program/Internship/Actions/BatchUpdateInternshipStatusAction.php` — batch status update
- `app/Program/Internship/Actions/ReadCloseReadinessAction.php` — 5-domain readiness check
- `app/Program/Internship/Rules/OpenForRegistration.php` — validation rule
- `app/Program/Internship/Policies/InternshipPolicy.php` — authorization
- `app/Program/Internship/Livewire/InternshipManager.php` — CRUD, CSV, batch, readiness UI
- `routes/web/program.php` — Route definitions
- `docs/modules/program.md` — Program module overview
- **Related specs:** [internship-groups.md](internship-groups.md) — Group & member management
