# Support Module

The `Support` module provides the **Operational Bridge** between Internara's architectural standards
and environmental reality. It centralizes development tooling, automated scaffolding, and complex
administrative utilities.

> **Governance Mandate:** This module implements the infrastructure required to satisfy
> **[SYRS-C-003]** (Service Layer Logic). All implementation must adhere to the
> **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Domain Domains

### 1.1 Scaffolding Domain

Provides custom Artisan generators that enforce the **Modular DDD**, **src Omission**, and
**Finality** conventions during development.

- `module:make-class`: Generates a final PHP class with direct namespacing.
- `module:make-interface`: Generates a contract in the appropriate domain folder.
- `module:make-trait`: Generates a reusable concern.
- `module:make-dusk`: Generates browser testing boilerplate.

### 1.2 Testing Domain

- **`AppTestCommand`**: An orchestrated test runner (`php artisan app:test`) that executes modular
  test suites sequentially to optimize memory usage.

---

## 2. Engineering Standards

- **Environment Awareness**: This module is allowed to interact with low-level system resources
  (Filesystem, Shell) to facilitate automation.
- **Service Orchestration**: Complex utilities (like Onboarding) must delegate specific attribute
  updates to the respective domain modules (User, Student, Teacher).

---

## 3. Verification & Validation (V&V)

- **Feature Tests**: Validating mass CSV import workflows and generator output consistency.
- **Command Tests**: Verifying the orchestrated sequential execution of test segments.
- **Command**: `php artisan test modules/Support`

---

_The Support module shields business domains from operational complexity through automation._
