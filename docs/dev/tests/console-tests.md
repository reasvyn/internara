# Infrastructure Verification: CLI & Orchestration Standards

This guide formalizes the protocols for verifying **System Orchestration** tools, specifically
**Artisan Commands** and background jobs, adhering to **ISO/IEC 12207**.

---

## 1. Artisan Command Verification

Orchestration tools must be verified for deterministic behavior and correct user feedback.

### 1.1 Feedback & Interaction

```php
test('it successfully generates a new module', function () {
    $this->artisan('module:make NewModule')
        ->expectsOutput('Module [NewModule] created successfully.')
        ->assertExitCode(0);
});
```

---

## 2. Background Process Orchestration

Verify that asynchronous tasks are correctly dispatched and handled by the queue system.

- **Protocol**: Use `Queue::fake()` to verify that the job was pushed with the correct payload
  (e.g., UUID of the target entity).
- **Execution**: Verify the job's internal logic by manually invoking the `handle()` method within a
  controlled test environment.

---

## 3. System Health & Metadata Verification

Specific infrastructure tests must verify the integrity of the **Product Identity**.

- **App Info Audit**: Verify that `app_info.json` exists and matches the expected Blueprint ID and
  author attribution.
- **Module Discovery**: Verify that the module loader correctly identifies and activates modules
  defined in `modules_statuses.json`.

---

_Infrastructure reliability ensures the operational stability of the Internara ecosystem._
