# Interface Specification (IS): Service Contracts & Inter-Module Communication

This document formalizes the **Interface Specification (IS)** for the Internara system, standardized according to **ISO/IEC/IEEE 15288** (System life cycle processes). It defines the protocols for inter-module communication, service contracts, and data transfer objects (DTOs).

---

## 1. Architectural Integrity: The Contract-First Principle

Internara enforces **Contract-First Communication**. Modules must never access the concrete implementations or models of other modules. All interactions are mediated through **Service Contracts** (Interfaces).

### 1.1 Invariants
- **Abstraction**: Depend only on interfaces located in `src/Services/Contracts/`.
- **Discovery**: Services are registered and resolved via the Laravel Service Container.
- **Portability**: Contracts allow for the implementation to be swapped or mocked during testing without affecting dependent modules.

---

## 2. Core Service Contracts (The Public API)

Each domain module provides a set of public contracts that define its available capabilities.

### 2.1 Identity (User & Profile Module)
- **`UserService`**: Manage authentication, roles, and basic user data.
- **`ProfileService`**: Manage institutional identifiers (NISN/NIP) and student profiles.

### 2.2 Internship (Internship & Placement Module)
- **`InternshipService`**: Program lifecycle orchestration.
- **`RegistrationService`**: Student-program enrollment and status management.
- **`PlacementService`**: Industry partner slot allocation and eligibility logic.

### 2.3 Vocational (Journal & Attendance Module)
- **`JournalService`**: Activity logging and verification.
- **`AttendanceService`**: Presence tracking and temporal verification.

---

## 3. Data Transfer Protocols (DTOs) & Payloads

To ensure type safety and cross-boundary stability, complex data is moved using **DTOs**.

### 3.1 Pattern: Immutable DTOs
All DTOs must be implemented as PHP 8.4+ readonly classes.

```php
readonly class InternshipRegistrationData {
    public function __construct(
        public string $internshipId,
        public string $studentId,
        public string $placementId,
        public array $metadata = []
    ) {}
}
```

### 3.2 Event Payloads (Lightweight)
Domain events (e.g., `InternshipStarted`) must only carry the **UUID** of the aggregate root to prevent serialization issues and stale state.

---

## 4. Error Handling & Failure Semantics

Interfaces must provide deterministic error handling.
- **Exceptions**: Use semantic, localized exceptions from `src/Exceptions/`.
- **Validation**: Entry boundaries (PEP) must perform full input validation before invoking service methods.
- **Transactionality**: Orchestration services are responsible for ensuring atomicity across multiple contract calls.
