# Enum Pattern Reference

> **Last updated:** 2026-06-10
>
> **Scope:** Enum contracts, state machine patterns, case conventions, business logic methods, and
> testing strategies used across all Internara modules.

---

## 1. Enum Architecture Overview

All enums in Internara are PHP 8 `string`-backed enums. Every enum **must** implement
`LabelEnum`. State machine enums additionally implement `StatusEnum`. UI badge enums optionally
implement `ColorableEnum`.

```
┌─────────────────────────────────────────────────┐
│                  LabelEnum                       │
│  label(): string                                │
│  ▲                                              │
│  │                                              │
│  ├── StatusEnum (state machines)                │
│  │   isTerminal(): bool                         │
│  │   canTransitionTo(StatusEnum): bool          │
│  │   validTransitions(): array                  │
│  │                                              │
│  └── ColorableEnum (UI badges)                  │
│      color(): string                            │
└─────────────────────────────────────────────────┘
```

### Three-Tier Contract Hierarchy

| Contract | Mandate | Purpose |
|----------|---------|---------|
| `LabelEnum` | All enums | Human-readable label via `__()` translation |
| `StatusEnum` | Lifecycle enums | State machine transitions, terminal detection |
| `ColorableEnum` | UI badge enums | Tailwind/UI color per status |

### File Location Rules

