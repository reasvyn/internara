# Exception Handling: Engineering Resilience Standards

This document formalizes the **Fault Tolerance** and **Error Management** protocols for the
Internara project, adhering to **ISO/IEC 25010** (Reliability) and **ISO/IEC 27034** (Application
Security). It defines the strategy for ensuring system stability while providing secure, traceable,
and localized feedback to stakeholders.

> **Governance Mandate:** Exception handling must prioritize system integrity and the protection of
> sensitive information as mandated by the authoritative
> **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. Categorization of Faults & Exceptions

Internara utilizes a tiered classification system to manage system anomalies based on their origin
and intended resolution path.

### 1.1 Validation Exceptions (Anticipated Errors)

- **Origin**: Boundary layers (Livewire Components, API Controllers).
- **Behavior**: Handled via standard validation protocols.
- **Feedback**: Immediate, localized feedback identifying the specific invalid input.

### 1.2 Domain Exceptions (Business Logic Violations)

- **Origin**: The **Service Layer**.
- **Behavior**: Custom exceptions (e.g., `JournalLockedException`) representing specific business
  rule violations.
- **Feedback**: Transformed into user-friendly notifications in the UI layer.

### 1.3 System Exceptions (Non-Recoverable Failures)

- **Origin**: Infrastructure (Database, Network, Filesystem).
- **Behavior**: Logged with full stack traces for forensic analysis.
- **Feedback**: Abstracted into a generic, secure notification (e.g., "System Anomaly") in
  production to prevent information leakage.

---

## 2. Security & Privacy Invariants (ISO/IEC 27034)

Exception handling must never compromise the system's security posture or expose Personally
Identifiable Information (PII).

### 2.1 Information Leakage Prevention

- **Sanitization**: Error messages intended for end-users must never include database schema
  details, file paths, or variable values.
- **PII Masking**: The automated logging subsystem must redact sensitive fields (e.g., `email`,
  `password`, `ssn`) from all log payloads.

### 2.2 Forensic Traceability

- **Correlation**: Every exception must be associated with a unique **Correlation ID** and the
  authenticated **User ID** to facilitate cross-module log analysis.
- **Contextual Logging**: Capture the specific module state and operation parameters while ensuring
  compliance with privacy mandates.

---

## 3. Localization (i11n) Standards

Hard-coding of exception messages is a **Critical Quality Violation**.

- **Protocol**: All exception messages must be resolved via translation keys.
- **Mapping**:
    - **Module-Specific**: `__('module::exceptions.key')`.
    - **Generic/Shared**: `__('exception::messages.key')`.

---

## 4. Implementation Pattern

### 4.1 Boundary Level Handling

Livewire components must orchestrate the transition from Domain Exceptions to UI-friendly feedback.

```php
try {
    $this->journalService->lock($journalId);
} catch (JournalAlreadyLockedException $e) {
    $this->notify(__('journal::exceptions.already_locked'), type: 'warning');
} catch (DomainException $e) {
    $this->notify(__('exception::messages.unauthorized'), type: 'error');
}
```

### 4.2 Service Layer Propagation

Services should throw semantic, custom exceptions that reflect the domain rule being violated.

- **Base Requirement**: Custom exceptions must reside in the `src/Exceptions/` directory of their
  respective module.

---

_By adhering to these resilience standards, Internara ensures that faults are managed in a
disciplined, secure, and user-centric manner, preserving the reliability of the modular monolith._
