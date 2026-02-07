# Console Verification: CLI Infrastructure Standards

This document formalizes the **CLI Verification** protocols for the Internara project, standardized
according to **ISO/IEC 12207** (Infrastructure Process). Console tests verify that custom Artisan
commands produce deterministic output and execute intended side-effects without architectural
regression.

> **Governance Mandate:** All administrative orchestration tools must demonstrate compliance with
> the **[Automated Tooling Reference](../tooling.md)** through comprehensive automated verification.

---

## 1. Execution Verification (Command State)

Verification artifacts utilize the `artisan` helper to orchestrate command execution and assert
against the resulting systemic state.

### 1.1 Output Invariant Verification

```php
test('it displays the authoritative system metadata', function () {
    $this->artisan('app:info')
        ->expectsOutputToContain('Internara')
        ->expectsOutputToContain('ARC01')
        ->assertExitCode(0);
});
```

### 1.2 Side-Effect Invariant Verification

Verification must confirm that state-altering commands (e.g., RBAC synchronization) correctly modify
the persistence baseline.

```php
test('it synchronizes the modular permission baseline', function () {
    $this->artisan('permission:sync')->assertExitCode(0);

    expect(Permission::where('name', 'attendance.report')->exists())->toBeTrue();
});
```

---

## 2. Interactive Orchestration Verification

For commands requiring subject interaction (e.g., confirmation prompts), verification must simulate
valid and invalid inputs to ensure fault tolerance.

```php
test('it mandates confirmation prior to baseline cache destruction', function () {
    $this->artisan('app:flush-cache')
        ->expectsConfirmation('Proceed with systemic cleanup?', 'yes')
        ->assertExitCode(0);
});
```

---

## 3. Construction Invariants for Console Tests

- **Exit Code Invariant**: Successful execution must return exit code `0`. Analytical verification
  of error codes is mandatory for negative scenarios.
- **Dependency Isolation**: Infrastructure interactions (Email, API) must be mocked using the
  **[Mocking Strategy](mocking.md)**.
- **V&V Mandatory**: All CLI verification must pass the **`composer test`** gate.

---

_CLI verification ensures that the administrative maintenance interface of Internara remains
resilient and safe for operational orchestration._