- **Module-specific enum** → `app/{Module}/{SubModule}/Enums/{Name}.php`
- **Cross-submodule enum** → `app/{Module}/Enums/{Name}.php` (inside a module's root `Enums/`)
- **Shared enum** → `app/Enums/{Name}.php` (used by 2+ modules)
- **Test** → `tests/Unit/{Module}/{SubModule}/Enums/{Name}Test.php`

---

## 2. LabelEnum Contract

Every enum in the codebase implements `LabelEnum` (`app/Core/Contracts/LabelEnum.php`):

```php
interface LabelEnum
{
    public function label(): string;
}
```

All `label()` implementations delegate to `__()` for i18n. Two translation styles are used:

### Per-case `match()` (most common)

```php
public function label(): string
{
    return match ($this) {
        self::DRAFT => __('Draft'),
        self::PUBLISHED => __('Published'),
        self::CLOSED => __('Closed'),
    };
}
```

### Dynamic key from value

```php
public function label(): string
{
    return __("account_status.status.{$this->value}");
}

public function label(): string
{
    return __("permission.role.{$this->value}");
}
```

Used when the enum has many cases (e.g. `AccountStatus` with 8 cases) or when translations are
maintained in structured lang files.

### Plain value (no translation needed)

```php
// BloodType
public function label(): string
{
    return $this->value;
}
```

---

## 3. StatusEnum Contract

State machine enums implement `StatusEnum` (`app/Core/Contracts/StatusEnum.php`), which extends
`LabelEnum`:

```php
interface StatusEnum extends LabelEnum
{
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}
```

### Common `canTransitionTo` Implementation

Almost all status enums share the same guard implementation:

```php
public function canTransitionTo(StatusEnum $target): bool
{
    if (! ($target instanceof self)) {
        return false;
    }

    return in_array($target, $this->validTransitions(), true);
}
```

This pattern:
1. Rejects cross-enum transitions (type safety)
2. Delegates valid targets to `validTransitions()`
3. Uses strict `in_array()` — no implicit enum-to-string coercion

### Exception: AccountStatus (extra terminal guard)

`AccountStatus` adds an explicit terminal check before delegating to valid transitions:

```php
public function canTransitionTo(StatusEnum $target): bool
{
    if (! ($target instanceof self)) {
        return false;
    }
    if ($this->isTerminal()) {
        return false;
    }

    return in_array($target, $this->validTransitions(), true);
}
```

This is a belt-and-suspenders approach — `validTransitions()` already returns `[]` for terminal
states, but the explicit guard makes the intent clearer.

---

## 4. ColorableEnum Contract

Optional contract for enums displayed as UI badges:

```php
interface ColorableEnum
{
    public function color(): string;
}
```

Returns a Tailwind color keyword used by UI components for badge rendering:

```php
// AccountStatus (the only ColorableEnum implementer)
public function color(): string
{
    return match ($this) {
        self::PROVISIONED => 'warning',
        self::ACTIVATED => 'info',
        self::VERIFIED => 'success',
        self::PROTECTED => 'primary',
        self::RESTRICTED => 'warning',
        self::SUSPENDED => 'error',
        self::INACTIVE => 'warning',
        self::ARCHIVED => 'error',
    };
}
```

Supported colors: `primary`, `success`, `warning`, `error`, `info`.

---

## 5. Case Convention

### `UPPER_SNAKE` case names, lowercase string values

```php
enum InternshipStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### Value Convention Table

| Convention | Rule | Example |
|------------|------|---------|
| Case name | `UPPER_SNAKE` | `REVISION_REQUIRED` |
| Backing value | lowercase | `'revision_required'` |
| Multi-word value | `snake_case` | `'head_of_department'` |

### Edge Cases

```php
// Short values (single character)
case A = 'a';
case B = 'b';
case AB = 'ab';
case O = 'o';

// Values that differ from case name
case SUPER_ADMIN = 'superadmin';     // not 'super_admin'
case PROVISIONED = 'provisioned';    // not 'provisioned' — identical
```

---

## 6. Business Logic on Enums

Business logic methods live **directly on the enum class**. This is a deliberate choice — enums are
the natural home for behavior that depends solely on the enum's value.

### Domain Query Methods

Boolean methods that answer questions about the current state:

| Enum | Method | Returns `true` for |
|------|--------|--------------------|
| `AssignmentStatus` | `isActive()` | `PUBLISHED` |
| `AttendanceStatus` | `isOnTime()` | `PRESENT` |
| `AttendanceStatus` | `isExcused()` | `PERMISSION`, `SICK` |
| `AccountStatus` | `allowsLogin()` | `ACTIVATED`, `VERIFIED`, `PROTECTED`, `RESTRICTED`, `INACTIVE` |
| `InternshipStatus` | `isAcceptingRegistrations()` | `PUBLISHED`, `ACTIVE` |
| `AuditCategory` | `isCritical()` | `REQUIREMENTS`, `PERMISSIONS`, `DATABASE` |
| `SupervisionLogStatus` | `isActive()` | `PENDING`, `IN_PROGRESS`, `SUBMITTED` |
| `AbsenceReasonType` | `requiresAttachment()` | `SICK`, `EMERGENCY` |
| `AbsenceRequestStatus` | `isProcessed()` | `APPROVED`, `REJECTED` |
| `RegistrationDocumentStatus` | `isPending()` / `isVerified()` / `isRejected()` | per-case helpers |

### Method Naming Rules

| Prefix | Semantics | Example |
|--------|-----------|---------|
| `is` | Boolean state query | `isTerminal()`, `isActive()`, `isOnTime()` |
| `has` | Feature/attribute presence | `hasProperty()` |
| `can` | Permission or ability | `canTransitionTo()` |
| `requires` | Prerequisite needed | `requiresAction()`, `requiresAttachment()` |
| `allows` | Permission granted | `allowsLogin()` |

### Data Provider Methods

Methods that return structured data derived from the enum:

```php
// SettingType — type detection and casting
public static function detect(mixed $value): self { ... }
public function cast(mixed $value): mixed { ... }

// EvaluationCategory — per-category criteria
public function defaultCriteria(): array { ... }

// EmploymentStatus — options for select dropdowns
public static function options(): array { ... }

// Role — role grouping and resolution
public static function userRoles(): array { ... }
public static function functionalRoles(): array { ... }
public function resolvesTo(): array { ... }
public function is(self $functionalRole): bool { ... }

// SettingGroup — default group
public static function default(): self { ... }
```

### Domain-Specific Transition Logic (Non-StatusEnum)

`AnnouncementStatus` implements its own `canTransitionTo` without implementing `StatusEnum`:

```php
enum AnnouncementStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => in_array($target, [self::SCHEDULED, self::PUBLISHED], true),
            self::SCHEDULED => $target === self::PUBLISHED,
            self::PUBLISHED => false,
        };
    }
}
```

---

## 7. State Machine Patterns

### Pattern A: Revision Loop (Draft → Submit → Revision → Draft)

Used by workflow entities with an iterative review cycle.

**LogbookStatus** (4 states):

```
  DRAFT ──────────► SUBMITTED
    ▲                  │
    │                  ▼
    │          REVISION_REQUIRED
    │                  │
    └──────────────────┘
