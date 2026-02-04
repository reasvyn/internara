# Exception Module

The `Exception` module provides the foundational infrastructure for standardized error handling and
fault tolerance within the Internara ecosystem. It ensures that technical failures are captured,
logged, and presented to users in a secure, localized, and context-aware manner.

> **Governance Mandate:** This module implements the requirements defined in the authoritative **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Public Module**, the `Exception` module provides base classes and concerns that are consumed
by all domain modules to ensure consistent error behavior across the modular monolith.

---

## 2. Core Components

### 2.1 Foundational Exceptions

- **`AppException`**: The base class for all domain-specific exceptions. It separates internal
  technical details (for logging) from user-friendly localized messages (for UI).
- **`RecordNotFoundException`**: A specialized exception for 404 scenarios, providing automated
  context for missing database records.

### 2.2 Global Concerns

- **`HandlesAppException`**: A reusable trait for Controllers, Livewire components, and Service
  Providers to facilitate standardized exception reporting and rendering.

---

## 3. Engineering Standards

- **Brevity & Context**: Exception names reflect their semantic purpose (e.g.,
  `RecordNotFoundException` vs generic `NotFoundException`).
- **Zero Magic Values**: Utilizes `Symfony\Component\HttpFoundation\Response` constants for all HTTP
  status codes.
- **Security Invariant**: Automatically redact sensitive data (PII) from exception logs and prevents
  information leakage in production environments by abstracting system errors into generic
  notifications.
- **i18n Compliance**: Mandates the use of translation keys for all user-facing feedback.

---

## 4. Verification & Validation (V&V)

Robustness is verified through **Pest v4**:

- **Unit Tests**: Verifies translation resolution, status code mapping, and JSON rendering logic.
- **Coverage**: Ensures 100% reliability of the foundational error reporting chain.
- **Command**: `php artisan test modules/Exception`

---

_The Exception module ensures that Internara fails gracefully and securely, preserving systemic
integrity during anomalies._
