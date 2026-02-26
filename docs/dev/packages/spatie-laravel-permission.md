# Spatie Permission: Modular IAM Orchestration

This document formalizes the integration of the `spatie/laravel-permission` package, which powers
the **Identity & Access Management (IAM)** and **RBAC** baseline for the Internara project. It
defines the technical customizations required to maintain modular sovereignty and UUID-based
identification.

---

## 1. Technical Baseline Customizations

Internara utilizes a specialized configuration of the permission engine to satisfy the security
requirements defined in the **[System Requirements Specification](../specs.md)**.

### 1.1 Identity Invariant: UUID Support

The persistence baseline for all security artifacts (Roles, Permissions) is configured to utilize
**UUID v4** primary keys, ensuring consistency with the systemic identity standard.

### 1.2 Resource Sovereignty (Module Ownership)

The security schema is enhanced with a `module` metadata attribute to establish clear ownership
boundaries.

- **Traceability**: Facilitates identification of the module responsible for defining a granular
  capability.
- **Cleanup**: Enables automated decommissioning of modular permissions during baseline retirement.

### 1.3 Decoupled Configuration Injection

The `Permission` module injects its configuration at runtime to prevent leakage into the global
baseline and maintain modular portability.

- **Protocol**: Runtime overrides ensure that the system resolves the customized modular models
  instead of the package defaults.

---

## 2. Governance & Implementation Invariants

Verification of security compliance is mandatory for all configuration baselines.

- **Policy Invariant**: Direct permission verification within the presentation layer is prohibited.
  Logic must be encapsulated within **[Authorization Policies](../governance.md)**.
- **Baseline Synchronization**: Updates to the modular RBAC baseline are performed via the
  **[Automated Tooling](../tooling.md)**: `php artisan permission:sync`.
- **V&V Mandatory**: All security state transitions must be verified via **`composer test`**.

---

_By strictly governing the IAM engine, Internara ensures a resilient, traceable, and secure access
control posture across its modular ecosystem._
