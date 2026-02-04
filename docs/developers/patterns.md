# Logic Orchestration & Data Transfer Patterns

This document defines the advanced **Process Orchestration** patterns for the Internara project,
**[Architecture Description](architecture.md)**
use of **Data Transfer Objects (DTOs)** and Service orchestration to manage complex business logic
while maintaining modular isolation.

---

## 1. The Service as a Process Manager

In Internara, Services are not merely CRUD wrappers; they are **Process Managers** responsible for
orchestrating domain logic, validation, and side-effects.

- **Responsibility**: A Service method should encapsulate a complete "Business Use Case."
- **Isolation Invariant**: Services interact with other modules strictly via **Service Contracts**.

---

## 2. Data Transfer Objects (DTOs)

To ensure type safety and eliminate "Primitive Obsession," complex inputs must be encapsulated in
DTOs.

### 2.1 Pattern: Immutable DTOs

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

### 2.2 Applicability

- **Recommended**: For methods accepting more than 3 parameters or complex nested arrays.
- **Mandatory**: For data crossing module boundaries via Service Contracts.

---

## 3. Transactional Integrity Protocols

When a business process impacts multiple modules, the **Initiating Service** must maintain
transactional control.

```php
public function register(InternshipRegistrationData $data): Internship
{
    return DB::transaction(function () use ($data) {
        // 1. Internal Persistence
        $internship = $this->createInternshipRecord($data);

        // 2. Cross-Module Orchestration via Contract
        $this->accountService->initializeAccess($data->studentId);

        // 3. Event Dispatching
        event(new InternshipRegistered($internship));

        return $internship;
    });
}
```

---

## 4. Query Orchestration (EloquentQuery Extension)

While `EloquentQuery` provides standard CRUD, complex domain queries should be formalized into
semantic methods to preserve the **Single Source of Truth**.

- **Anti-Pattern**: Building complex queries directly in Livewire components.
- **Correct Pattern**: Encapsulating the logic within the Service using `this->query()`.

---

_Utilizing these patterns ensures that business logic remains discoverable, testable, and
architecturally sound, enabling the system to scale in complexity without decaying into technical
debt._


---

# Cross-Module Event Governance

This document formalizes the **Asynchronous Orchestration** protocols for the Internara project,
standardized according to the **[Architecture Description](architecture.md)** Process
View. It defines the standards for utilizing events and listeners to handle side-effects across
modular boundaries.

---

## 1. The Asynchronous Side-Effect Invariant

To maintain low coupling, primary business actions should not have direct knowledge of their
secondary consequences.

- **Objective**: Decouple the "Trigger" (e.g., `InternshipFinished`) from the "Consequence" (e.g.,
  `NotifyStudent`, `GenerateCertificate`).
- **Standard**: Utilize Laravel's **Event/Listener** system for all cross-module side-effects that
  do not require immediate transactional consistency.

---

## 2. Event Specification & Semantics

Events must be named in the **Past Tense**, reflecting a state change that has already occurred.

- **Convention**: `{Entity}{Action}ed` (e.g., `JournalApproved`, `AssessmentFinalized`).
- **Location**: `modules/{Module}/src/Events/`.

### 2.1 Event Payload Standards

Events should carry the minimum necessary state, typically the **UUID** of the impacted entity or a
lightweight DTO.

```php
class InternshipStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $internshipId) {}
}
```

---

## 3. Listener Responsibility & Isolation

Listeners are responsible for executing the side-effect by interacting with the appropriate Service
Contract.

- **Isolation Constraint**: A Listener in Module B reacting to an event from Module A must not
  access Module A's models directly. It must utilize Module B's internal services or other public
  contracts.
- **Async Execution**: By default, listeners for side-effects (Notifications, Logging, Third-party
  Sync) should implement the `ShouldQueue` interface.

---

## 4. Event Registry & Traceability

To prevent the system from becoming a "Black Box," all cross-module events must be traceable.

- **Documentation**: Significant domain events must be mentioned in the Architectural Blueprint of
  the version series.
- **Monitoring**: Utilize the `AuditLog` concern to record the dispatching of critical domain events
  for forensic analysis.

---

_By governing events with these protocols, Internara ensures a decoupled, scalable, and traceable
architecture that handles complexity through elegant asynchronous orchestration._


---

# Data Integrity & Orchestration Protocols

This document formalizes the **Software-Level Referential Integrity (SLRI)** protocols for the
Internara project, adhering to **ISO/IEC 25010** (Reliability). It defines the engineering standards
required to maintain data consistency in a modular monolith environment where physical foreign key
constraints across module boundaries are prohibited.

