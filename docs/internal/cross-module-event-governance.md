# Cross-Module Event Governance

This document formalizes the **Asynchronous Orchestration** protocols for the Internara project,
standardized according to the **[Architecture Description](architecture-description.md)** Process
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
