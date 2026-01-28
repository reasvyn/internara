# Mocking Strategy: Dependency Isolation Standards

This document defines the **Dependency Mocking** protocols for the Internara project, standardized
according to the **[Architecture Description](../internal/architecture-description.md)**. Mocking is
utilized to enforce **Strict Modular Isolation**, enabling the verification of domain logic without
triggering side-effects across module boundaries.

---

## 1. Mocking Service Contracts (The Authoritative Method)

Internara utilizes constructor-based injection of **Service Contracts**. Verification suites must
leverage Laravel's Service Container to swap concrete implementations with verified mocks.

### 1.1 Standard Contract Mocking

```php
test('it orchestrates user creation via the service contract', function () {
    // 1. Create a mock of the Service Contract (Interface)
    $userService = mock(UserService::class);

    // 2. Define behavioral expectations
    $userService->shouldReceive('create')->once()->andReturn(new User());

    // 3. Register the mock baseline in the Service Container
    app()->instance(UserService::class, $userService);

    // 4. Execute the orchestrating logic
    $registrationService->registerNewStudent($data);
});
```

---

## 2. Temporal & Environmental Orchestration

Certain domain rules depend on systemic state, such as temporal invariants or configuration
baselines.

### 2.1 Temporal Verification (Time Travel)

Utilize Pest's temporal helpers to verify time-sensitive logic (e.g., Attendance deadlines).

```php
test('it rejects check-in attempts following the temporal deadline', function () {
    freezeTime('2026-01-28 09:00:00'); // Post-deadline baseline

    expect($service->canCheckIn())->toBeFalse();
});
```

### 2.2 Configuration Baselining

```php
test('it respects the modular late-threshold setting', function () {
    setting(['late_threshold' => 15]);

    // Behavioral verification logic...
});
```

---

## 3. Construction Invariants for Mocking

- **Interface Invariant**: Always mock the **Service Contract**, never the concrete class. This
  ensures verification is independent of internal implementation details.
- **Model Invariant**: Direct mocking of Eloquent models is prohibited. Utilize **Factories** and
  the `RefreshDatabase` concern to manage persistence state.
- **Side-Effect Verification**: Utilize **Spies** when only the occurrence of an event (e.g.,
  Notification dispatch) needs to be verified without strict return expectations.

---

_By strictly isolating dependencies through mocking, Internara ensures that verification suites
remain performant, focused, and resilient to changes in external modules._
