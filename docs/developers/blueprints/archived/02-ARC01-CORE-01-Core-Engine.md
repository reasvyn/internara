# Application Blueprint: Core Engine (ARC01-CORE-01)

**Series Code**: `ARC01-CORE-01` | **Status**: `Archived` (Done)

---

## 1. Strategic Context

- **Spec Alignment**: This configuration baseline implements the foundational **Architecture & Maintainability** ([SYRS-NF-601]) requirements of the authoritative **[Specs](../specs.md)**.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities
- **Modular Autoloader**: Automated discovery of modules and their service providers (via `nwidart/laravel-modules`).
- **Persistence Orchestrator**: Configuration of Eloquent to support UUID-based primary and foreign keys without physical constraints.
- **Developer Tooling**: Initialization of custom Artisan generators (`module:make-class`, etc.) to enforce project conventions.
- **Dynamic Configuration**: Provisioning of the global `setting()` helper and persistence infrastructure for runtime configuration management.

### 2.2 Service Contracts
- **EloquentQuery**: Standardized contract for repository-like domain services.

### 2.3 Data Architecture
- **Identity Invariant**: Systemic integration of the `HasUuid` concern across all domain models.
- **Namespacing**: Implementation of the **src-Omission** invariant (Namespace: `Modules\Name`, Path: `modules/Name/src`).

---

## 3. Presentation Strategy (User Experience View)

### 3.1 Interface Design
- **Alias Subsystem**: Centralized model aliasing via `AliasServiceProvider` to streamline internal references and UI data fetching.

### 3.2 Invariants
- **Structural Integrity**: Modular isolation during autodiscovery verified to ensure zero-coupling at the presentation layer.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record
- **Standardization**: Formalization of the **[Coding Conventions](../conventions.md)** and **[Architecture Description](../architecture.md)**.
- **API Catalog**: Documentation of the base `EloquentQuery` pattern for standardized CRUD.

### 4.2 Module Standards
- **Infrastructure Record**: Authoring of the initial `README.md` files for the `Shared` and `Core` modules.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 5.1 Realized Outcomes
- **UUID Invariant**: Successfully implemented via `Modules\Shared\Models\Concerns\HasUuid`. Verified usage in all core domain models.
- **src-Omission**: Correctly configured in `composer.json` and `config/modules.php`.
- **Shared Abstractions**: `EloquentQuery` base service successfully provisioned.
- **Alias Registry**: `AliasServiceProvider` active, providing global shorthand for all primary domain models. This infrastructure proved crucial for the rapid implementation of the **[Identity & Security (ARC01-USER-01)](03-ARC01-USER-01-Identity.md)** series.

### 5.2 Identified Anomalies & Corrections
- **Metadata Desync**: `app:info` command was found to report inconsistent version data (v0.4.0-alpha). **Correction**: Removed redundant `info` and `author` keys from `modules/Core/config/config.php` and standardized `AppInfoCommand` to prioritize `app_info.json` as the SSoT.
- **Alias Gaps**: The alias registry was missing several newer domain models. **Correction**: Synchronized `AliasServiceProvider` with the current module catalog.

### 5.3 Improvement Plan
- [x] Eliminate redundant metadata configuration in the Core module.
- [x] Standardize `app:info` output to reflect authoritative project data.
- [x] Expand `AliasServiceProvider` to cover all active domain entities.

---

## 6. Exit Criteria & Verification Protocols

- **Verification Gate**: 100% pass rate across the core infrastructure suites via **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Demonstrated modular isolation during autodiscovery.
    - Verified functionality of UUID identity generation.

---

## 7. Improvement Suggestions (Legacy)

- **Polymorphic Identity**: Realized via the `Profile` module in later series.
- **Privacy Hardening**: Implemented via `Masker` utility in the `Shared` module.
