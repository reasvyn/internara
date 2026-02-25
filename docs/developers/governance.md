# System Governance: Security and Logic Standards

This document serves as the authoritative record for both security governance (Authentication &
Authorization) and systemic logic orchestration patterns within the Internara project.

---

## 1. Identity & Access Management: RBAC Standards

This section formalizes the **Identity and Access Management (IAM)** framework for the Internara
project, adhering to **ISO/IEC 27001** (Access Control) and **ISO/IEC 29146** (Access Control
Framework). It defines the protocols for **Role-Based Access Control (RBAC)** to ensure system
security, data confidentiality, and administrative accountability.

> **Governance Mandate:** All roles and permissions must be traceable to the User Roles and
> administrative requirements defined in the authoritative **[Internara Specs](specs.md)**.

### 1.1 Authorization Philosophy: Least Privilege

Internara operates on the principle of **Least Privilege** and **Explicit Deny by Default**.

- **Least Privilege**: Users are granted only the minimum permissions necessary to execute their
  assigned domain functions.
- **Explicit Deny**: Access is denied unless a specific permission or role assignment is verified.
- **Modular Sovereignty**: Authorization logic is encapsulated within the module that owns the
  domain resource, utilizing framework-standard Policies.

### 1.2 Role Taxonomy & Traceability

Roles are defined in the `Core` module and mapped directly to the stakeholders identified in the
SSoT.

| Role                    | SSoT Stakeholder     | Primary Domain Function            |
| :---------------------- | :------------------- | :--------------------------------- |
| **Administrator**       | System Admin         | Full System Orchestration          |
| **Instructor**          | Instructor (Teacher) | Supervision, Mentoring, Assessment |
| **Staff**               | Practical Work Staff | Administration, Scheduling, V&V    |
| **Industry Supervisor** | Industry Supervisor  | On-site Feedback, Mentoring        |
| **Student**             | Student              | Journals, Attendance, Reporting    |

### 1.3 Permission Specification (ISO/IEC 29146)

Permissions represent granular capabilities and follow a strict **Module-Action** naming convention
to ensure semantic clarity.

- **Convention**: `{module}.{action}` (e.g., `attendance.report`, `journal.approve`).
- **Traceability**: Every permission must fulfill a specific functional requirement defined in the
  blueprint or SSoT.

### 1.4 Implementation Layers

#### 1.4.1 UI Layer (Presentation Logic)

Authorization must be enforced at the UI level to prevent unauthorized interaction.

- **Directive**: Use `@can` for Blade templates and `$this->authorize()` within Livewire components.
- **Behavior**: Elements representing unauthorized actions must be suppressed from the interface
  entirely.

#### 1.4.2 Service Layer (Business Logic)

The Service Layer provides the final defense against unauthorized execution.

- **Invariant**: Services must verify authorization before performing state-altering operations,
  especially for destructive actions (Delete/Force-Delete).
- **Mandatory Execution**: Every administrative method (Create/Update/Delete) within a Service MUST
  explicitly invoke `Gate::authorize()` to ensure security even when called from non-HTTP contexts
  (e.g., CLI, Jobs, or Inter-Module calls).

#### 1.4.3 Policy Pattern (The Governance Layer)

Every domain model must be associated with a **Policy Class**. Policies encapsulate complex
authorization rules, including ownership checks and state-dependent access.

```php
public function update(User $user, Journal $journal): bool
{
    // Verification: Permission check AND ownership validation
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

### 1.5 Configuration & Synchronization (Baselines)

Permissions are introduced through **Modular Seeders** to maintain a consistent security baseline.

#### 1.5.1 Idempotent Seeding

Modules must define their permissions within a seeder class, utilizing the `PermissionService`
contract.

```php
namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Services\Contracts\PermissionService;