```

Transitions:
- `DRAFT → SUBMITTED`
- `SUBMITTED → VERIFIED` (terminal) or `REVISION_REQUIRED`
- `REVISION_REQUIRED → DRAFT` (back to edit)
- `VERIFIED → []` (terminal)

**SubmissionStatus** (5 states, adds `GRADED` as second terminal):

```
  DRAFT ──────────► SUBMITTED
    ▲                  │
    │                  ├──► VERIFIED (terminal)
    │                  ├──► GRADED (terminal)
    │                  ▼
    │          REVISION_REQUIRED
    │                  │
    └──────────────────┘
```

### Pattern B: Approval Pipeline (Pending → Approved / Rejected)

Used for any request that must be reviewed and either accepted or denied.

```
         ┌──► APPROVED (terminal)
  PENDING┤
         └──► REJECTED (terminal)
```

**Enums using this pattern:**
- `AccountApplicationStatus`
- `RegistrationDocumentStatus`
- `PlacementChangeStatus`
- `AbsenceRequestStatus`

All share identical transition logic — only the labels differ.

### Pattern C: Linear Progression with Cancellation

States move forward through defined stages, with a cancellation escape at each step.

**InternshipStatus** (5 states):

```
  DRAFT ──► PUBLISHED ──► ACTIVE ──► COMPLETED (terminal)
    │            │           │
    └──► CANCELLED (terminal) ◄──┘
```

**PartnershipStatus** (3 states):

```
  ACTIVE ──► EXPIRED (terminal)
    │
    └──► TERMINATED (terminal)
```

**AssignmentStatus** (3 states):

```
  DRAFT ──► PUBLISHED ──► CLOSED (terminal)
    │
    └──► CLOSED (terminal, direct)
```

### Pattern D: Incident Lifecycle

**IncidentStatus** (4 states):

```
  REPORTED ──► INVESTIGATING ──► RESOLVED ──► CLOSED (terminal)
    │               │
    └──► RESOLVED ──┘
```

### Pattern E: Two-State (Binary)

**CertificateStatus**: `ISSUED → REVOKED` (terminal)
**ReportStatus**: `DRAFT → FINALIZED` (terminal)

### Pattern F: All Terminal (No Transitions)

**AttendanceStatus** has 6 states, all terminal — attendance records are recorded directly with
their final classification and never transition:

```
  PRESENT  LATE  EARLY_OUT  ABSENT  PERMISSION  SICK
  (all terminal, no transitions)
```

### Pattern G: Supervision Lifecycle (Complex)

**SupervisionLogStatus** (6 states):

```
  PENDING ──► IN_PROGRESS ──► SUBMITTED ──► VERIFIED ──► COMPLETED (terminal)
    │              │               │
    └──► CANCELLED (terminal) ◄───┘
```

### Pattern H: User Account Lifecycle (Most Complex)

**AccountStatus** (8 states) — the most elaborate state machine in the system:

```
  PROVISIONED ──► ACTIVATED ──► VERIFIED ──► RESTRICTED ──► VERIFIED
       │              │              │              │
       │              ├──► SUSPENDED ──┤              │
       │              │      │         │              │
       │              │      ├──► ACTIVATED           │
       │              │      ├──► VERIFIED            │
       │              │      └──► ARCHIVED (terminal)  │
       │              │                                │
       │              └──► ARCHIVED (terminal)          │
       │                                                │
       ├──► SUSPENDED ──────────────► ARCHIVED          │
       │                                                │
       └────────────────────────────────────────────────┘

  INACTIVE ──► VERIFIED
       │
       ├──► ARCHIVED (terminal)
       └──► SUSPENDED

  PROTECTED (terminal — no outgoing transitions)
