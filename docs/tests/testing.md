# Testing Philosophy: Engineering Reliability

Internara prioritizes a **Spec-Driven** and **Isolation-Aware** verification strategy. We utilize
the **Pest v4** framework to ensure that every system capability is technically correct, localized,
and secure.

> **Governance Mandate:** Testing is the authoritative mechanism for validating fulfillment of the
> **[System Requirements Specification](../internal/system-requirements-specification.md)**.
> Construction is incomplete without formal verification pass.

---

## 1. Verification Framework: Pest PHP v4

Pest is the foundational tool for our **Verification & Validation (V&V)** activities.

- **Traceability**: Enables mapping of tests to specific System Requirements Specification
  requirements.
- **Velocity**: Support for parallel execution via the optimized verification suite.
- **Architectural Guard**: Enforces modular boundaries via Architecture (Arch) Testing.

---

## 2. Structural Hierarchy of Tests

Verification is categorized based on the architectural depth of the artifact under test:

### 2.1 Unit Verification

Testing atomic logic (Services, Concerns, Enums) in isolation.

- **Location**: `modules/{Module}/tests/Unit/{Layer}/`.

### 2.2 Feature Validation

Testing integrated user stories and domain flows, including UI and persistence.

- **Location**: `modules/{Module}/tests/Feature/{Layer}/`.

---

## 3. Mandatory V&V Protocols

An artifact satisfies the quality gate only when it passes the following invariants:

1.  **System Requirements Specification Compliance**: Verifies behavior aligns with the
    **[System Requirements Specification](../internal/system-requirements-specification.md)**.
2.  **Role-Based Access (RBAC)**: Verifies that access is restricted to the roles defined in the
    System Requirements Specification.
3.  **Localization Integrity (i18n)**: Verifies that all user-facing output is localized across all
    supported locales (`id`, `en`).
4.  **Boundary Isolation**: Verifies compliance with the **Strict Isolation** invariants in the
    **[Architecture Description](../internal/architecture-description.md)**.

---

## 4. Execution of the Verification Suite

The following command is the **Mandatory Verification Gate** for all engineering activities:

```bash
# Full System Verification
composer test
```

- **Objective**: Execute all unit, feature, and architecture tests in parallel to ensure a
  consistent and verified system baseline.

---

_Verification artifacts are the executable documentation of our engineering rigor and commitment to
the Internara specifications._
