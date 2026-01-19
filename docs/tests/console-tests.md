# Console Testing: CLI Verification

Internara utilizes custom Artisan commands for critical administrative tasks like permission
synchronization and binding refreshes. Console tests verify that these commands produce the correct
output and perform the intended side effects.

---

## 1. Testing Command Execution

Use the `artisan` helper to trigger commands and assert their behavior.

### 1.1 Verifying Output

```php
it('displays the correct application version', function () {
    $this->artisan('app:info')
        ->expectsOutputToContain('Internara')
        ->expectsOutputToContain('v0.6.0')
        ->assertExitCode(0);
});
```

### 1.2 Verifying Side Effects

Some commands modify the database or configuration.

```php
it('synchronizes modular permissions', function () {
    $this->artisan('permission:sync')
        ->expectsOutputToContain('Synchronizing...')
        ->assertExitCode(0);

    expect(Permission::where('name', 'attendance.view')->exists())->toBeTrue();
});
```

---

## 2. Testing Interactive Commands

For commands that require user input (e.g., confirmation prompts).

```php
it('asks for confirmation before refreshing bindings', function () {
    $this->artisan('app:refresh-bindings')
        ->expectsConfirmation('Are you sure?', 'yes')
        ->assertExitCode(0);
});
```

---

## 3. Best Practices

1.  **Assert Exit Codes**: Always ensure the command exits with `0` (Success) or the appropriate
    error code.
2.  **Mock Heavy Logic**: If a command sends emails or calls external APIs, mock those services
    within the test.
3.  **Silent Mode**: Test that the `--quiet` flag correctly suppresses output.

---

_Console commands are the "Maintenance Interface" of Internara. Testing them ensures that
administrative operations are reliable and safe to run in production._
