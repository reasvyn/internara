# Profile Module

The `Profile` module manages extended user data and academic integration within the Internara
ecosystem. It provides the necessary storage for information that distinguishes users beyond their
core authentication credentials, such as institutional IDs, contact details, and department
affiliations.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## 1. Architectural Role

As a **Core Domain Module**, the `Profile` module acts as a specialized data store for user-specific
attributes. It maintains a strict one-to-one relationship with the `User` module while facilitating
decoupled integration with the `Department`, `Student`, and `Teacher` modules.

---

## 2. Core Components

### 2.1 Service Layer

- **`ProfileService`**: Manages the initialization and synchronization of user profiles.
    - _Features_: Automated profile creation upon user registration and role-based "profileable"
      model association (Student/Teacher).
    - _Contract_: `Modules\Profile\Services\Contracts\ProfileService`.

### 2.2 Persistence Layer

- **`Profile` Model**: The central entity for extended personal data.
    - _Identifiers_: Utilizes **national_identifier** (SSoT for NIP/NISN or national IDs) and
      **registration_number** (SSoT for institutional/school IDs like NIS) to consolidate all
      stakeholder identities into a single record.
    - _Relationships_: Linked to `User` (Identity), `Department` (Academic Scoping), and a
      polymorphic `profileable` (Domain Specifics).
    - _Security_: Uses **UUID v4** for secure identification.

---

## 3. Engineering Standards

- **Zero-Coupling**: Cross-module relationships are managed via indexed UUID columns without
  physical foreign keys.
- **Model Isolation**: Utilizes the `Role` Enum from the `Permission` module to perform role-based
  logic, avoiding direct dependency on the `User` model for constants.
- **Privacy First**: Sensitive fields (phone, address, emergency contacts) are subject to automated
  encryption at rest and masking in system logs via the `Log` module.
- **Enhanced Demographics**: Supports collection of Gender, Blood Type, and Emergency Contact
  metadata to satisfy institutional safety requirements.

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Integration Tests**: Verifies seamless relationship mapping with the `Department` module.
- **Feature Tests**: Validates user-facing profile update workflows and authorization.
- **Command**: `php artisan test modules/Profile`

---

_The Profile module ensures that every user in Internara has a complete and context-aware academic
identity._