```

### Terminal State Summary

| # Terminal States | Examples |
|-------------------|----------|
| 2+ terminal states | `AccountStatus` (PROTECTED, ARCHIVED), `SubmissionStatus` (VERIFIED, GRADED), `InternshipStatus` (COMPLETED, CANCELLED), `SupervisionLogStatus` (COMPLETED, CANCELLED, VERIFIED) |
| 1 terminal state | `IncidentStatus` (CLOSED), `LogbookStatus` (VERIFIED), `AssignmentStatus` (CLOSED), `ReportStatus` (FINALIZED), `CertificateStatus` (REVOKED), `PartnershipStatus` (EXPIRED, TERMINATED) |
| All terminal | `AttendanceStatus` (6 states) |
| None terminal | N/A — all status enums have at least one terminal state |

### Transition Canonical Form

Every `StatusEnum` follows this exact structure:

```php
public function validTransitions(): array
{
    return match ($this) {
        self::STATE_A => [self::STATE_B, self::STATE_C],
        self::STATE_B => [self::STATE_D],
        self::STATE_C => [],
        self::STATE_D => [],
    };
}
```

Rules:
- Terminal states return `[]` (empty array, no further transitions).
- All valid destinations are listed explicitly — no wildcards.
- `match()` is exhaustive: every case must appear.
- Return type `list<static>` — the list of valid target enum cases.

### Guarding Transitions in Actions

Command Actions enforce `canTransitionTo()` before persisting:

```php
class SubmitLogbookAction extends BaseAction
{
    public function execute(Logbook $entry, array $data): Logbook
    {
        $target = LogbookStatus::SUBMITTED;

        if (! $entry->status->canTransitionTo($target)) {
            throw new RejectedException(
                __('Cannot transition from :current to :target', [
                    'current' => $entry->status->label(),
                    'target' => $target->label(),
                ]),
            );
        }

        return $this->transaction(function () use ($entry, $data) {
            // ...
        });
    }
}
```

---

## 8. Model Defaults

Model `$attributes` must use `->value`, never hardcoded strings:

```php
// ✅ Correct
protected $attributes = [
    'status' => InternshipStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];
```

Factory definitions follow the same rule:

```php
public function definition(): array
{
    return [
        'status' => InternshipStatus::DRAFT->value,
    ];
}

public function published(): static
{
    return $this->state(
        fn (array $attrs) => ['status' => InternshipStatus::PUBLISHED->value],
    );
}
```

### Enum Casting on Models

Models cast status columns to enum via `$casts`:

```php
protected $casts = [
    'status' => InternshipStatus::class,
];
```

This allows direct enum comparison on the model:

```php
$entry->status === LogbookStatus::SUBMITTED
$entry->status->canTransitionTo(LogbookStatus::VERIFIED)
$entry->status->label()
```

---

## 9. Enum Method Patterns

### Pattern 1: Action-Oriented Boolean (`isFinalized`, `requiresAction`)

These methods help Livewire and Blade determine what UI to show:

```php
// LogbookStatus
public function isFinalized(): bool
{
    return $this === self::VERIFIED;
}

public function requiresAction(): bool
{
    return in_array($this, [self::SUBMITTED, self::REVISION_REQUIRED], true);
}

// SubmissionStatus
public function isFinalized(): bool
{
    return in_array($this, [self::VERIFIED, self::GRADED], true);
}

public function requiresAction(): bool
{
    return in_array($this, [self::SUBMITTED, self::REVISION_REQUIRED], true);
}
```

Used in Blade for conditional rendering:

```blade
@if($entry->status->requiresAction())
    <x-mary-badge value="{{ $entry->status->label() }}" class="badge-warning" />
@elseif($entry->status->isFinalized())
    <x-mary-badge value="{{ $entry->status->label() }}" class="badge-success" />
