# Laravel Verification: Framework Integration Standards

This guide formalizes the protocols for utilizing **Laravel's testing utilities** within the 
Internara verification lifecycle, adhering to **ISO/IEC 29119**.

---

## 1. The Verification Baseline (Pest v4)

Internara utilizes **Pest v4** as the primary orchestration engine. All tests must demonstrate 
compliance with the **Mirroring Invariant** (tests must reside in `tests/` mirroring the `src/` 
hierarchy).

### 1.1 Global Configuration (`tests/Pest.php`)

Shared logic and traits are centralized in the global configuration file. 
- **Infrastructure**: All feature tests must extend `Tests\TestCase`.
- **Persistence**: Use the `RefreshDatabase` trait for tests impacting the database state.

---

## 2. Integration Patterns

### 2.1 The Service Container Invariant

To ensure deterministic verification and modular isolation, all dependencies must be resolved 
via the **Service Container**.

- **Protocol**: Inject **Service Contracts** (Interfaces) into your test closures.
- **Mocking**: Utilize `this->mock()` or `this->spy()` to intercept cross-module communication 
  via contracts.

### 2.2 Event & Notification Verification

For cross-module side-effects, utilize Laravel's faking mechanisms:

```php
test('it dispatches the internship registered event', function () {
    Event::fake();

    $this->internshipService->register($data);

    Event::assertDispatched(InternshipRegistered::class);
});
```

---

## 3. Execution Protocols

- **Sequential Execution**: Use `php artisan app:test` to run tests module-by-module.
- **Code Coverage**: Target a minimum of **90% behavioral coverage**.
- **Static Audit**: Ensure tests pass `composer lint` for stylistic consistency.

---

_By strictly governing framework integration, Internara ensures a high-fidelity, maintainable, 
and reliable verification ecosystem._
