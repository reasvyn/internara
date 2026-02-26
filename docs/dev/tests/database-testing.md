# Persistence Verification: Data Integrity Standards

This guide formalizes the protocols for verifying the **Persistence Layer** and ensuring data
integrity according to **ISO/IEC 25010** (Reliability).

---

## 1. Schema & Migration Verification

Every migration must be verified to ensure it satisfies the systemic invariants.

- **UUID Invariant**: Primary keys must be binary or string UUIDs.
- **Zero Cross-Module Keys**: No physical foreign keys (`foreignId`) are permitted across module
  boundaries. Use `string('foreign_uuid')` instead.

### 1.1 Writing Schema Tests

```php
test('it has the correct schema structure', function () {
    expect('users')->toHaveColumns(['uuid', 'email', 'password', 'created_at']);
});
```

---

## 2. Model & Factory Integrity

### 2.1 The `HasUuid` Requirement

Models must be verified to ensure they correctly implement the `Shared\Models\Concerns\HasUuid`
concern.

### 2.2 Factory Reliability

Factories must be deterministic and capable of creating valid, ready-to-use entities.

```php
test('the student factory is valid', function () {
    $student = Student::factory()->create();

    expect($student->uuid)->toBeUuid()->and($student->user)->toBeInstanceOf(User::class);
});
```

---

## 3. Software-Level Referential Integrity (SLRI)

Because physical foreign keys are prohibited across modules, we must verify that the **Service
Layer** correctly orchestrates data relationships.

- **Existence Check**: Verify that services throw `EntityNotFoundException` when a referenced UUID
  from another module does not exist.
- **Deletion Protocols**: Verify that deleting a parent record triggers the appropriate consequence
  (Restriction, Cascade, or Nullification) in related modules.

---

_Data is the systemic record. Verification ensures its integrity is absolute._