@endif
```

### Pattern 2: Static Factory / Default

```php
// SettingGroup
public static function default(): self
{
    return self::GENERAL;
}

// AnnouncementStatus
public static function default(): self
{
    return self::DRAFT;
}
```

### Pattern 3: Static Value Collection

```php
// SettingType
public static function values(): array
{
    return array_map(fn (self $case) => $case->value, self::cases());
}

// EmploymentStatus
public static function options(): array
{
    return array_map(
        fn (self $case) => ['id' => $case->value, 'name' => $case->label()],
        self::cases(),
    );
}
```

### Pattern 4: Role Resolution (Role Enum)

The `Role` enum is the most logic-heavy enum, implementing role grouping and resolution:

```php
enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'superadmin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';
    case MENTOR = 'func_mentor';
    case MENTEE = 'func_mentee';

    public function resolvesTo(): array
    {
        return match ($this) {
            self::ADMIN => [self::SUPER_ADMIN, self::ADMIN],
            self::MENTOR => [self::TEACHER, self::SUPERVISOR],
            self::MENTEE => [self::STUDENT],
            default => [$this],
        };
    }

    public static function functionalRolesFor(self $userRole): array
    {
        return match ($userRole) {
            self::SUPER_ADMIN, self::ADMIN => [self::ADMIN],
            self::TEACHER, self::SUPERVISOR => [self::MENTOR],
            self::STUDENT => [self::MENTEE],
            default => [],
        };
    }
}
```

### Pattern 5: Type Detection / Casting

```php
// SettingType
public static function detect(mixed $value): self
{
    return match (true) {
        is_bool($value) => self::BOOLEAN,
        is_int($value) => self::INTEGER,
        is_float($value) => self::FLOAT,
        is_array($value) => self::JSON,
        $value === null => self::NULL,
        default => self::STRING,
    };
}

