# Mocking Strategy: Isolate & Conquer

To maintain the **Isolation Principle** of our Modular Monolith, Internara utilizes mocking to
verify logic without triggering side effects in other modules. This ensures that unit tests remain
fast and focused on a single responsibility.

---

## 1. Mocking Services (Contracts)

Since we utilize **Auto-Discovery** and type-hint **Contracts**, mocking a dependency is
straightforward via Laravel's Service Container.

### 1.1 Standard Service Mock

```php
it('calls the user service when creating a profile', function () {
    // 1. Create a mock of the Contract
    $userService = mock(UserServiceInterface::class);

    // 2. Define expectations
    $userService->shouldReceive('create')->once()->andReturn(new User());

    // 3. Swap the implementation in the container
    app()->instance(UserServiceInterface::class, $userService);

    // 4. Run the logic
    $profileService->setupForNewUser($data);
});
```

---

## 2. Mocking Global Helpers & Time

Sometimes logic depends on system state, such as current time or configuration.

### 2.1 Time Travel

Use Pest's time helpers to test logic with expiration dates (e.g., Attendance check-in).

```php
it('fails to check-in after the deadline', function () {
    freezeTime('2026-01-19 09:00:00'); // Past the deadline

    expect($service->canCheckIn())->toBeFalse();
});
```

### 2.2 Mocking Settings

```php
it('respects the global late threshold', function () {
    setting(['late_threshold' => 15]);

    // Test logic...
});
```

---

## 3. Best Practices

1.  **Don't Mock Models**: Eloquent models are internal state. Mocking them leads to brittle tests.
    Use Factories and `RefreshDatabase` instead.
2.  **Mock Interfaces, Not Classes**: Always mock the **Contract**. This ensures your test is
    independent of the concrete implementation.
3.  **Use `Spy` for Side Effects**: If you only need to verify that a method was called (e.g.,
    sending a notification), use a Spy instead of a strict Mock.

---

_Mocking is our primary tool for enforcing module boundaries during testing. By isolating
dependencies, we ensure that a bug in one module doesn't cause a cascade of failing tests across the
entire suite._
