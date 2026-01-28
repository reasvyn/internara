# Testing & Verification Guide: V&V Standards

This document formalizes the **Verification & Validation (V&V)** protocols for the Internara
project, adhering to **IEEE Std 1012** (Standard for System, Software, and Hardware Verification and
Validation) and **ISO/IEC 29119** (Software Testing). It defines the methodologies for ensuring
technical correctness and requirement fulfillment.

> **Governance Mandate:** All software artifacts must demonstrate fulfillment of the requirements
> defined in the **[System Requirements Specification](system-requirements-specification.md)**.
> Tests serve as the executable proof of engineering rigor.

---

## 1. V&V Philosophy: Traceable Construction

Internara adopts a **TDD-First** and **Traceability-Driven** testing philosophy.

- **Traceability**: Every test suite must be traceable to a specific requirement in the System Requirements Specification or an
  architectural invariant in the AD.
- **Independence**: Verification artifacts must verify domain behavior while respecting the **Strict
  Isolation** invariants of the Modular Monolith.
- **Regression Prevention**: Automated verification is mandatory before any baseline promotion.

---

## 2. Testing Hierarchy (The V-Model View)

| Level            | Focus                 | Objective                               | Implementation                  |
| :--------------- | :-------------------- | :-------------------------------------- | :------------------------------ |
| **Unit**         | Component Isolation   | Verify logic in mathematical isolation. | Pest (Mocking all dependencies) |
| **Integration**  | Service Contracts     | Verify inter-module communication.      | Feature tests via Contracts     |
| **System**       | End-to-End User Flow  | Validate fulfillment of user stories.   | Feature tests (HTTP/Livewire)   |
| **Architecture** | Structural Invariants | Enforce modular isolation policies.     | Pest Arch Plugin                |

---

## 3. Mandatory Testing Patterns

### 3.1 Architecture Invariants (Isolation Enforcement)

To maintain modular integrity, we utilize **Architecture Testing** to prevent unauthorized
cross-module coupling.

```php
arch('module isolation')
    ->expect('Modules\Internship')
    ->not->toUse('Modules\User\Models')
    ->because('Domain modules must interact with external models via Service Contracts.');
```

### 3.2 Localization & i11n Validation

User-facing artifacts must be verified across all supported locales (`id`, `en`) to satisfy
**[SYRS-NF-403]**.

```php
test('it returns localized verification error', function () {
    app()->setLocale('id');
    expect(__('validation.required', ['attribute' => 'nama']))->toBe('nama wajib diisi.');
});
```

### 3.3 Authorization (RBAC) Verification

Access rights must be verified for every role defined in the System Requirements Specification to satisfy **[SYRS-NF-502]**.

---

## 4. Test Construction & State Management

### 4.1 Layered Placement

- **Feature Tests**: `modules/{Module}/tests/Feature/{Livewire|Services|Api}`
- **Unit Tests**: `modules/{Module}/tests/Unit/{Models|Enums|Support}`

### 4.2 Cross-Module State Setup

- **Factory Resolution**: Use factories resolved from the target module to ensure valid state
  initialization without concrete coupling. Direct model instantiation of foreign entities is
  prohibited.

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

_Non-compliant artifacts (untested or failing) are considered architectural defects and will be
rejected during the verification phase._