public function cast(mixed $value): mixed
{
    return match ($this) {
        self::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
        self::INTEGER => (int) $value,
        self::FLOAT => (float) $value,
        self::JSON => is_string($value) ? json_decode($value, true) : (array) $value,
        self::NULL => null,
        default => (string) $value,
    };
}
```

### Pattern 6: Default Criteria Provider

```php
// EvaluationCategory
public function defaultCriteria(): array
{
    return match ($this) {
        self::MENTOR => [
            'communication' => __('evaluation.criteria.communication'),
            'responsiveness' => __('evaluation.criteria.responsiveness'),
            'guidance_quality' => __('evaluation.criteria.guidance_quality'),
        ],
        // ...
    };
}
```

---

## 10. Complete Enum Inventory

### Legend

| Column | Meaning |
|--------|---------|
| L | Implements `LabelEnum` |
| S | Implements `StatusEnum` (state machine) |
| C | Implements `ColorableEnum` (UI badges) |
| States | Number of cases |
| Terminals | Number of terminal states (if StatusEnum) |

### Status Enums (State Machines — implement `StatusEnum`)

| # | Enum | Module | Path | L | S | C | States | Terminals | Pattern |
|---|------|--------|------|---|---|---|--------|-----------|---------|
| 1 | `AccountStatus` | User | `app/User/Enums/AccountStatus.php` | ✓ | ✓ | ✓ | 8 | 2 (PROTECTED, ARCHIVED) | H — User lifecycle |
| 2 | `SupervisionLogStatus` | Guidance | `app/Guidance/SupervisionLog/Enums/SupervisionLogStatus.php` | ✓ | ✓ | — | 6 | 3 (COMPLETED, CANCELLED, VERIFIED) | G — Supervision cycle |
| 3 | `AttendanceStatus` | Journals | `app/Journals/Attendance/Enums/AttendanceStatus.php` | ✓ | ✓ | — | 6 | 6 (all) | F — All terminal |
| 4 | `SubmissionStatus` | Assignment | `app/Assignment/Submission/Enums/SubmissionStatus.php` | ✓ | ✓ | — | 5 | 2 (VERIFIED, GRADED) | A — Revision loop v2 |
| 5 | `InternshipStatus` | Program | `app/Program/Internship/Enums/InternshipStatus.php` | ✓ | ✓ | — | 5 | 2 (COMPLETED, CANCELLED) | C — Linear + cancel |
| 6 | `IncidentStatus` | Incident | `app/Incident/IncidentReport/Enums/IncidentStatus.php` | ✓ | ✓ | — | 4 | 1 (CLOSED) | D — Incident cycle |
| 7 | `LogbookStatus` | Journals | `app/Journals/Logbook/Enums/LogbookStatus.php` | ✓ | ✓ | — | 4 | 1 (VERIFIED) | A — Revision loop |
| 8 | `AssignmentStatus` | Assignment | `app/Assignment/Enums/AssignmentStatus.php` | ✓ | ✓ | — | 3 | 1 (CLOSED) | C — Linear + cancel |
| 9 | `AccountApplicationStatus` | Enrollment | `app/Enrollment/AccountApplication/Enums/AccountApplicationStatus.php` | ✓ | ✓ | — | 3 | 2 (APPROVED, REJECTED) | B — Approval |
| 10 | `RegistrationDocumentStatus` | Enrollment | `app/Enrollment/Registration/Enums/RegistrationDocumentStatus.php` | ✓ | ✓ | — | 3 | 2 (VERIFIED, REJECTED) | B — Approval |
| 11 | `PlacementChangeStatus` | Enrollment | `app/Enrollment/Placement/Enums/PlacementChangeStatus.php` | ✓ | ✓ | — | 3 | 2 (APPROVED, REJECTED) | B — Approval |
| 12 | `AbsenceRequestStatus` | Journals | `app/Journals/AbsenceRequest/Enums/AbsenceRequestStatus.php` | ✓ | ✓ | — | 3 | 2 (APPROVED, REJECTED) | B — Approval |
| 13 | `PartnershipStatus` | Partners | `app/Partners/Partnership/Enums/PartnershipStatus.php` | ✓ | ✓ | — | 3 | 2 (EXPIRED, TERMINATED) | C — Linear + cancel |
| 14 | `CertificateStatus` | Certification | `app/Certification/Certificate/Enums/CertificateStatus.php` | ✓ | ✓ | — | 2 | 1 (REVOKED) | E — Binary |
| 15 | `ReportStatus` | Reports | `app/Reports/Report/Enums/ReportStatus.php` | ✓ | ✓ | — | 2 | 1 (FINALIZED) | E — Binary |

### Standalone Status (Custom transition logic, no StatusEnum)

| # | Enum | Module | Path | L | S | C | States | Notes |
|---|------|--------|------|---|---|---|--------|-------|
| 16 | `AnnouncementStatus` | SysAdmin | `app/SysAdmin/Announcement/Enums/AnnouncementStatus.php` | ✓ | — | — | 3 | Own `canTransitionTo(self)`, no `isTerminal()`/`validTransitions()` |

### Label Enums (implement `LabelEnum` only)

| # | Enum | Module | Path | L | S | C | States | Notable Methods |
|---|------|--------|------|---|---|---|--------|-----------------|
| 17 | `Role` | Auth | `app/Auth/Permissions/Enums/Role.php` | ✓ | — | — | 7 | `userRoles()`, `functionalRoles()`, `resolvesTo()`, `is()` |
| 18 | `SettingType` | Settings | `app/Settings/Enums/SettingType.php` | ✓ | — | — | 7 | `detect()`, `cast()`, `values()` |
| 19 | `SettingGroup` | Settings | `app/Settings/Enums/SettingGroup.php` | ✓ | — | — | 7 | `default()` |
| 20 | `StructuralPosition` | User | `app/User/Enums/StructuralPosition.php` | ✓ | — | — | 6 | — |
| 21 | `EvaluationCategory` | Evaluation | `app/Evaluation/Enums/EvaluationCategory.php` | ✓ | — | — | 5 | `defaultCriteria()` |
| 22 | `EmploymentStatus` | User | `app/User/Enums/EmploymentStatus.php` | ✓ | — | — | 5 | `options()` |
| 23 | `DocumentCategory` | Document | `app/Document/Enums/DocumentCategory.php` | ✓ | — | — | 5 | — |
| 24 | `IncidentType` | Incident | `app/Incident/IncidentReport/Enums/IncidentType.php` | ✓ | — | — | 5 | — |
| 25 | `AuditCategory` | Core | `app/Core/Enums/AuditCategory.php` | ✓ | — | — | 5 | `isCritical()` |
| 26 | `IncidentSeverity` | Incident | `app/Incident/IncidentReport/Enums/IncidentSeverity.php` | ✓ | — | — | 4 | — |
| 27 | `EvaluatorRole` | Evaluation | `app/Evaluation/Enums/EvaluatorRole.php` | ✓ | — | — | 4 | — |
| 28 | `AbsenceReasonType` | Journals | `app/Journals/AbsenceRequest/Enums/AbsenceReasonType.php` | ✓ | — | — | 4 | `requiresAttachment()` |
| 29 | `BloodType` | User | `app/User/Enums/BloodType.php` | ✓ | — | — | 4 | `label()` returns raw value |
| 30 | `AuditStatus` | Core | `app/Core/Enums/AuditStatus.php` | ✓ | — | — | 3 | `symbol()` |
| 31 | `SupervisionType` | Guidance | `app/Guidance/SupervisionLog/Enums/SupervisionType.php` | ✓ | — | — | 3 | — |
| 32 | `InternshipGroupRole` | Program | `app/Program/InternshipGroup/Enums/InternshipGroupRole.php` | ✓ | — | — | 3 | — |
| 33 | `Gender` | User | `app/User/Enums/Gender.php` | ✓ | — | — | 2 | — |
| 34 | `CsvRowResult` | Core | `app/Core/Enums/CsvRowResult.php` | ✓ | — | — | 2 | — |
| 35 | `MediaCollection` | Settings | `app/Settings/Enums/MediaCollection.php` | — | — | — | 2 | No contracts — pure value store |

### Module Distribution

| Module | Status Enums | Label Enums | Total |
|--------|-------------|-------------|-------|
| Core | 0 | 3 | 3 |
| Auth | 0 | 1 | 1 |
| User | 1 | 4 | 5 |
| Settings | 0 | 3 | 3 |
| Journals | 3 | 1 | 4 |
| Assignment | 2 | 0 | 2 |
| Guidance | 1 | 1 | 2 |
| Enrollment | 3 | 0 | 3 |
| Program | 1 | 1 | 2 |
| Partners | 1 | 0 | 1 |
| Incident | 1 | 2 | 3 |
| Evaluation | 0 | 2 | 2 |
| Certification | 1 | 0 | 1 |
| Reports | 1 | 0 | 1 |
| Document | 0 | 1 | 1 |
| SysAdmin | 1 | 0 | 1 |
| **Total** | **16** | **19** | **35** |

---

## 11. Testing Enums

### Test File Location

```
tests/Unit/{Module}/{SubModule}/Enums/{Name}Test.php
```

E.g. `tests/Unit/Journals/Logbook/Enums/LogbookStatusTest.php`

### Test Structure

Enum tests follow a consistent pattern of four test groups per `StatusEnum`:

#### 1. Cases & Values

```php
test('logbook status has all cases', function () {
    expect(LogbookStatus::cases())->toHaveCount(4);
    expect(LogbookStatus::DRAFT->value)->toBe('draft');
    expect(LogbookStatus::SUBMITTED->value)->toBe('submitted');
    expect(LogbookStatus::VERIFIED->value)->toBe('verified');
    expect(LogbookStatus::REVISION_REQUIRED->value)->toBe('revision_required');
});
```

#### 2. Labels

```php
test('logbook status labels are non-empty', function () {
    foreach (LogbookStatus::cases() as $s) {
        expect($s->label())->toBeString()->not->toBeEmpty();
    }
});
```

#### 3. Business Logic Methods

```php
test('only verified is finalized', function () {
    expect(LogbookStatus::VERIFIED->isFinalized())->toBeTrue();
    expect(LogbookStatus::DRAFT->isFinalized())->toBeFalse();
    expect(LogbookStatus::SUBMITTED->isFinalized())->toBeFalse();
    expect(LogbookStatus::REVISION_REQUIRED->isFinalized())->toBeFalse();
});

