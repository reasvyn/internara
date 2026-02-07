# Database Verification: Persistence Integrity Standards

This document formalizes the **Persistence Verification** protocols for the Internara project,
standardized according to **ISO/IEC 25010** (Reliability). Database tests verify the structural
integrity of schemas, the behavior of shared model concerns, and the accuracy of complex query
scoping within the modular monolith framework.

> **Governance Mandate:** All persistence logic must demonstrate compliance with the
> **[Data Integrity Protocols](../patterns.md)** through rigorous automated verification.

---

## 1. Structural Verification (Schema Invariants)

Verification artifacts must ensure that database migrations correctly implement the technical
requirements defined in the System Requirements Specification.

### 1.1 Identity Invariant: UUID Verification

```php
test('it implements uuid v4 identification', function () {
    $model = User::factory()->create();
    expect(Str::isUuid($model->id))->toBeTrue();
});
```

### 1.2 Isolation Invariant: No Physical Foreign Keys

Verification must confirm the absence of cross-module physical constraints, ensuring modular
portability as defined in the **[Architecture Description](../architecture.md)**.

---

## 2. Behavioral Verification: Shared Concerns

Verification of the `Shared` module concerns must be performed within each domain context to ensure
consistent application of global business rules.

### 2.1 Lifecycle State Transitions

```php
test('it captures an immutable audit trail for status modifications', function () {
    $registration = InternshipRegistration::factory()->create();
    $registration->setStatus('verified', 'Analytical justification');

    expect($registration->statuses)->toHaveCount(2);
});
```

### 2.2 Temporal Scoping Invariant

```php
test('it filters operational data by the active academic cycle', function () {
    setting(['active_academic_year' => '2025/2026']);
    $journal = Journal::factory()->create();

    expect($journal->academic_year)->toBe('2025/2026');
});
```

---

## 3. Construction Invariants for Persistence Tests

- **Environment Isolation**: Mandatory use of the `RefreshDatabase` trait to ensure zero state
  contamination between verification cycles.
- **Strict Isolation**: Cross-module relationship testing must utilize manual ID assignment to
  prevent concrete factory coupling.
- **V&V Mandatory**: All persistence tests must pass the **`composer test`** gate.

---

_Database verification is the critical defense against data corruption and architectural decay,
ensuring the systemic reliability of the Internara platform._
