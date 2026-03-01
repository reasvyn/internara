# Laravel Modules: The Foundational Orchestrator

This document formalizes the integration of the `nwidart/laravel-modules` package, which serves as
the primary orchestrator for the **Modular Monolith** baseline of the Internara project. It provides
the structural framework and autoloading logic required to maintain autonomous domain boundaries.

> **Governance Mandate:** All modular activities must satisfy the requirements for **Strict Domain
> Isolation** defined in the authoritative **[Architecture Description](../architecture.md)**.

---

## 1. Directory Structure & Namespacing

To maintain brevity and align with standard Laravel conventions, Internara enforces the
**src-Omission** invariant for all module namespaces.

### 1.1 Encapsulated Source Layout (The `src/` Invariant)

All domain logic is encapsulated within a dedicated `src/` directory to isolate functional artifacts
from modular assets.

- **Implementation**: `'paths.app_folder' => 'src/'` in `config/modules.php`.
- **Result**: Logical artifacts reside at `modules/{Module}/src/`.
- **Namespace Definition**: The `src` segment is omitted from the namespace.
- **Mapping**: `Modules\User\Services` maps to `modules/User/src/Services/`.

### 1.2 Hierarchy of Concerns

Every module must adhere to the following internal directory baseline:

- `src/Contracts/`: Module-wide interfaces.
- `src/Models/`: Eloquent entities (Mandatory UUID).
- `src/Services/`: Business logic orchestrators.
- `src/Providers/`: Service registration and boot logic.
- `src/Http/`: Controllers and Middleware.
- `src/Livewire/`: Presentation components.
- `database/`: Migrations, Factories, and Seeders.
- `lang/`: Localization keys (ID/EN).
- `resources/`: Blade views and assets.

---

## 2. Configuration & Construction Protocols

### 2.1 Automated Scaffolding (Stubs)

Modular artifacts must be generated via the **[Automated Tooling](../development-tooling.md)** to ensure
compliance with the Internara structural baseline. Custom stubs located in `stubs/modules/` are
utilized to enforce strict typing and DocBlock standards upon generation.

### 2.2 Auto-Discovery

- **Enabled**: Translations and migrations are automatically discovered to reduce provider
  boilerplate.
- **Gating**: Component discovery is strictly restricted to **Enabled** modules via the
  `modules_statuses.json` registry.

---

## 3. Mandatory Isolation Protocols

The modular engine is utilized to enforce **Strict Domain Isolation**:

- **Persistence Invariant**: No physical foreign key constraints across modular boundaries. Use
  indexed UUID columns and SLRI protocols.
- **Logic Invariant**: Cross-module interaction is restricted to **Service Contracts** resolved via
  the Laravel Service Container.
- **UI Composition**: Use of the **Slot Injection** pattern via the `UI` module for cross-module
  frontend integration.

---

## 4. Verification Protocols

Each module is considered an independent verification unit.

- **Command**: `php artisan module:test {ModuleName}`.
- **Architecture Tests**: Every module must include Pest Arch tests to verify that it does not leak
  dependencies into unauthorized domains.

---

_By strictly governing the modular engine, Internara ensures a resilient, decoupled, and analysable
architecture that supports high-velocity developmental iterations._
