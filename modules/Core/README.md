# Core Module

The `Core` module serves as the authoritative repository for Internara-specific infrastructure,
foundational configurations, and specialized development tooling. It acts as the "glue" that
connects architectural invariants with business reality.

> **Governance Mandate:** This module implements the requirements defined in the authoritative **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

Unlike the `Shared` module which is strictly business-agnostic, the `Core` module is tailored to
Internara's specific domain requirements. It provides:

- **Foundational Services**: Domain-specific logic that spans multiple modules (e.g., Analytics).
- **Infrastructure Fallbacks**: Safe defaults for system-wide helpers.
- **Developer Experience (DX)**: Custom scaffolding tools that enforce project conventions.

---

## 2. Key Features

### 2.1 Localization Infrastructure

- **Middleware (`SetLocale`)**: Automatically manages application locale persistence based on
  session state, ensuring compliance with **[SYRS-NF-403]**.
- **Dual-Language Integrity**: Provides the primary fallback and structure for Indonesian and
  English support.

### 2.2 Analytics & Monitoring

- **`AnalyticsAggregator`**: A centralized service that orchestrates cross-module data to provide
  institutional summaries and identify "At-Risk" students based on engagement and assessment
  metrics.
    - _Contract_: `Modules\Core\Services\Contracts\AnalyticsAggregator`.

### 2.3 Dynamic Settings (Infrastructure)

- **`setting()` Fallback**: Provides a safe, non-functional fallback for the global `setting()`
  helper if the primary `Setting` module is unavailable, preventing system-wide fatal errors during
  early boot cycles.

### 2.4 Development Tooling (Artisan)

Provides custom generators that respect the **src Omission** and **Context-Aware Naming**
conventions:

- `module:make-class`: Generates a standard PHP class.
- `module:make-interface`: Generates a contract/interface.
- `module:make-trait`: Generates a reusable concern.
- `module:app-info`: Displays current system version and metadata.

---

## 3. Data Integrity & Persistence

The `Core` module manages foundational system tables required for application stability:

- **`cache`**: Centralized storage for application performance.
- **`jobs`**: Infrastructure for asynchronous task execution.
- **`statuses`**: Audit trail for entity state transitions (utilized by the `Status` module).

---

## 4. Verification & Validation (V&V)

Reliability is ensured through targeted unit testing:

- **Middleware Tests**: Verifies locale switching logic.
- **Service Tests**: Verifies analytics calculation and mock-driven orchestration.
- **Command Verification**: Ensures all custom generators are correctly registered and functional.

---

_The Core module is the structural heart of Internara. It must be maintained with high rigor to
ensure systemic stability._