test('submitted and revision required require action', function () {
    expect(LogbookStatus::SUBMITTED->requiresAction())->toBeTrue();
    expect(LogbookStatus::REVISION_REQUIRED->requiresAction())->toBeTrue();
    expect(LogbookStatus::DRAFT->requiresAction())->toBeFalse();
    expect(LogbookStatus::VERIFIED->requiresAction())->toBeFalse();
});
```

#### 4. Terminal & Transitions

```php
test('only verified is terminal', function () {
    expect(LogbookStatus::VERIFIED->isTerminal())->toBeTrue();
    expect(LogbookStatus::DRAFT->isTerminal())->toBeFalse();
    expect(LogbookStatus::SUBMITTED->isTerminal())->toBeFalse();
    expect(LogbookStatus::REVISION_REQUIRED->isTerminal())->toBeFalse();
});

test('valid transitions', function () {
    expect(LogbookStatus::DRAFT->validTransitions())->toContain(LogbookStatus::SUBMITTED);
    expect(LogbookStatus::SUBMITTED->validTransitions())->toContain(LogbookStatus::VERIFIED, LogbookStatus::REVISION_REQUIRED);
    expect(LogbookStatus::REVISION_REQUIRED->validTransitions())->toContain(LogbookStatus::DRAFT);
    expect(LogbookStatus::VERIFIED->validTransitions())->toBe([]);
});

