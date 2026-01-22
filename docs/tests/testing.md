# Testing Philosophy: Engineering Reliability

Internara prioritizes a "Security-First" and "Spec-Driven" testing strategy. We use the **Pest**
testing framework to ensure that every feature is functional, localized, and secure.

> **Governance Mandate:** Testing is the primary mechanism for verifying fulfillment of the
> **[Internara Specs](../internal/internara-specs.md)**. No feature is complete without passing
> Spec Validation tests.

---

## 1. Testing Framework: Pest PHP (v4)

We use Pest for its expressive syntax and deep integration with Laravel.

- **Expressive**: Tests read like natural language.
- **Speed**: Built-in support for parallel execution.
- **Architecture**: Enforces modular boundaries via Arch Testing (no cross-module leaks).

---

## 2. Test Categories

### 2.1 Unit Tests
Testing individual methods or logic in absolute isolation.
- **Focus**: Services, Model Concerns, and Enums.
- **Location**: `modules/{ModuleName}/tests/Unit/{Layer}/`.

### 2.2 Feature Tests
Testing the full interaction flow, including UI and database.
- **Focus**: Livewire components, Services, and HTTP routes.
- **Location**: `modules/{ModuleName}/tests/Feature/{Layer}/`.

---

## 3. Mandatory Verification Patterns

No feature is considered "Done" until it passes:

1.  **Spec Compliance**: Verifies behavior matches `internara-specs.md`.
2.  **Role-Based Access**: Verifies that only the designated User Roles can access the feature.
3.  **Multi-Language (i11n)**: Verifies that all user-facing strings are correctly localized (ID/EN).
4.  **Boundary Integrity**: Verifies no physical cross-module foreign keys or unauthorized service leaks.

---

## 4. Running Tests

### 4.1 Global Suite
```bash
php artisan test --parallel
```

### 4.2 Modular Suite
```bash
php artisan test --filter=ModuleName
```

---

_Testing is the executable documentation of our commitment to quality and the Internara Specs._