> **Governance Mandate:** All service-layer persistence logic must demonstrate adherence to these
> integrity protocols to satisfy the
> **[Code Quality Standardization](quality.md)**.

---

## 1. The SLRI Principle (Software-Level Integrity)

Because Internara enforces **Strict Modular Isolation**, data relationships across modules must be
orchestrated by the **Service Layer** rather than the database engine.

- **Invariant**: The "Parent" module's Service Contract is the authoritative source for validating
  the existence of a foreign entity.
- **Protocol**: Never query a foreign module's model directly to verify existence; utilize the
  designated Service Contract.

---

## 2. Integrity Verification Patterns

### 2.1 Creation & Assignment Verification

When creating a record that refers to a foreign module (e.g., assigning a Student to a Placement),
the service must verify the foreign entity's existence.

```php
public function create(array $data): Model
{
    // Verification: Utilize the foreign Service Contract to validate the identity
    if (! $this->studentService->exists($data['student_id'])) {
        throw new EntityNotFoundException(__('student::exceptions.not_found'));
    }

    return parent::create($data);
}
```

### 2.2 Deletion & Restriction Protocols

To prevent "Orphaned Records," services must implement one of the following strategies during
deletion:

- **Restrict**: Prevent deletion if dependent records exist in other modules.
- **Cascade (Logic)**: Trigger a domain event (e.g., `InternshipDeleted`) to allow other modules to
  clean up their related data asynchronously.
- **Nullify**: Update foreign references to `null` if the relationship is optional.

---

## 3. Transactional Orchestration

Multi-module operations that alter the state of multiple entities must be encapsulated within a
database transaction to ensure **Atomicity**.

- **Standard**: Utilize `DB::transaction()` within the primary orchestrating Service.
- **Scope**: Transactions should be kept as short as possible to minimize lock contention.

---

## 4. Query Scoping & Performance

Since physical joins are prohibited across module boundaries, utilize the following patterns for
data retrieval:

- **Eager Loading (Internal)**: Use `with()` only for relationships within the same module.
- **Hydration (External)**: Retrieve external data via Service Contracts and hydrate the result
  collection manually or through UI-level orchestration.

---

_By strictly adhering to these protocols, Internara ensures a reliable and consistent data
ecosystem, preserving the benefits of modularity without sacrificing systemic integrity._


---

# Policy Patterns: Authorization Governance Standards

This document formalizes the **Authorization Policy Patterns** for the Internara project, adhering
to **ISO/IEC 29146** (Access Control Framework) and **ISO/IEC 27001** (Access Control). It defines
standardized logic for **Policy Enforcement Points (PEP)** to ensure consistent and context-aware
security across all domain modules.

> **Governance Mandate:** Authorization logic must strictly enforce the **User Roles** and
> administrative constraints defined in the authoritative
> **[Internara Specs](specs.md)**.

---

## 1. Policy Enforcement Architecture

In Internara, **Policies** serve as the primary PEP for all domain resources. Every Eloquent model
must be associated with a Policy class to centralize and formalize access decisions.

- **Invariant**: Authorization checks must be performed at the earliest possible boundary (PEP)
  before any business logic is executed.
- **Protocol**: Controllers and Livewire components must utilize `$this->authorize()` or `@can` to
  invoke the associated Policy.

---

## 2. Standard Authorization Patterns

### 2.1 Pattern: Permission-Based Ownership (Default)

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

### 2.2 Pattern: Hierarchical Supervision (Relational)

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

### 2.3 Pattern: Administrative Override (Super-Admin)

The **Super-Admin** role maintains universal bypass capabilities to facilitate emergency system
orchestration and maintenance.

- **Implementation**: Defined via `Gate::before()` in the `AuthServiceProvider`.

---

## 3. Engineering Standards for Policies

### 3.1 Strict Typing Invariant

All policy methods must declare strict types for the `User` subject and the domain `Model` object.
Failure to use strict typing is considered an architectural defect.

### 3.2 Semantic CRUD Mapping

Policy methods must correspond 1:1 with the standard system actions:

- `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

### 3.3 Explicit Deny by Default

If any condition remains unsatisfied, the policy must return `false`. Silence or ambiguity in policy
logic is prohibited.

---

_By adhering to these standardized policy patterns, Internara ensures a resilient, context-aware,
and traceable authorization posture across its modular ecosystem._
