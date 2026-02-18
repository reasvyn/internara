# Testing & Verification Guide: Engineering Reliability

This document formalizes the **Verification & Validation (V&V)** protocols for the Internara
project, adhering to **IEEE Std 1012** (Standard for System, Software, and Hardware Verification and
Validation) and **ISO/IEC 29119** (Software Testing). It defines the methodologies for ensuring
technical correctness and requirement fulfillment.

> **Governance Mandate:** Testing is the authoritative mechanism for validating fulfillment of the
> **[System Requirements Specification](specs.md)**. All software artifacts must demonstrate
> fulfillment of the requirements defined in the SSoT. Construction is incomplete without a formal
> verification pass.

---

## 1. V&V Philosophy: Traceable Construction

Internara adopts a **Spec-Driven**, **TDD-First**, and **Isolation-Aware** verification strategy.

- **Traceability**: Every test suite must be traceable to a specific requirement in the SyRS or an
  architectural invariant in the Architecture Description.
- **Independence**: Verification artifacts must verify domain behavior while respecting the **Strict
  Isolation** invariants. Tests should interact with external domains exclusively via their
  **Service Contracts**.
- **Minimized Mocking**: To reduce the risk of "mock-gap" bugs and ensure realistic verification,
  the use of real **Service Contracts** is preferred over `Mockery` for cross-module integration.
  Mocking should be reserved for high-overhead infrastructure (External APIs, Mail, etc.).

---

## 2. Infrastructure & Configuration

The testing environment is orchestrated through three core files that ensure consistency and modular
scalability.

### 2.1 Environmental Testing Standards

To ensure predictable behavior across different runtime states, tests must utilize the standard
environment helpers.

- **Detection**: Use `is_testing()` to verify the application is correctly identifying the test
  environment.
- **Conditional Logic**: Avoid using `isDebugMode()` or `isDevelopment()` to drive test logic. Tests
  should aim to verify production-like behavior unless explicitly testing diagnostic features.
- **Maintenance Mode**: When testing system resilience, use `is_maintenance()` to verify that the
  application responds correctly to maintenance lockdowns.

### 2.1 `phpunit.xml` (Environment Blueprint)

The **PHPUnit Configuration** defines the global testing environment, including database drivers
(SQLite in-memory), memory limits, and the discovery paths for modular test suites.

- **Role**: Bootstraps the testing framework and manages environment variables.

### 2.2 `tests/Pest.php` (Verification Scope)

The **Pest Configuration** defines the "rules of engagement" for all tests. It configures the base
test classes, traits (e.g., `RefreshDatabase`), and the file paths where Pest should be active.

- **Role**: Standardizes test execution across root and modular boundaries.

### 2.3 `tests/TestCase.php` (Aggressive Cleanup)

The **Base Test Case** implements strict memory management and lifecycle hooks to ensure stability
in constrained environments.

- **Role**: Enforces the **Aggressive Lifecycle Cleanup** mandated in section 6.1.

---

## 3. Testing Hierarchy (The V-Model View)

| Level            | Focus                 | Objective                               | Implementation                |
| :--------------- | :-------------------- | :-------------------------------------- | :---------------------------- |
| **Unit**         | Component Isolation   | Verify logic in mathematical isolation. | Pest (Mocking internal deps)  |
| **Integration**  | Service Contracts     | Verify inter-module communication.      | Contracts & Public Concrete   |
| **System**       | End-to-End User Flow  | Validate fulfillment of user stories.   | Feature tests (HTTP/Livewire) |
| **Browser**      | Visual & UX Integrity | Verify UI/UX and frontend behavior.     | Laravel Dusk (Preferred)      |
| **Architecture** | Structural Invariants | Enforce modular isolation policies.     | Pest Arch Plugin              |

### 3.1 Structural Placement

To ensure high discoverability and organization, the directory structure within the `tests` folder
MUST mirror the internal **Modular DDD** structure of the module's `src` directory.

- **Unit Verification**: Testing atomic logic (Services, Concerns, Enums) in isolation.
    - **Location**: `modules/{Module}/tests/Unit/({Domain}/){Layer}/`
- **Feature Validation**: Testing integrated user stories and domain flows.
    - **Location**: `modules/{Module}/tests/Feature/({Domain}/){Layer}/`
