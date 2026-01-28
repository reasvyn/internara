# Logic Orchestration & Data Transfer Patterns

This document defines the advanced **Process Orchestration** patterns for the Internara project,
consistent with the **[Architecture Description](architecture-description.md)**. It formalizes the
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
