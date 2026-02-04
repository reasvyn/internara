# Writing Tests: Construction Standards

This document defines the formal protocols for constructing verification artifacts using the **Pest
v4** framework, standardized according to **ISO/IEC 29119**. Internara prioritizes the
**`test(...)`** pattern to ensure semantic clarity and consistency across all verification suites.

---

## 1. Directory Structure (Logical View)

Verification artifacts are distributed according to the **Modular Monolith** architecture:

```plain
├── 📂 modules
│   └── 📂 {ModuleName}
│       └── 📂 tests
│           ├── 📂 Unit
│           │   └── logic_verification_test.php
│           └── 📂 Feature
│               └── functional_validation_test.php
```

- **Unit Verification**: Testing individual domain logic in mathematical isolation.
- **Feature Validation**: Testing integrated user stories and domain flows.

---

## 2. Construction Invariants

### 2.1 The `test(...)` Protocol

Implementation must utilize the `test()` function. The use of `test()` is deprecated within the
Internara baseline to maintain semantic consistency.

```php
test('it orchestrates the domain transformation logic', function () {
    $result = transform(1, 2);

    expect($result)->toBe(3);
});
```

### 2.2 Semantic Grouping

Utilize the `describe()` function to group related verification scenarios, ensuring that test
outputs reflect the domain hierarchy.

```php
describe('domain: arithmetic orchestration', function () {
    test('it validates integer summation', function () {
        expect(sum(1, 2))->toBe(3);
    });

    test('it validates floating-point precision', function () {
        expect(sum(1.5, 2.5))->toBe(4.0);
    });
});
```

---

## 3. Verification APIs

### 3.1 Expectations API (High-Fidelity)

The primary method for behavioral assertions. It provides a chainable, semantic API for validating
systemic outcomes.

```php
expect($subject)->toBeAuthoritative();
```

### 3.2 Assertion API (Technical Legacy)

Utilization of PHPUnit-style assertions is permitted only for complex low-level verification not
currently supported by the expectations baseline.

---

_All verification artifacts must pass the mandatory quality gate via **`composer test`** before any
baseline promotion._