test('can transition correctly', function () {
    expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::SUBMITTED))->toBeTrue();
    expect(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::VERIFIED))->toBeTrue();
    expect(LogbookStatus::SUBMITTED->canTransitionTo(LogbookStatus::REVISION_REQUIRED))->toBeTrue();
    expect(LogbookStatus::REVISION_REQUIRED->canTransitionTo(LogbookStatus::DRAFT))->toBeTrue();
    expect(LogbookStatus::DRAFT->canTransitionTo(LogbookStatus::VERIFIED))->toBeFalse();
    expect(LogbookStatus::VERIFIED->canTransitionTo(LogbookStatus::DRAFT))->toBeFalse();
});
```

#### 5. Type Safety (cross-enum rejection)

```php
test('returns false for wrong enum type', function () {
    $mock = new class implements StatusEnum
    {
        public function label(): string { return 'mock'; }
        public function isTerminal(): bool { return false; }
        public function canTransitionTo(StatusEnum $target): bool { return false; }
        public function validTransitions(): array { return []; }
    };

    expect(AccountStatus::ACTIVATED->canTransitionTo($mock))->toBeFalse();
});
```

#### 6. Color (if ColorableEnum)

```php
test('color returns string for each status', function () {
    foreach (AccountStatus::cases() as $status) {
        expect($status->color())->toBeString();
    }
});
```

### Label-Only Enum Tests

Simpler — just cases and labels:

```php
test('evaluation category has all expected cases', function () {
    expect(EvaluationCategory::cases())->toHaveCount(5);
    expect(EvaluationCategory::MENTOR->value)->toBe('mentor');
});

test('evaluation category label returns non-empty string', function () {
    foreach (EvaluationCategory::cases() as $category) {
        expect($category->label())->toBeString()->not->toBeEmpty();
    }
});
```

Plus any business logic specific to the enum:

```php
test('default criteria returns array for each category', function () {
    $criteria = EvaluationCategory::MENTOR->defaultCriteria();
    expect($criteria)->toBeArray();
    expect($criteria)->toHaveKey('communication');
});
```

### Running Tests

```bash
php artisan test --compact --filter=LogbookStatus
php artisan test --compact --filter=AccountStatus
```
