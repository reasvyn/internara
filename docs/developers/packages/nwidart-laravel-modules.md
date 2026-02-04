# Laravel Modules: The Foundational Orchestrator

This document formalizes the integration of the `nwidart/laravel-modules` package, which serves as
the primary orchestrator for the **Modular Monolith** baseline of the Internara project. It provides
the structural framework and autoloading logic required to maintain autonomous domain boundaries.

---

## 1. Structural Baselines (Architectural Invariants)

Internara utilizes a customized configuration of the modular engine to enforce the following
architectural invariants:

### 1.1 Encapsulated Source Layout

All domain logic is encapsulated within a dedicated `src/` directory to isolate functional artifacts
from modular assets.

- **Implementation**: `'paths.app_folder' => 'src/'`.
- **Result**: Logical artifacts reside at `modules/{Module}/src/`.

### 1.2 Namespace Synchronization (src-Omission)

The autoloading baseline is configured to omit the `src` segment from the PHP namespace, ensuring
semantic brevity and alignment with the
**[Architecture Description](../architecture-description.md)**.

- **Invariant**: `Modules\{Module}\Models\Entity` maps to `modules/{Module}/src/Models/Entity.php`.

---

## 2. Automated Construction Protocols

Modular artifacts must be generated via the
**[Automated Tooling](../automated-tooling-reference.md)** to ensure compliance with the Internara
structural baseline.

### 2.1 Artifact Scaffolding

- **Contracts Layer**: Interface definitions for cross-module decoupling.
- **Domain Layer**: Business logic orchestration via Service Contracts.
- **Persistence Layer**: UUID-based entity mapping.

---

## 3. Mandatory Isolation Protocols

The modular engine is utilized to enforce **Strict Domain Isolation**:

- **Persistence Invariant**: No physical foreign key constraints across modular boundaries.
- **Communication Invariant**: Cross-module interaction is restricted to **Service Contracts**
  resolved via the Laravel Service Container.

---

_By strictly governing the modular engine, Internara ensures a resilient, decoupled, and analysable
architecture that supports high-velocity developmental iterations._
