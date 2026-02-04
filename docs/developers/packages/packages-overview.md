# Package Integration: The Internara Supporting Ecosystem

This document formalizes the **Supporting Ecosystem** for the Internara project, standardized
according to **ISO/IEC 12207** (Supporting Life Cycle Processes). It defines the protocols for
integrating third-party dependencies while maintaining the **Strict Isolation** invariants of the
modular monolith.

---

## 1. Architectural Drivers (Modularity Engine)

### 1.1 [nwidart/laravel-modules](nwidart-laravel-modules.md)

The foundational orchestrator for domain isolation. It provides the structural baseline and
autoloading logic required to maintain autonomous modular boundaries.

### 1.2 [mhmiton/laravel-modules-livewire](mhmiton-laravel-modules-livewire.md)

The integration bridge for cross-module presentation logic, enabling semantic component discovery
and modular view namespaces.

---

## 2. Security & Governance (Access & Audit)

### 2.1 [spatie/laravel-permission](spatie-laravel-permission.md)

The primary engine for **Identity & Access Management (IAM)**. This baseline is encapsulated within
the `Permission` module, providing **UUID-based RBAC** as mandated by the System Requirements
Specification.

### 2.2 [spatie/laravel-activitylog](spatie-laravel-activitylog.md)

Orchestrates systemic auditability. It is integrated into the `Log` module to ensure that all
state-altering operations satisfy the **Traceability** requirements of the System Requirements
Specification.

---

## 3. Presentation & State Persistence

### 3.1 [Livewire](laravel-livewire.md)

The primary driver for high-fidelity, reactive user interfaces. It enables complex state
orchestration through strict-typed PHP logic.

### 3.2 [spatie/laravel-model-status](spatie-laravel-model-status.md)

Implements the **Entity Lifecycle** baseline. It provides standardized state transitions and
forensic audit trails for operational data.

---

## 4. Technical Integration Invariants

To prevent architectural regression, all package integrations must satisfy the following:

- **Isolation Invariant**: Configuration must be injected at runtime via modular providers; leakage
  into the global baseline is prohibited.
- **Namespace Invariant**: Integrations must respect the **src-Omission** namespace rule to maintain
  modular portability.
- **V&V Mandatory**: Every package integration must pass the automated verification gate via
  **`composer test`**.

---

_By strictly governing package integration, Internara ensures that the system remains
architecturally pure and maintainable, leveraging external innovation without compromising modular
integrity._
