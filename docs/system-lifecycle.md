# System Lifecycle

This document describes the PKL (Praktik Kerja Lapangan / internship) management lifecycle from the **system's perspective**. Where the [User Flow](user-flow.md) focuses on actors and their actions, this document focuses on how the system orchestrates state, enforces rules, manages data, and connects events seamlessly.

**Every event links to its detailed document in `docs/lifecycles/`.**

---

## Table of Contents

1. [The System as a State Machine](#1-the-system-as-a-state-machine)
2. [Entity State Universe](#2-entity-state-universe)
3. [Phase 0: System Bootstrap](#3-phase-0-system-bootstrap)
4. [Phase 1: Foundation](#4-phase-1-foundation)
5. [Phase 2: Internship Planning](#5-phase-2-internship-planning)
6. [Phase 3: Registration Engine](#6-phase-3-registration-engine)
7. [Phase 4: Operations Engine](#7-phase-4-operations-engine)
8. [Phase 5: Assessment Engine](#8-phase-5-assessment-engine)
9. [Phase 6: Closure Engine](#9-phase-6-closure-engine)
10. [Phase 7: Archival Engine](#10-phase-7-archival-engine)
11. [Cross-Cutting Systems](#11-cross-cutting-systems)
12. [Complete State Transition Map](#12-complete-state-transition-map)

---

## 1. The System as a State Machine

Internara is fundamentally a **distributed state machine**. Every major entity (internship, registration, user, logbook, submission) follows a defined state lifecycle with validated transitions, guards, and terminal states.

### State Machine Architecture

```
                    ┌─────────────────────────────────────┐
                    │         Application Layer            │
                    │  (Livewire Components + Actions)     │
                    └──────────────┬──────────────────────┘
                                   │ delegates to
                                   ▼
                    ┌─────────────────────────────────────┐
                    │         Action Layer                 │
                    │  (State transitions + validation)    │
                    └──────────────┬──────────────────────┘
                                   │ executes on
                                   ▼
                    ┌─────────────────────────────────────┐
                    │         Model Layer                  │
                    │  (Eloquent + Enum + Entity)          │
                    │                                     │
                    │  Enum: defines valid transitions     │
                    │  Entity: encapsulates business rules │
                    │  Model: persists state               │
                    └──────────────┬──────────────────────┘
                                   │ persists to
                                   ▼
                    ┌─────────────────────────────────────┐
                    │         Database                     │
                    │  (all tables, UUID keys)             │
                    └─────────────────────────────────────┘
```

### Transition Validation Chain

Every state change follows this chain:

```
1. User Action (Livewire/Controller)
       │
2. Action Layer (e.g., UpdateInternshipAction)
       │
3. Enum Transition Check (e.g., InternshipStatus::canTransitionTo())
       │  └── If invalid → throw RuntimeException → UI shows error
       │
4. Entity Business Rule (e.g., RegistrationState::canBeApproved())
       │  └── If invalid → action aborts with explanation
       │
5. Model Persistence
       │
6. Audit Log (activity_log)
       │
7. Side Effects (notifications, events, cache invalidation)
```

### Terminal States

The system defines these terminal (irreversible) states:

| Entity | Terminal States | Meaning |
|---|---|---|
| **Internship** | `COMPLETED`, `CANCELLED` | Period done, no further activities |
| **Account** | `ARCHIVED`, `PROTECTED` | User cannot log in or transition |
| **Logbook Entry** | `VERIFIED` | Entry finalized by teacher |
| **Submission** | `VERIFIED`, `GRADED` | Assignment work scored/approved |
| **Assessment** | (finalized) | `finalized_at` set, scores locked |

---

## 2. Entity State Universe

The system manages state across 8 interrelated entities. Each entity's state machine is defined in an Enum class with transition rules:

| Entity | Enum / Status Source | States | Transitions defined in |
|---|---|---|---|
| **User Account** | `AccountStatus` | 8 states | `AccountStatus::validTransitions()` |
| **Internship** | `InternshipStatus` | 5 states | `InternshipStatus::canTransitionTo()` |
| **Registration** | Spatie Model Status | pending → active → completed | `RegistrationState` entity |
| **Logbook** | `LogbookStatus` | 4 states | `LogbookStatus::isFinalized()`, `requiresAction()` |
| **Assignment** | `AssignmentStatus` | 3 states | `AssignmentStatus::isActive()` |
| **Submission** | `SubmissionStatus` | 4 states | Mirror of LogbookStatus |
| **Attendance** | `AttendanceStatus` | 6 values (not a state machine) | `isOnTime()`, `isExcused()` |
| **Assessment** | `AssessmentResult` | open → finalized | `AssessmentResult::isFinalized()` |

### State Coupling Map

States across entities are not independent — they form a coupled system:

```
Internship:DRAFT
    └── Registration creation blocked (not accepting)
    └── Placements can be defined (preparation)

Internship:PUBLISHED
    └── Registration:PENDING can be created (if within window)
    └── InternshipPeriod::isAcceptingRegistrations() = true

Internship:ACTIVE
    └── Registration:ACTIVE (operational)
    │       └── Logbook:DRAFT → SUBMITTED → VERIFIED
    │       └── Attendance:PRESENT/LATE/etc
    │       └── Submission:DRAFT → SUBMITTED → VERIFIED
    │       └── Supervision:IN_PROGRESS → SUBMITTED → VERIFIED
    │       └── Assessment:open → FINALIZED
    │
    └── Registration:PENDING → ACTIVE (if still within window)

Internship:COMPLETED
    └── All Registration activities locked
    └── No state transitions on child entities
    └── Assessment must be FINALIZED first (recommended)
```

---

## 3. Phase 0: System Bootstrap

**Events:** [`system-installation`](lifecycles/system-installation.md) → [`setup-wizard`](lifecycles/setup-wizard.md)

The system starts in a "clean slate" state — no database, no configuration, no users.

### Boot Sequence

```
[No state] → Provisioning → Seeded → Configured → Locked
```

#### Step 1: Provisioning (`ProvisionSystemAction`)

The system:
1. Verifies server environment (PHP 8.4+, extensions, writable paths)
2. Generates application key (if not present)
3. Runs 57 migrations creating all tables
4. Seeds 5 roles with permissions via `RolePermissionSeeder`
5. Seeds 18 default settings via `AppSettingSeeder`
6. Creates storage symlink (`public/storage → storage/app/public`)
7. Clears all caches

**Post-state:** Database has schema + seed data. No `.installed` lock file.

#### Step 2: Setup Token Generation

The system generates a one-time setup token:
- Encrypted with app key
- Stored in `setups` table with expiry
- Embedded in a one-time URL

#### Step 3: Wizard Finalization (`FinalizeSetupAction`)

The system:
1. Writes `.installed` lock file to project root
2. Encrypts and stores a 64-character recovery key
3. Invalidates the setup token (prevents re-access)
4. Clears setup session data
5. Dispatches `SetupFinalized` domain event
6. Returns the recovery key in plaintext (shown once, never retrievable)

**Post-state:** System is "installed" — `.installed` exists, setup routes return 404.

### System Guard: `.installed` Lock File

The `.installed` file at the project root is the system's "is this installed" flag:

- Created by `FinalizeSetupAction`
- Checked by `AppMetadata::isInstalled()` before reading settings
- Blocks the setup wizard route (`RequireSetupAccessMiddleware`)
- Bypassed during testing (`TestCase::setUp()` creates it)
- Removed by `php artisan setup:reset`

---

## 4. Phase 1: Foundation

**Events:** [`school-configuration`](lifecycles/school-configuration.md) → [`user-creation`](lifecycles/user-creation.md)

### School & Academic Structure

The system enforces:

| Rule | Mechanism |
|---|---|
| **Single school record** | `SchoolState::canBeCreated()` — blocks duplicate schools |
| **One active academic year** | `ActivateAcademicYearAction` toggles the active flag, deactivates others |
| **Department integrity** | Foreign keys prevent orphaned departments with linked records |
| **Academic year default** | `CreateInternshipAction` auto-assigns active academic year if none specified |

### Settings Resolution Chain

When the system resolves `setting('key')`:

```
1. Runtime overrides      (testing only, in-memory)
2. AppInfo metadata        (composer.json for app_name, version, etc.)
3. Database (cached)       (settings table, cached forever in settings.{key})
4. Laravel config          (config('key') fallback)
5. Default value           (provided by caller)
```

Cache invalidation:
- Single key: `Settings::forget($key)` clears key + group + all caches
- Batch update: `SetSettingAction::executeBatch()` invalidates all affected keys
- Full flush: `php artisan cache:clear`

### User & Role System

The system manages users through Spatie Laravel Permission:

- **5 roles**: `super_admin`, `admin`, `teacher`, `student`, `supervisor`
- **Role assignment**: via Spatie's `assignRole()` method
- **Role checking**: `$user->hasRole()`, `$user->hasAnyRole()`, middleware `role:...`
- **Super Admin bypass**: `Gate::before` grants all permissions to super_admin

#### User Creation Transaction (`CreateUserAction`)

The system wraps user creation in a single atomic transaction:

```
START TRANSACTION
  INSERT INTO users (id, name, email, password, ...)
  INSERT INTO profiles (user_id, ...)          [if profile data provided]
  CALL assignRole($roles)                       [Spatie permission sync]
  CALL setStatus($status)                       [Spatie model-status]
  INSERT INTO activity_log (action='user_created')
COMMIT
```

If any step fails, all changes are rolled back.

---

## 5. Phase 2: Internship Planning

**Events:** [`internship-creation`](lifecycles/internship-creation.md) → [`company-placement`](lifecycles/company-placement.md)

### Internship Status Machine

The system manages 5 states with validated transitions:

```
DRAFT ──► PUBLISHED ──► ACTIVE ──► COMPLETED
  │                        │
  └────► CANCELLED ◄───────┘
```

**Transition validation** (`InternshipStatus::canTransitionTo()`):

```
DRAFT     → [PUBLISHED, CANCELLED]
PUBLISHED → [ACTIVE, CANCELLED]
ACTIVE    → [COMPLETED, CANCELLED]
CANCELLED → []  (terminal)
COMPLETED → []  (terminal)
```

**Registration acceptance** (`InternshipPeriod::isAcceptingRegistrations()`):
- Only PUBLISHED and ACTIVE internships accept registrations
- AND current date must be within the registration window

### Placement Capacity System

The system enforces capacity through the `PlacementCapacity` entity:

```
Placement: quota=10, filled_quota=0  →  10 available slots
Placement: quota=10, filled_quota=10 →  FULL (isFull() = true)
```

Capacity is:
- **Incremented** when a registration transitions to ACTIVE
- **Checked** before any registration activation (`hasAvailableSlots()`)
- **Protected** against reduction below current fill level (update validation)
- **Never decremented** automatically (manual adjustment via update)

---

## 6. Phase 3: Registration Engine

**Event:** [`student-registration`](lifecycles/student-registration.md)

The registration system provides three paths, each handled by a different action:

```
┌─────────────────────────────────────────────────────────────────┐
│                    REGISTRATION ENGINE                           │
│                                                                  │
│  ┌───────────────────┐  ┌─────────────────┐  ┌──────────────┐  │
│  │ Path A: Apply     │  │ Path B: Self    │  │ Path C:      │  │
│  │ ApplyAccountAction│  │ RegisterIntern- │  │ DirectPlace- │  │
│  │ → VerifyAccountAct│  │ shipAction →    │  │ mentAction   │  │
│  │ ion               │  │ VerifyRegistrat-│  │              │  │
│  │                   │  │ ionAction       │  │              │  │
│  └───────────────────┘  └─────────────────┘  └──────────────┘  │
│           │                      │                    │         │
│           └──────────┬───────────┴────────────────────┘         │
│                      ▼                                          │
│           ┌──────────────────────┐                               │
│           │   Registration:ACTIVE│                               │
│           │   (with placement,   │                               │
│           │    mentors, dates)   │                               │
│           └──────────────────────┘                               │
└─────────────────────────────────────────────────────────────────┘
```

### Path A: Account Application (System Orchestration)

```
ApplyAccountAction                    VerifyAccountAction
  ┌──────────────┐                      ┌──────────────────┐
  │ Validates     │                      │ Checks capacity  │
  │ email unique  │                      │ Creates User     │
  │ Prevents dup  │                      │ Creates Profile  │
  │ Creates Appli-│                      │ Creates Mentee   │
  │ cation:PENDING│                      │ Creates Registra-│
  └──────┬───────┘                      │ tion:ACTIVE      │
         │                               │ Attaches mentors │
         ▼                               │ Increments quota │
  AccountApplication                     │ Sends notif      │
  (status: pending)                      └──────────────────┘
```

### Path B: Self-Registration (System Orchestration)

```
RegisterInternshipAction                VerifyRegistrationAction
  ┌────────────────┐                      ┌──────────────────┐
  │ Checks status  │                      │ Validates pending│
  │ Creates Mentee │                      │ Checks capacity  │
  │ Creates Regis- │                      │ Assigns placement│
  │ tration:PENDING│                      │ Sets dates       │
  │ Sends notif    │                      │ Transitions ACT  │
  └──────┬─────────┘                      │ INCREMENT QUOTA  │
         │                                │ Attaches mentors │
         ▼                                │ Notifies student │
  Registration                            └──────────────────┘
  (status: pending)
```

### Path C: Direct Placement (System Orchestration)

```
DirectPlacementAction
  ┌──────────────────┐
  │ Checks capacity  │
  │ Creates Mentee   │
  │ Creates Registration:ACTIVE (skips pending)
  │ Increments quota │
  │ Attaches mentors │
  │ Logs audit       │
  └──────────────────┘
```

### System Guards (Registration)

| Guard | Implementation | Failure Behavior |
|---|---|---|
| **No duplicate active registration** | `RegisterInternshipAction` checks for existing active/pending | Error: "Already registered" |
| **Placement capacity** | `PlacementCapacity::hasAvailableSlots()` | Error: "Placement is full" |
| **Registration must be pending** | `VerifyRegistrationAction` validates status | Error: "Not in pending state" |
| **Registration window** | `InternshipPeriod::isAcceptingRegistrations()` | Error: "Not accepting registrations" |
| **Duplicate application** | `ApplyAccountAction` checks email uniqueness | Error: "Application already exists" |
| **Mentor assignment** | `registration_mentor` pivot attached on activation | System warning if no mentors |

---

## 7. Phase 4: Operations Engine

**Events:** [`logbook-workflow`](lifecycles/logbook-workflow.md), [`attendance-tracking`](lifecycles/attendance-tracking.md), [`assignment-workflow`](lifecycles/assignment-workflow.md), [`supervision-process`](lifecycles/supervision-process.md)

During the operations phase, four parallel activity streams are managed by the system. Each has its own state machine but shares the common precondition: **Registration must be ACTIVE**.

### Concurrency Model

```
                    ┌─────────────────────────────────────┐
                    │       Registration:ACTIVE            │
                    └─────────────────────────────────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
┌───────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Logbook      │    │   Attendance    │    │  Assignments    │
│  (daily)      │    │   (daily)       │    │  (per task)     │
├───────────────┤    ├─────────────────┤    ├─────────────────┤
│ DRAFT →       │    │ PRESENT         │    │ DRAFT →         │
│ SUBMITTED →   │    │ LATE            │    │ SUBMITTED →     │
│ VERIFIED      │    │ ABSENT          │    │ VERIFIED/GRADED │
│ (or REVISION) │    │ PERMISSION/SICK │    │ (or REVISION)   │
└───────────────┘    └─────────────────┘    └─────────────────┘

┌─────────────────┐
│  Supervision    │
│  (per session)  │
├─────────────────┤
│ GUIDANCE: auto- │
│ verified if by  │
│ teacher         │
│ MENTORING:      │
│ needs teacher   │
│ verification    │
└─────────────────┘
```

### Logbook State Machine

The `LogbookState` entity controls what operations are allowed:

```
DRAFT ──► SUBMITTED ──► VERIFIED (terminal)
            │
            └──► REVISION_REQUIRED ──► DRAFT (student edits, resubmits)
```

| State | Student can edit | Teacher can verify | Teacher can request revision |
|---|---|---|---|
| DRAFT | Yes | No | No |
| SUBMITTED | No | Yes | Yes |
| VERIFIED | No | No | No |
| REVISION_REQUIRED | Yes | No | No |

**Daily integrity**: System enforces one SUBMITTED entry per day per student. DRAFT can overwrite itself.

### Assignment & Submission State Machine

```
Assignment: DRAFT ──► PUBLISHED ──► CLOSED

Submission: DRAFT ──► SUBMITTED ──► VERIFIED (terminal)
                        │               or
                        └──► REVISION_REQUIRED ──► DRAFT (resubmit)
```

The `GradeSubmissionAction` and `VerifySubmissionAction` overlap in functionality:
- Both can transition SUBMITTED → VERIFIED
- `GradeSubmissionAction` additionally records a numeric score
- `VerifySubmissionAction` marks as verified without score (pass/fail)

### Supervision State Machine

```
IN_PROGRESS ──► SUBMITTED ──► (if by teacher) ──► COMPLETED (auto-verified)
                              ──► (if by supervisor) ──► SUBMITTED
                                                          │
                                                          ▼
                                                      VERIFIED (by teacher)
```

**Auto-type determination** (`CreateSupervisionLogAction`):
```
if auth()->id() === registration.teacher_id → type = 'guidance' → auto-verified
if auth()->id() === registration.mentor_id  → type = 'mentoring' → needs verification
```

### Attendance Value Set

Attendance uses a fixed set of 6 values, not a state machine:

```
PRESENT | LATE | EARLY_OUT | ABSENT | PERMISSION | SICK
```

The `AttendanceStatus` enum provides:
- `isOnTime()`: PRESENT only
- `isExcused()`: PERMISSION, SICK

### Cross-Entity Preconditions (Operations)

The system checks these conditions before allowing any operation:

| Operation | Requires | Entity | Method |
|---|---|---|---|
| Create logbook | Active registration within period | `MenteeState` | `canSubmitLogbook()` |
| Record attendance | Active registration within period | `MenteeState` | `canClockIn()` |
| Submit assignment | Active registration | `MenteeState` | `canSubmitAssignment()` |
| Create supervision | Mentor assigned to registration | Registration | mentor pivot exists |
| Verify supervision | School teacher role | `MentorRole` | `canVerifySupervisionLog()` |
| Grade submission | Teacher or supervisor role | `MentorRole` | `canGradeSubmission()` |

---

## 8. Phase 5: Assessment Engine

**Events:** [`assessment-scoring`](lifecycles/assessment-scoring.md), [`mentor-evaluation`](lifecycles/mentor-evaluation.md)

### Rubric Structure

The system manages a three-level rubric hierarchy:

```
Rubric
  └── Competencies (weighted, evaluator-role-assigned)
        └── Indicators (weighted, max-scored)
```

### Assessment Initialization

When a user opens the assessment grading page, the system runs `InitializeAssessmentAction`:

1. Loads the registration with internship
2. Finds the active rubric for the internship:
   ```
   Rubric::where('internship_id', $internshipId)
       ->orWhereNull('internship_id')     // global rubrics
       ->where('is_active', true)
       ->first()
   ```
3. Creates or finds an Assessment record:
   ```
   Assessment::firstOrCreate(
       ['registration_id' => $registrationId],
       ['rubric_id' => $rubric->id, 'type' => 'final']
   )
   ```

### Role-Based Scoring Visibility

The system filters competencies by the current user's role:

```
auth()->user()->hasRole('super_admin') || hasRole('admin')
  └── sees ALL competencies (admin override)

auth()->user()->hasRole('teacher')
  └── sees competencies where evaluator_role = 'teacher'
  └── AND must be assigned as mentor to the student

auth()->user()->hasRole('supervisor')
  └── sees competencies where evaluator_role = 'supervisor'
  └── AND must be assigned as mentor
```

### Score Calculation Engine (`FinalizeAssessmentAction`)

The system computes the final score using weighted normalization:

```
For each indicator i in competency:
    normalized_i = (score_i / max_score_i) × 100
    competency_score += normalized_i × (weight_i / 100)

For each competency c:
    total_weighted += competency_score_c × (weight_c / 100)

Final score = total_weighted (bounded 0-100)
```

### Finalization Guard

Once finalized (`'finalized_at' !== null`):
- No further score updates allowed
- Assessment is read-only
- This is enforced by `AssessmentResult::isFinalized()`

### Mentor Evaluation

The system stores evaluations as separate records (not part of assessments):

```
Evaluation
├── mentor_id (who is evaluated)
├── evaluator_id (who submitted)
├── criteria_scores (JSON: communication, responsiveness, guidance_quality)
├── overall_score (nullable, 0-100)
├── feedback (nullable)
└── timestamps
```

---

## 9. Phase 6: Closure Engine

**Event:** [`period-closing`](lifecycles/period-closing.md)

### Internship Closure

The system transitions the internship to COMPLETED via `UpdateInternshipAction`:

```
ACTIVE → COMPLETED
```

**System effects after closure:**

| Subsystem | Behavior |
|---|---|
| **Logbook** | `CreateLogbookAction` blocked — no new entries |
| **Attendance** | `CreateAttendanceAction` blocked — no new records |
| **Assignments** | Student submission blocked — no new submissions |
| **Supervision** | `CreateSupervisionLogAction` blocked — no new logs |
| **Assessment** | Already finalized or left as-is (no new scoring) |
| **Registration** | No new registrations accepted |
| **Placement** | Quota locked, no new placements |

### Batch Closure Engine

`BatchUpdateInternshipStatusAction` performs mass updates:

```
Input: filtered query (by academic year, date range, etc.)
Process: UPDATE internships SET status = 'completed' WHERE status = 'active' AND [filters]
Output: count of affected records
```

Only ACTIVE internships are affected. The query filter is built from the current filter state of the Livewire component.

### Pre-Close System Integrity Check (Recommended)

The system does not enforce a mandatory pre-close check, but best practice is to verify:

| Integrity check | Why |
|---|---|
| All assessments finalized | Prevents incomplete grading |
| All submissions graded | Prevents incomplete feedback |
| All supervision logs verified | Ensures mentor documentation is complete |
| Attendance reconciled | Ensures accurate attendance records |

---

## 10. Phase 7: Archival Engine

**Event:** [`account-archiving`](lifecycles/account-archiving.md)

### Account Archival

The system transitions user accounts to ARCHIVED:

```
[VERIFIED | SUSPENDED | INACTIVE | RESTRICTED] → ARCHIVED
```

ARCHIVED is terminal — `AccountStatus::isTerminal()` returns `true`.

**System effects:**

| Aspect | Behavior |
|---|---|
| **Login** | `allowsLogin()` = false — blocked at authentication layer |
| **Data** | All related records preserved (logbooks, submissions, etc.) |
| **Transitions** | `canTransitionTo()` = false — no further status changes |
| **Visibility** | Hidden from active user lists but searchable in archives |

### The PROTECTED Guard

Super Admin accounts use the PROTECTED status:
- `isTerminal()` = true (same as ARCHIVED)
- No transitions into or out of PROTECTED
- Cannot be archived or deleted
- Ensures at least one super admin always exists

### GDPR Deletion Log

The system maintains a `gdpr_deletion_logs` table for compliance:

```
gdpr_deletion_logs
├── user_id
├── requested_by
├── processed_at
├── data_scope (what was deleted/archived)
└── audit_reference (link to activity_log)
```

---

## 11. Cross-Cutting Systems

### Event System

The system dispatches domain events for key state transitions:

| Event | Trigger | Listeners |
|---|---|---|
| `InternshipCreated` | Internship created | (reserved for future: notifications, integrations) |
| `SetupFinalized` | Setup wizard completes | (reserved: post-setup actions) |

### Notification System

The system sends notifications for these events:

| Notification | Trigger | Channel |
|---|---|---|
| `WelcomeNotification` | Account created via application | Mail |
| `AdminRecoveredNotification` | Admin account recovered | Mail |
| `RegistrationNotification` | Student registers for internship | Mail |
| `AssignmentNotification` | Assignment published | Mail, broadcast, database |
| `SubmissionFeedbackNotification` | Submission graded/verified | Mail, broadcast, database |
| `AccountStatusNotification` | Account status changed | Mail, broadcast, database |
| `ReportGeneratedNotification` | Report generated | Mail, broadcast, database |

### Audit Logging

Every state-changing action logs to `activity_log` via `LogAuditAction`:

```
activity_log
├── log_name = business domain (Internship, User, Assessment, etc.)
├── description = action description
├── subject_type / subject_id = target entity
├── causer_type / causer_id = who performed the action
├── properties = contextual payload (JSON)
├── event = event type (created, updated, deleted, etc.)
└── batch_uuid = groups related activities
```

### Caching Strategy

| What | Cache Key | TTL | Invalidation |
|---|---|---|---|
| Settings | `settings.{key}` | Forever | On setting update |
| Settings group | `settings.group.{name}` | Forever | On group update |
| All settings | `settings.all` | Forever | On any setting update |
| Dashboard stats | `managerial_stats` | 10 minutes | Manual |

### Authorization System

The system has a layered authorization model:

```
Layer 1: Route Middleware
  auth → role:super_admin|admin → pipe-delimited OR

Layer 2: Policy / Gate
  Gate::authorize('viewAny', Model::class)
  $this->authorize('update', $user)
  Gate::before → super_admin bypass

Layer 3: Action-level
  Authority verification within Action::execute()
  Owner checks, role checks, status checks
```

---

## 12. Complete State Transition Map

### Entity: Internship (`InternshipStatus`)

```
DRAFT ───────────────────────────────────────────────────────────┐
  │  [created via CreateInternshipAction]                         │
  │                                                               │
  ├──► PUBLISHED  [UpdateInternshipAction]                        │
  │      │                                                        │
  │      ├──► ACTIVE  [UpdateInternshipAction]                    │
  │      │      │                                                 │
  │      │      ├──► COMPLETED  [UpdateInternshipAction]          │
  │      │      │              [BatchUpdateInternshipStatusAction]│
  │      │      │                                                 │
  │      │      └──► CANCELLED  [UpdateInternshipAction]          │
  │      │                                                        │
  │      └──► CANCELLED  [UpdateInternshipAction]                 │
  │                                                                │
  └──► CANCELLED  [UpdateInternshipAction]                        │
                                                                   │
  COMPLETED  → (terminal, no transitions out)                      │
  CANCELLED  → (terminal, no transitions out)                     │
└──────────────────────────────────────────────────────────────────┘
```

### Entity: Registration

```
PENDING ──► ACTIVE ──► COMPLETED
  │            │
  │ created    │ created
  │ via:       │ via:
  │ Register-  │ DirectPlacementAction
  │ Internship │ VerifyAccountAction
  │ Action     │ VerifyRegistrationAction
  │            │
  ▼            ▼
  (no terminal states defined as enum — uses Spatie model-status)
```

### Entity: User Account (`AccountStatus`)

```
PROVISIONED ──► ACTIVATED ──► VERIFIED ──► [RESTRICTED | SUSPENDED | INACTIVE]
  │                │              │                        │
  │                │              ├────────────────────────┘
  │                │              ▼
  │                │         ARCHIVED (terminal)
  │                │
  │                └──► [SUSPENDED │ ARCHIVED]
  │
  └──► [SUSPENDED]

PROTECTED  → (immutable, no transitions in or out)
ARCHIVED   → (terminal, no transitions out)
```

### Entity: Logbook Entry (`LogbookStatus`)

```
DRAFT ──► SUBMITTED ──► VERIFIED (terminal)
              │
              └──► REVISION_REQUIRED ──► DRAFT (resubmit loop)
```

### Entity: Assignment

```
DRAFT ──► PUBLISHED ──► CLOSED
```

### Entity: Submission (`SubmissionStatus`)

```
DRAFT ──► SUBMITTED ──► VERIFIED (terminal)
              │              or
              │         GRADED (terminal)
              │
              └──► REVISION_REQUIRED ──► DRAFT (resubmit loop)
```

### Entity: Assessment

```
OPEN ──► FINALIZED (terminal, irreversible)
```

### Entity: Supervision Log

```
IN_PROGRESS ──► SUBMITTED ──► (if by teacher) COMPLETED (auto-verified)
                              ──► (if by supervisor) SUBMITTED
                                                      │
                                                      ▼
                                                  VERIFIED (by teacher)
```

### Cross-Entity Lifecycle Timeline

```
Time ──────────────────────────────────────────────────────────────────►

Phase 0:     [Install]────[Setup Wizard]────────────────────────────────
Phase 1:     [School Config]────[User Creation]─────────────────────────
Phase 2:     [Internship Creation]────[Company/Placement]───────────────
Phase 3:     [Student Registration]─────────────────────────────────────
Phase 4:     [Logbook][Attendance][Assignments][Supervision]━━━━━━━━━━━
Phase 5:     [Assessment]────[Mentor Eval]──────────────────────────────
Phase 6:     [Period Close]─────────────────────────────────────────────
Phase 7:     [Archiving]────────────────────────────────────────────────
                │                                                    │
                ▼                                                    ▼
           Start of cycle                                       End of cycle
           (Phase 2 repeats)                                    (data preserved)
```

---

## References

| Document | Focus |
|---|---|
| [User Flow](user-flow.md) | Actor-centric flow — who does what |
| [Architecture](architecture.md) | Code structure and layering |
| [Lifecycle Events](lifecycles/system-installation.md) | Detailed event specifications |
