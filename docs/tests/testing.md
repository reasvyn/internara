# Testing Philosophy: Engineering Reliability

Internara prioritizes a "Security-First" and "Behavior-Driven" testing strategy. We use the **Pest**
testing framework to ensure that every feature is not only functional but also secure against common
modular monolith vulnerabilities like IDOR and broken access control.

---

## 1. Testing Framework: Pest PHP (v4)

We use Pest for its expressive syntax and deep integration with Laravel.

- **Expressive**: Tests read like natural language.
- **Speed**: Built-in support for parallel execution.
- **Architecture**: Allows us to enforce modular boundaries via Arch Testing.

---

## 2. Test Categories

We categorize tests by their scope and purpose.

### 2.1 Unit Tests

Testing individual methods or business logic in isolation.

- **Focus**: Services and Model Concerns.
- **Location**: `modules/{ModuleName}/src/tests/Unit/`.

### 2.2 Feature Tests

Testing the full interaction flow, including UI states and database persistence.

- **Focus**: Livewire components and HTTP routes.
- **Location**: `modules/{ModuleName}/src/tests/Feature/`.

### 2.3 Browser Tests

Simulating real user interaction in a headless browser.

- **Focus**: Complex multi-step forms and client-side reactive components.

---

## 3. Mandatory Verification

No feature is "Done" until it passes the following:

1.  **Authorization Test**: Verifies that only authorized roles/users can access the logic.
2.  **Validation Test**: Verifies that improper data is rejected with correct error codes.
3.  **Boundary Test**: Verifies that a module cannot access data from another module without using a
    Contract.

---

## 4. Running Tests

### 4.1 Global Suite

Run all tests in parallel for maximum speed.

```bash
php artisan test --parallel
```

### 4.2 Modular Suite

Run tests for a specific module only.

```bash
./vendor/bin/pest modules/User/src/tests
```

---

_Testing is documentation for code behavior. High-quality tests allow us to refactor with confidence
and ensure a stable experience for our users._