- **Browser Validation**: Verifying frontend behavior, UI motion, and UX requirements.
    - **Location**: `modules/{Module}/tests/Browser/({Domain}/){Layer}/`

#### 3.1.1 Placement Rules

1.  **Mirroring Invariant**: The test path must exactly mirror the `src` path, replacing `src` with
    `tests/{Level}`.
2.  **Domain-to-Module Exception**: If the **Domain Name** is identical to the **Module Name**, the
    domain folder must be omitted in the test structure.
    - _Example_: `modules/User/tests/Unit/Models/UserTest.php` (✅ Correct)
3.  **Namespace Alignment**: Test namespaces must follow the same omission rules as source
    namespaces, typically starting with `Modules\{Module}\Tests\{Level}\...`.

---

## 4. Specialized Testing Guides

For detailed technical implementation of specific testing patterns, refer to the following
authoritative guides:

- **[Unit Tests](tests/pestphp/writing-tests.md)**: Guidelines for testing atomic units of logic.
- **[Laravel Testing Basics](tests/laravel-tests.md)**: Getting started guide for testing in the
  Laravel ecosystem.
- **[Feature Tests](tests/pestphp/writing-tests.md)**: Patterns for verifying integrated domain
  flows.
- **[HTTP Tests](tests/http-tests.md)**: Protocols for verifying API endpoints and controller
  responses.
- **[Console Tests](tests/console-tests.md)**: Verification of Artisan commands and CLI output.
- **[Dusk Browser Tests](tests/dusk-browser-test.md)**: Primary guide for browser automation via
  Laravel Dusk (Preferred for CPU compatibility).
- **[Browser Tests (Legacy)](tests/pestphp/browser-testing.md)**: Playwright-based verification (Use
  only if environment supports it).
- **[Database Tests](tests/database-testing.md)**: Strategies for verifying persistence and complex
  query logic.
- **[Mocking Standards](tests/mocking.md)**: Rules for when and how to utilize `Mockery` or Pest's
  native mocking.
- **[Livewire Tests](tests/livewire-tests.md)**: Specialized verification for reactive components.

---

## 5. Mandatory Testing Patterns

### 5.1 Architecture Invariants (Isolation Enforcement)

To maintain modular integrity, we utilize **Architecture Testing** to prevent unauthorized
cross-module coupling.

```php
arch('module isolation')
    ->expect('Modules\Internship')
    ->not->toUse('Modules\User\Models')
    ->because('Domain modules must interact with external models via Service Contracts.');
```

### 5.2 Localization & i18n Validation

User-facing artifacts must be verified across all supported locales (`id`, `en`) to satisfy
**[SYRS-NF-403]**.

```php
test('it returns localized verification error', function () {
    app()->setLocale('en');
    expect(__('validation.required', ['attribute' => 'name']))->toBe('The name field is required.');
});
```

---

## 6. Execution & Quality Gates

Verification is the primary safeguard against systemic regression. The following gates are mandatory
for any baseline promotion:

- **Pass Criteria**: 100% success rate on all test suites across all modules.
- **Coverage Requirement**: Minimum 90% behavioral coverage for all Domain and Core modules.
- **Static Analysis**: Zero violations in `composer lint` (Laravel Pint).

The following commands are the **Mandatory Verification Gates**:

- **Automated Testing**: Adherence to modular stability is mandatory. Run `composer test` prior to
  any repository synchronization.
- **Full System Verification**: Orchestrated sequential execution via `php artisan app:test`.

```bash
# Full System Verification
composer test
```

### 6.1 Aggressive Lifecycle Cleanup

To ensure stability in memory-constrained environments, verification artifacts must implement strict
resource management.

- **Implementation**: The `tearDown()` method in `TestCase.php` must invoke `$this->app->flush()`
  and `gc_collect_cycles()` to clear the service container and trigger garbage collection.
- **Mocking**: Minimize the use of complex `Mockery` objects for large-scale integration tests.

### 6.2 The `app:test` Orchestrator

Developers should utilize the `php artisan app:test` command for sequential module execution. It
ensures that the memory heap is reset between module runs, effectively capping memory usage to the
requirements of a single module.

---

_Non-compliant artifacts (untested, leaking, or failing) are considered architectural defects and
will be rejected during the verification phase._
