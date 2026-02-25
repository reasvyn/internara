# Application Blueprint: Project Genesis (ARC01-INIT-01)

**Series Code**: `ARC01-INIT` | **Scope**: `Infrastructure & Foundational Engine` | **Compliance**: `ISO/IEC 12207`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the creation of the authoritative *Modular Monolith* infrastructure required to satisfy **[SYRS-NF-601]** (Isolation), **[SYRS-NF-602]** (TALL Stack), and **[SYRS-NF-504]** (Identity Integrity).
- **Objective**: To establish the "Engine Room" of Internara. This phase defines the behavioral protocols, technical utilities, and systemic invariants that will govern every subsequent domain module.
- **Rationale**: A disciplined foundation prevents architectural decay. By formalizing infrastructure before domain logic, we ensure a predictable and secure engineering environment.

---

## 2. Logic & Architecture (The Modular Engine)

### 2.1 Autonomous Module Discovery
- **Mechanism**: Implementation of an autonomous module loader that detects and initializes modules within the `modules/` directory without requiring manual registration in the central application core.
- **Namespace Invariant**: Strict enforcement of the **"src Omission"** rule (omitting the `src` segment in namespaces) to align modular structures with standard Laravel conventions and reduce cognitive load.

### 2.2 Standardized Service Orchestration
- **The EloquentQuery Engine**: Implementation of `Modules\Shared\Services\EloquentQuery` as the single standard for query and persistence operations.
    - **Features**: Automatic relational searching, type-safe filtering, and transparent caching via the `remember()` method.
- **Contract-First Communication**: All inter-module interactions MUST occur via **Service Contracts** (Interfaces). The use of external concrete classes is strictly prohibited.

---

## 3. Data Architecture (Persistence Invariants)

### 3.1 Identity & State
- **UUID v4 Invariant**: Mandatory usage of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) as the primary identity for all domain entities to prevent ID enumeration attacks **[SYRS-NF-504]**.
- **Auditable Lifecycle**: Implementation of `HasStatus` (via the `Status` module) to track and audit entity state transitions ("who", "when", "why") as an immutable audit trail.

### 3.2 Software-Level Referential Integrity (SLRI)
- **Physical Isolation**: Prohibition of physical foreign key constraints across module boundaries at the database level.
- **Service Verification**: Referential integrity is manually verified at the **Service Layer** utilizing indexed UUID columns to ensure modular portability.

---

## 4. Security & Governance (Access Control)

### 4.1 RBAC Baseline
- **Least Privilege**: Implementation of a Role-Based Access Control (RBAC) engine (via the `Permission` module) governed by the principle of minimum necessary permissions.
- **Explicit Deny**: Access is denied by default unless explicitly granted through a **Policy Class** associated with each domain model.

### 4.2 Privacy & Hardening
- **PII Encryption**: Usage of the `encrypted` cast on Eloquent models for PII data (phone, address, national identifiers) to protect data at rest **[SYRS-NF-503]**.
- **Forensic Logging**: Integration of the `Log` module with automated masking processors to redact sensitive information in all logging sinks.

---

## 5. Presentation & UX (The Face of Internara)

### 5.1 Slot Injection Pattern
- **Decoupled UI**: Implementation of the `SlotRegistry` within the `UI` module to allow domain modules to inject UI elements (menus, widgets, buttons) into global layouts without physical coupling.

### 5.2 Responsive & Mobile-First
- **Layout Tiering**: Standardization of the layout structure (Base -> Page -> Component) utilizing Tailwind v4 to ensure a consistent **Mobile-First** experience **[SYRS-NF-401]**.

---

## 6. Verification Strategy (V&V View)

### 6.1 Mirroring Invariant
- **Standard**: The `tests/` directory MUST mirror the `src/` structure 1:1.
- **Tooling**: Utilization of **Pest v4** for unit and feature testing with a target of >90% behavioral coverage.

### 6.2 Architecture Audit
- **Isolation Enforcement**: Utilization of Pest Arch to ensure that the `Shared` and `Core` modules remain domain-agnostic and that no isolation leaks occur between domain modules.

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Modular infrastructure is operational; autonomous loader is active; standardized CRUD patterns are verified.
- **Quality Gate**: 100% pass rate in **`composer test`** and **`composer lint`**. Zero violations in PHPStan Level 8 static analysis.

---

_This blueprint constitutes the authoritative engineering record. Every technical modification must be validated against these Genesis principles._