class AttendancePermissionSeeder extends Seeder
{
    public function run(PermissionService $service): void
    {
        $permissions = ['attendance.view', 'attendance.check-in', 'attendance.report'];

        foreach ($permissions as $name) {
            $service->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
```

#### 1.5.2 Baseline Synchronization

Updates to the security baseline are performed via the native synchronization command:

```bash
php artisan permission:sync
```

---

## 2. Logic Orchestration & Data Transfer Patterns

This section defines the advanced **Process Orchestration** patterns for the Internara project,
consistent with the **[Architecture Description](architecture.md)**. It formalizes the use of **Data
Transfer Objects (DTOs)** and Service orchestration to manage complex business logic while
maintaining modular isolation.

### 2.1 The Service as a Process Manager

In Internara, Services are not merely CRUD wrappers; they are **Process Managers** responsible for
orchestrating domain logic, validation, and side-effects.

- **Responsibility**: A Service method should encapsulate a complete "Business Use Case."
- **Isolation Invariant**: Services interact with other modules strictly via **Service Contracts**.

### 2.2 Data Transfer Objects (DTOs)

To ensure type safety and eliminate "Primitive Obsession," complex inputs must be encapsulated in
DTOs.

#### 2.2.1 Pattern: Immutable DTOs

Utilize PHP 8.4+ readonly properties to create immutable data structures for cross-layer
communication.

```php
readonly class InternshipRegistrationData
{
    public function __construct(
        public string $studentId,
        public string $departmentId,
        public DateTime $startDate,
        public array $metadata = [],
    ) {}
}
```

#### 2.2.2 Applicability

- **Recommended**: For methods accepting more than 3 parameters or complex nested arrays.
- **Mandatory**: For data crossing module boundaries via Service Contracts.

### 2.3 Transactional Integrity Protocols

When a business process impacts multiple modules, the **Initiating Service** must maintain
transactional control.

```php
public function register(InternshipRegistrationData $data): string
{
    return DB::transaction(function () use ($data) {
        // 1. Internal Persistence
        $internship = $this->createInternshipRecord($data);

        // 2. Cross-Module Orchestration via Contract
        $this->accountService->initializeAccess($data->studentId);

        // 3. Event Dispatching
        event(new InternshipRegistered($internship->uuid));

        return $internship->uuid;
    });
}
```

### 2.4 Query Orchestration (EloquentQuery Extension)

While `EloquentQuery` provides standard CRUD, complex domain queries should be formalized into
semantic methods to preserve the **Single Source of Truth**.

- **Anti-Pattern**: Building complex queries directly in Livewire components.
- **Correct Pattern**: Encapsulating the logic within the Service using `this->query()`.

---

## 3. Cross-Module Event Governance

This section formalizes the **Asynchronous Orchestration** protocols for the Internara project,
standardized according to the **[Architecture Description](architecture.md)** Process View. It
defines the standards for utilizing events and listeners to handle side-effects across modular
boundaries.

### 3.1 The Asynchronous Side-Effect Invariant

To maintain low coupling, primary business actions should not have direct knowledge of their
secondary consequences.

- **Objective**: Decouple the "Trigger" (e.g., `InternshipFinished`) from the "Consequence" (e.g.,
  `NotifyStudent`, `GenerateCertificate`).
- **Standard**: Utilize Laravel's **Event/Listener** system for all cross-module side-effects that
  do not require immediate transactional consistency.

### 3.2 Event Specification & Semantics

Events must be named in the **Past Tense**, reflecting a state change that has already occurred.

- **Convention**: `{Entity}{Action}ed` (e.g., `JournalApproved`, `AssessmentFinalized`).
- **Location**: `modules/{Module}/src/Events/`.

#### 3.2.1 Event Payload Standards

Events should carry the minimum necessary state, typically the **UUID** of the impacted entity or a
lightweight DTO.

```php
class InternshipStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $internshipId) {}
}
```

### 3.3 Listener Responsibility & Isolation

Listeners are responsible for executing the side-effect by interacting with the appropriate Service
Contract.

- **Isolation Constraint**: A Listener in Module B reacting to an event from Module A must not
  access Module A's models directly. It must utilize Module B's internal services or other public
  contracts.
- **Async Execution**: By default, listeners for side-effects (Notifications, Logging, Third-party
  Sync) should implement the `ShouldQueue` interface.

### 3.4 Event Registry & Traceability

To prevent the system from becoming a "Black Box," all cross-module events must be traceable.

- **Documentation**: Significant domain events must be mentioned in the Architectural Blueprint of
  the version series.
- **Monitoring**: Utilize the `HandlesAuditLog` concern to record the dispatching of critical domain
  events for forensic analysis.

---

## 4. Data Integrity & Orchestration Protocols

This section formalizes the **Software-Level Referential Integrity (SLRI)** protocols for the
Internara project, adhering to **ISO/IEC 25010** (Reliability). It defines the engineering standards
required to maintain data consistency in a modular monolith environment where physical foreign key
constraints across module boundaries are prohibited.

> **Governance Mandate:** All service-layer persistence logic must demonstrate adherence to these
> integrity protocols to satisfy the **[Code Quality Standardization](quality.md)**.

### 4.1 The SLRI Principle (Software-Level Integrity)

Because Internara enforces **Strict Modular Isolation**, data relationships across modules must be
orchestrated by the **Service Layer** rather than the database engine.

- **Invariant**: **Physical foreign keys across module boundaries are strictly prohibited**.
- **Authority**: The "Parent" module's Service Contract is the authoritative source for validating
  the existence of a foreign entity.
- **Protocol**: Never query a foreign module's model directly to verify existence; utilize the
  designated Service Contract.

### 4.2 Integrity Verification Patterns

#### 4.2.1 Creation & Assignment Verification

When creating a record that refers to a foreign module, the service must verify the foreign entity's
existence via its Service Contract.

```php
public function create(array $data): Model
{
    // Verification: Invoke the foreign Service Contract to validate the identity
    if (! $this->foreignService->exists($data['foreign_id'])) {
        throw new EntityNotFoundException(__('module::exceptions.not_found'));
    }

    return parent::create($data);
}
```

#### 4.2.2 Deletion & Restriction Protocols

To prevent "Orphaned Records," services must implement one of the following strategies during
deletion:

- **Restrict**: Prevent deletion if dependent records exist in other modules.
- **Cascade (Logic)**: Trigger a domain event (e.g., `InternshipDeleted`) to allow other modules to
  clean up their related data asynchronously.
- **Nullify**: Update foreign references to `null` if the relationship is optional.

### 4.3 Transactional Orchestration

Multi-module operations that alter the state of multiple entities must be encapsulated within a
database transaction to ensure **Atomicity**.

- **Standard**: Utilize `DB::transaction()` within the primary orchestrating Service.
- **Scope**: Transactions should be kept as short as possible to minimize lock contention.

### 4.4 Query Scoping & Performance

Since physical joins are prohibited across module boundaries, utilize the following patterns for
data retrieval:

- **Eager Loading (Internal)**: Use `with()` only for relationships within the same module.
- **Hydration (External)**: Retrieve external data via Service Contracts and hydrate the result
  collection manually or through UI-level orchestration.

---

## 5. The Instructional Loop

This section defines the systemic lifecycle of daily student activities and the subsequent
supervisory verification process.

### 5.1 The Daily Cycle

The instructional loop is a high-frequency process designed to capture evidence of student learning
and industry engagement.

1.  **Activity Execution**: Student performs tasks at the internship placement.
2.  **Logging & Claiming**: Student records a **Journal Entry** and claims specific **Competencies**
    (Skills) mastered during the day.
3.  **Presence Verification**: Student performs **Attendance Check-in/out** to provide temporal
    evidence.
4.  **Supervisory Audit**:
    - **Mentor** provides behavioral feedback and validates the journal entry.
    - **Teacher** performs periodic visits and verifies competency attainment.
5.  **Telemetry Synthesis**: The system aggregates these interactions into the **Intelligence
    Dashboard** and calculates the **Participation Score**.

### 5.2 Integrity Invariants

- **Submission Window**: Journals must be recorded within the operational window (default: 7 days)
  to ensure accuracy and prevent retroactive logging "bloat".
- **Evidence-Based Evaluation**: Competency achievements are only certified if linked to verified
  journal entries.
- **Absence Integrity**: Approved absence requests automatically nullify the requirement for
  attendance logs for that specific date.

### 5.3 Implementation Standards

- **Isolation**: Domain modules (Journal, Attendance, Mentor) communicate via Service Contracts to
  update the assessment baseline.
- **Auditability**: Every state transition in the loop (Submitted -> Approved/Rejected) is recorded
  in the forensic audit trail.

---

## 6. Task Fulfillment Protocols

This section defines the protocols for managing student assignments and deliverables within the
Internara ecosystem.

### 6.1 Dynamic Assignment Engine

To allow for institutional flexibility, Internara utilizes a decoupled **Assignment** module that
manages student tasks independently of the core internship program.

- **Assignment Templates**: Administrators define `AssignmentTypes` (e.g., "Final Report", "Logbook
  Summary").
- **Automatic Instantiation**: When an internship program is created, the system automatically
  instantiates assignments for that program based on the current active types.

### 6.2 Fulfillment Invariants

A student's internship lifecycle is governed by the **Assignment Fulfillment Invariant**.

- **Certification Gating**: A student's program status cannot be transitioned to `completed` until
  every mandatory assignment linked to their program has a **Verified** submission.
- **Verification Loop**:
    1. Student uploads file (PDF/PPT/DOC).
    2. Supervisor (Teacher/Mentor) reviews and verifies the submission.
    3. The `AssignmentService` recalculates the fulfillment status for the registration.

### 6.3 Implementation Standards

- **Cross-Module Verification**: The `Internship` module verifies graduation readiness by injecting
  the `AssignmentService` contract and invoking `isFulfillmentComplete()`.
- **Status Auditing**: Every submission transition (Submitted -> Verified/Rejected) must be recorded
  in the `ActivityLog`.

---

## 7. Reporting Orchestration

This section defines the protocols for generating structured academic and administrative reports
across the Internara ecosystem.

### 7.1 The `ExportableDataProvider` Pattern

To ensure low coupling, the `Report` module does not query domain models directly. Instead, it
interacts with **Data Providers** defined in domain modules.

- **Contract**: `Modules\Shared\Contracts\ExportableDataProvider`.
- **Responsibility**: Domain modules must implement this contract to provide the data, template
  path, and filter rules required for a specific report type.

#### 7.1.1 Implementation Flow

1.  **Registry**: Domain modules register their Data Providers in the `Report` module's registry.
2.  **Request**: The `Report` module captures user filters and validates them against the provider's
    rules.
3.  **Synthesis**: The engine retrieves data from the provider and passes it to the designated Blade
    template.

### 7.2 Asynchronous Generation Strategy

Large-scale reports (e.g., class-wide competency transcripts) must be generated asynchronously to
preserve system responsiveness.

- **Standard**: All heavy document synthesis jobs must implement the `ShouldQueue` interface.
- **Feedback**: The system utilizes the `Notifier` service to inform users once the report is
  available for download.

### 7.3 Storage & Security

- **Private Persistence**: All generated reports must be stored on the `private` disk.
- **Signed Access**: Access to download reports is restricted via **Signed URLs** to prevent
  unauthorized data exposure.

---

## 8. System Configuration & Dynamic Settings

This section defines the protocols for managing application-wide configurations that require runtime
administrative control via the `setting()` helper.

### 8.1 Authoritative Source: The `setting()` Helper

Internara utilizes a global `setting()` helper to retrieve dynamic configurations from the database
with a proactive caching layer.

- **Primary Provider**: The `Setting` module manages the persistence and caching of these values.
- **Infrastructure Fallback**: The `Core` module provides a safe, non-functional fallback for the
  helper to prevent system-wide fatal errors if the `Setting` module is unavailable during early
  boot cycles.

#### 8.1.1 Usage Pattern

```php
// Retrieve a setting with a default value
$threshold = setting('late_threshold', 15);

// Retrieve an array-based setting
$branding = setting('institutional_branding');
```

### 8.2 Bootstrapping Resilience

To ensure architectural stability, certain critical system checks must bypass the full service
container.

- **File-Based Discovery**: Logic related to module activation status (via `modules_statuses.json`)
  is designed to operate before the database engine is initialized.
- **Fail-Safe Defaults**: Always provide sensible default values in the second parameter of
  `setting()` to ensure operational continuity during environment migration or database resets.

### 8.3 Auditability Invariant

Every modification to a dynamic setting must be captured by the `Log` module.

- **Traceability**: Captured metadata must include the previous value, the new value, and the
  administrative subject responsible for the change.

---

## 9. Participation & Compliance Scoring

This section defines the automated logic used to calculate student engagement metrics during the
internship period.

### 9.1 Scoring Components

Compliance scores are derived from two primary high-frequency telemetry sources:

1.  **Attendance Score**: Derived from the ratio of actual presence to scheduled working days.
2.  **Journal Score**: Derived from the ratio of approved daily logs to scheduled working days.

### 9.2 Calculation Formula

The `ComplianceService` calculates scores based on the **Effective Working Days** (excluding
weekends) between the internship start date and the current date (or end date).

#### 9.2.1 Component Weighting

By default, the final compliance score is a simple weighted average:

- **Attendance**: 50%
- **Journaling**: 50%

**Formula**: `Final Score = (Attendance % * 0.5) + (Journal % * 0.5)`

#### 9.2.2 Participation Capping

To maintain data integrity, scores are capped at 100%. If a student logs more entries than required
working days (due to overtime or makeup tasks), the extra entries contribute to qualitative merit
but do not exceed the 100% participation threshold.

### 9.3 Implementation Invariant

- **Asynchronous Retrieval**: Assessment logic must retrieve compliance scores via the
  `ComplianceService` contract, never by querying `AttendanceLog` or `JournalEntry` models directly.
- **Dynamic Thresholds**: Working day calculations must respect institutional settings for late
  thresholds and submission windows.

---

## 10. Notification Orchestration

This section defines the protocols for dispatching notifications and UI feedback within the
Internara project.

### 10.1 Multi-Path Notification Strategy

Internara distinguishes between transient UI feedback and persistent/external notifications.

#### 10.1.1 UI Feedback (Transient)

Used for immediate response to a user action (e.g., "Record saved successfully").

- **Path**: Dispatched via the `Notifier` service contract.
- **Mechanism**: Livewire browser events (Toasts).
- **Service**: `Modules\Notification\Services\Contracts\Notifier`.

#### 10.1.2 System & User Notifications (Persistent/External)

Used for formal communication or asynchronous alerts (e.g., "New internship assignment",
"Registration approved").

- **Path**: Dispatched via the `NotificationService` contract.
- **Channels**:
    - **System**: Stored in the `notifications` database table.
    - **User**: Sent via external channels like **Email**.
- **Service**: `Modules\Notification\Services\Contracts\NotificationService`.

### 10.2 Implementation Standards

- **Contract-First**: Domain modules must never use the `Notification` facade directly. They must
  inject the appropriate service contract.
- **Localization**: All notification content must be localized via translation keys before being
  passed to the dispatcher.
- **Queueing**: Persistent notifications should implement the `ShouldQueue` interface to prevent UI
  blocking during external mail delivery.

---

## 11. Policy Patterns: Authorization Governance Standards

This section formalizes the **Authorization Policy Patterns** for the Internara project, adhering to
**ISO/IEC 29146** (Access Control Framework) and **ISO/IEC 27001** (Access Control). It defines
standardized logic for **Policy Enforcement Points (PEP)** to ensure consistent and context-aware
security across all domain modules.

### 11.1 Policy Enforcement Architecture

In Internara, **Policies** serve as the primary PEP for all domain resources. Every Eloquent model
must be associated with a Policy class to centralize and formalize access decisions.

- **Invariant**: Authorization checks must be performed at the earliest possible boundary (PEP)
  before any business logic is executed.
- **Protocol**: Controllers and Livewire components must utilize `$this->authorize()` or @can to
  invoke the associated Policy.

### 11.2 Standard Authorization Patterns

#### 11.2.1 Pattern: Permission-Based Ownership (Default)

The system verifies that the subject possesses the required functional permission AND maintains
ownership of the specific resource.

- **Applicability**: Personal records (e.g., Student Journals, Personal Profiles).

```php
public function update(User $user, Journal $journal): bool
{
    // Verification: Capability (Permission) + Context (Ownership)
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

#### 11.2.2 Pattern: Hierarchical Supervision (Relational)

Allows subjects in supervisory roles (Instructor, Industry Supervisor) to access resources
associated with their subordinates (Students).

- **Applicability**: Mentoring logs, academic reports, and attendance records.

```php
public function view(User $user, Journal $journal): bool
{
    // Context-Aware Verification: Is the user an assigned supervisor for this record?
    $isInstructor = $journal->registration->instructor_id === $user->id;
    $isMentor = $journal->registration->industry_supervisor_id === $user->id;

    return $user->can('journal.view') && ($isInstructor || $isMentor);
}
```

#### 11.2.3 Pattern: Administrative Override (Super-Admin)

The **Super-Admin** role maintains universal bypass capabilities to facilitate emergency system
orchestration and maintenance.

- **Implementation**: Defined via `Gate::before()` in the `AuthServiceProvider`.

### 11.3 Engineering Standards for Policies

#### 11.3.1 Strict Typing Invariant

All policy methods must declare strict types for the `User` subject and the domain `Model` object.
Failure to use strict typing is considered an architectural defect.

#### 11.3.2 Semantic CRUD Mapping

Policy methods must correspond 1:1 with the standard system actions:

- `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

#### 11.3.3 Explicit Deny by Default

If any condition remains unsatisfied, the policy must return `false`. Silence or ambiguity in policy
logic is prohibited.

---

_Security and logic orchestration are systemic invariants. By adhering to these governance
standards, Internara ensures a high-fidelity architecture that is disciplined, traceable, and
compliant with international engineering frameworks._
