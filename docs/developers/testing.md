# Testing & Verification Guide: Engineering Reliability

This document formalizes the **Verification & Validation (V&V)** protocols for the Internara
project, adhering to **IEEE Std 1012** (Standard for System, Software, and Hardware Verification and
Validation) and **ISO/IEC 29119** (Software Testing). It defines the methodologies for ensuring
technical correctness and requirement fulfillment.

> **Governance Mandate:** Testing is the authoritative mechanism for validating fulfillment of the
> **[System Requirements Specification](specs.md)**. All software artifacts must demonstrate 
> fulfillment of the requirements defined in the SSoT. Construction is incomplete without a 
> formal verification pass.

---

## 1. V&V Philosophy: Traceable Construction

Internara adopts a **Spec-Driven**, **TDD-First**, and **Isolation-Aware** verification strategy.

- **Traceability**: Every test suite must be traceable to a specific requirement in the System
  Requirements Specification or an architectural invariant in the Architecture Description.
- **Independence**: Verification artifacts must verify domain behavior while respecting the **Strict
  Isolation** invariants. Tests should interact with external domains exclusively via their **Service
  Contracts**.
- **Minimized Mocking**: To reduce the risk of "mock-gap" bugs and ensure realistic verification, the
  use of real **Service Contracts** is preferred over `Mockery` for cross-module integration.
  Mocking should be reserved for high-overhead infrastructure (External APIs, Mail, etc.).

---

## 2. Verification Framework: Pest PHP v4

Pest is the foundational tool for our **Verification & Validation (V&V)** activities.

- **Traceability**: Enables mapping of tests to specific SyRS requirements.
- **Velocity**: Support for parallel execution via the optimized verification suite.
- **Architectural Guard**: Enforces modular boundaries via Architecture (Arch) Testing.

---

## 3. Testing Hierarchy (The V-Model View)

| Level            | Focus                 | Objective                               | Implementation                  |
| :--------------- | :-------------------- | :-------------------------------------- | :------------------------------ |
| **Unit**         | Component Isolation   | Verify logic in mathematical isolation. | Pest (Mocking internal deps)    |
| **Integration**  | Service Contracts     | Verify inter-module communication.      | Contracts & Public Concrete     |
| **System**       | End-to-End User Flow  | Validate fulfillment of user stories.   | Feature tests (HTTP/Livewire)   |
| **Architecture** | Structural Invariants | Enforce modular isolation policies.     | Pest Arch Plugin                |

### 3.1 Structural Placement
- **Unit Verification**: Testing atomic logic (Services, Concerns, Enums) in isolation.
    - **Location**: `modules/{Module}/tests/Unit/{Layer}/`.
- **Feature Validation**: Testing integrated user stories and domain flows.
    - **Location**: `modules/{Module}/tests/Feature/{Layer}/`.

---

## 4. Mandatory Testing Patterns

### 4.1 Architecture Invariants (Isolation Enforcement)

To maintain modular integrity, we utilize **Architecture Testing** to prevent unauthorized
cross-module coupling.

```php
arch('module isolation')
    ->expect('Modules\Internship')
    ->not->toUse('Modules\User\Models')
    ->because('Domain modules must interact with external models via Service Contracts.');
```

### 4.2 Localization & i18n Validation

User-facing artifacts must be verified across all supported locales (`id`, `en`) to satisfy
**[SYRS-NF-403]**.

```php
test('it returns localized verification error', function () {
    app()->setLocale('id');
    expect(__('validation.required', ['attribute' => 'nama']))->toBe('nama wajib diisi.');
});
```

### 4.3 Authorization (RBAC) Verification

Access rights must be verified for every role defined in the System Requirements Specification to
satisfy **[SYRS-NF-502]**.

---

## 5. Execution & Quality Gates

The following command is the **Mandatory Verification Gate** and must be executed in full before any
repository synchronization:

```bash
# Full System Verification
composer test
```

- **Pass Criteria**: 100% success rate on all test suites.
- **Coverage Requirement**: Minimum 90% behavioral coverage for domain modules.

---

## 6. Resource Optimization & Memory Management

To ensure stability in memory-constrained environments, the following protocols are mandatory.

### 6.1 Aggressive Lifecycle Cleanup
- **Implementation**: The `tearDown()` method must invoke `$this->app->flush()` and
  `gc_collect_cycles()`.
- **Mocking**: Minimize the use of `Mockery::mock` for large service classes.

### 6.2 The `app:test` Orchestrator
Developers should utilize the `php artisan app:test` command for sequential module execution. It ensures that the memory heap is reset between module runs, effectively capping memory usage to the requirements of a single module.

---

_Non-compliant artifacts (untested, leaking, or failing) are considered architectural defects and
will be rejected during the verification phase._