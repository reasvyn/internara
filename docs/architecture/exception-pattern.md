# Exception Pattern

> **Last updated:** 2026-06-10
>
> Comprehensive reference on the dual exception hierarchy used throughout Internara. Covers both
> trees, the shared context trait, concrete exception classes, error handling in Actions, and
> testing conventions.

---

## Table of Contents

1. [Dual Hierarchy Overview](#1-dual-hierarchy-overview)
2. [AppException Tree](#2-appexception-tree)
3. [ModuleException Tree](#3-moduleexception-tree)
4. [Why Two Trees](#4-why-two-trees)
5. [HasExceptionContext Trait](#5-hasexceptioncontext-trait)
6. [Concrete Exception Reference](#6-concrete-exception-reference)
7. [When to Throw Which Exception](#7-when-to-throw-which-exception)
8. [Error Handling in Actions](#8-error-handling-in-actions)
9. [User-Facing vs System-Facing Exceptions](#9-user-facing-vs-system-facing-exceptions)
10. [Testing Exceptions](#10-testing-exceptions)

---

## 1. Dual Hierarchy Overview

Internara uses two parallel exception hierarchies, both rooted in PHP's `RuntimeException`. They are
independent siblings — `ModuleException` does **not** extend `AppException`.

```
RuntimeException
│
├── AppException (abstract)            ← Application, HTTP, infrastructure failures
│   │
│   ├── ActionException (abstract)     ← Request-level failures (validation, conflict)
│   │   ├── ValidationFailedException  422
│   │   └── ConflictException          409
│   │
│   ├── InfrastructureException (abstract)  ← System-level failures (rate limits, I/O)
│   │   └── RateLimitException         429
│   │
│   └── PresentationException (abstract)    ← HTTP-level failures (not found, unauthorized)
│       ├── NotFoundException          404
│       └── UnauthorizedException      403
│
└── ModuleException (abstract)         ← Business rule violations
    └── RejectedException             400
```

All 13 exception classes live under `app/Core/Exceptions/`. Every abstract base and every concrete
class is a single-file, single-responsibility class. The shared `HasExceptionContext` trait lives
in `app/Core/Exceptions/Concerns/HasExceptionContext.php`.

---

## 2. AppException Tree

`AppException` (`app/Core/Exceptions/AppException.php`) is the abstract root for exceptions related
to application flow, HTTP semantics, and infrastructure. It extends `RuntimeException` and uses
the `HasExceptionContext` trait:

```php
abstract class AppException extends RuntimeException
{
    use HasExceptionContext;

    abstract public function statusCode(): int;

    public function isUserFacing(): bool
    {
        return true;
    }

    public function shouldReport(): bool
    {
        return true;
    }
}
```

Every concrete exception in this tree must implement `statusCode()` — the HTTP status code the
exception maps to.

### 2.1 ActionException Branch

`ActionException` (`app/Core/Exceptions/ActionException.php`) is the abstract mid-level for
request-action failures: validation errors and conflict states. Default status code: **400**.

```php
abstract class ActionException extends AppException
{
    public function statusCode(): int
    {
        return 400;
    }

    public function isUserFacing(): bool
    {
        return true;
    }
}
```

Concrete children:

- **`ValidationFailedException`** (422) — input validation failure. Default hint: *"Please check
  your input and try again."*
- **`ConflictException`** (409) — duplicate record or state conflict. Default hint: *"The request
  conflicts with the current state of the resource."*

### 2.2 InfrastructureException Branch

`InfrastructureException` (`app/Core/Exceptions/InfrastructureException.php`) is the abstract
mid-level for system-level failures. These are **not user-facing** by default. Default status
code: **500**.

```php
abstract class InfrastructureException extends AppException
{
    public function statusCode(): int
    {
        return 500;
    }

    public function isUserFacing(): bool
    {
        return false;
    }
}
```

Concrete child:

- **`RateLimitException`** (429) — too many requests. Default hint: *"Please wait before making
  another request."*

### 2.3 PresentationException Branch

`PresentationException` (`app/Core/Exceptions/PresentationException.php`) is the abstract mid-level
for HTTP-level presentation failures. Default status code: **400**.

```php
abstract class PresentationException extends AppException
{
    public function statusCode(): int
    {
        return 400;
    }

    public function isUserFacing(): bool
    {
        return true;
    }
}
```

Concrete children:

- **`NotFoundException`** (404) — resource missing. Default hint: *"The requested resource does not
  exist or has been removed."*
- **`UnauthorizedException`** (403) — permission denied. Default hint: *"You do not have permission
  to perform this action."*

---

## 3. ModuleException Tree

`ModuleException` (`app/Core/Exceptions/ModuleException.php`) is the abstract root for business
rule violations. It extends `RuntimeException` directly — **not** `AppException`:

```php
abstract class ModuleException extends RuntimeException
{
    use HasExceptionContext;

    abstract public function statusCode(): int;
}
```

Concrete child:

- **`RejectedException`** (400) — thrown when a domain invariant or business rule is violated. The
  exception message describes what was rejected and why.

```php
class RejectedException extends ModuleException
{
    public function statusCode(): int
    {
        return 400;
    }
}
```

`RejectedException` has no default message, hint, or context — every throw site provides the
relevant details. Unlike `AppException` children, it does not override `isUserFacing()` — it
inherits the trait default of `true`.

---

## 4. Why Two Trees

`ModuleException` is deliberately **not** a subclass of `AppException`. This design enables
precise catch-block targeting without class-inspection logic:

```php
// Catch business rule violations only — never catches infrastructure errors
catch (ModuleException $e) {
    flash()->error($e->getMessage());
}

// Catch infrastructure failures only — never catches business rules
catch (InfrastructureException $e) {
    Log::error('External service failed', ['exception' => $e]);
}
```

If `ModuleException` extended `AppException`, a `catch (AppException $e)` block would silently
swallow business rule violations, making it impossible to separate "the user sent bad data" from
"the database is unreachable" at the catch level.

**Two scenarios illustrating the difference:**

| Scenario | Exception | Catch target |
|---|---|---|
| User tries to register with an existing email | `ConflictException` | `AppException` |
| User tries to transition an internship from *Draft* to *Completed* | `RejectedException` | `ModuleException` |
| API rate limit hit | `RateLimitException` | `InfrastructureException` (child of `AppException`) |
| Resource not found | `NotFoundException` | `AppException` |

---

## 5. HasExceptionContext Trait

`HasExceptionContext` (`app/Core/Exceptions/Concerns/HasExceptionContext.php`) is the shared trait
used by **both** `AppException` and `ModuleException`. It provides five capabilities:

### 5.1 Hint

A user-facing resolution hint attached to the exception:

```php
$e->withHint('Please verify your email address before logging in.');
$e->getHint(); // 'Please verify your email address before logging in.'
```

Concrete exceptions set a sensible default hint in their constructor. Callers override it when
they have more specific guidance:

```php
throw new ValidationFailedException(
    message: 'Email is required',
    hint: 'Enter a valid email address in the email field.',
    context: ['field' => 'email'],
);
```

### 5.2 Context

Arbitrary key-value metadata for debugging and logging:

```php
$e->withContext(['user_id' => $user->id, 'attempted_action' => 'delete_internship']);
$e->getContext(); // ['user_id' => ..., 'attempted_action' => 'delete_internship']
```

Context is **not** shown to end users by default. It appears in logs and CLI output (after PII
sanitization).

### 5.3 CLI Output

`toCliOutput()` formats the exception for terminal display. It concatenates the message, hint, and
sanitized context:

```
Grade book is locked
  Hint: Final grades have been submitted and the grade book is now locked for editing.
  grade_book_id: 5e7e8f9a-b123-4567
  locked_by: Administrator
```

Sensitive values (emails, passwords, tokens) are masked via `PiiMasker` before emission:

```php
$e->toCliOutput();
// email: jo***@example.com
// password: ***
```

### 5.4 PII Sanitization

`getSanitizedContext()` returns context with PII fields masked. This is used internally by
`toCliOutput()` and is available for custom renderers:

```php
$sanitized = $e->getSanitizedContext();
// ['email' => 'us***@example.com', 'token' => '***', 'safe_key' => 'visible']
```

### 5.5 Default Boolean Methods

- `isUserFacing(): bool` — defaults to `true`. Overridden to `false` by `InfrastructureException`.
- `shouldReport(): bool` — defaults to `true`. All exceptions are logged by default.

---

## 6. Concrete Exception Reference

| Class | Parent | Status Code | Default Message | Default Hint | User-Facing |
|---|---|---|---|---|---|
| `ValidationFailedException` | `ActionException` | 422 | `Validation failed` | *Please check your input and try again.* | Yes |
| `ConflictException` | `ActionException` | 409 | `Conflict` | *The request conflicts with the current state of the resource.* | Yes |
| `NotFoundException` | `PresentationException` | 404 | `Resource not found` | *The requested resource does not exist or has been removed.* | Yes |
| `UnauthorizedException` | `PresentationException` | 403 | `Unauthorized` | *You do not have permission to perform this action.* | Yes |
| `RateLimitException` | `InfrastructureException` | 429 | `Too many requests` | *Please wait before making another request.* | **No** |
| `RejectedException` | `ModuleException` | 400 | *(none — required)* | *(none — caller provides)* | Yes |

### Constructor Pattern

All six concrete exceptions follow the same constructor pattern:

```php
public function __construct(
    string $message = 'Default message',
    ?string $hint = null,
    array $context = [],
) {
    parent::__construct($message);
    $this->withHint($hint ?? 'Default hint for this exception type.');
    $this->withContext($context);
}
```

The three arguments map directly to the three capabilities of `HasExceptionContext`. When the
exception is thrown without explicit hint or context, the sensible defaults are used.

### Status Code Allocation

| Code | Exception | Rationale |
|---|---|---|
| 400 | `RejectedException`, `ActionException` (default) | General client error — business rule rejection or invalid action |
| 403 | `UnauthorizedException` | Authenticated but not permitted |
| 404 | `NotFoundException` | Resource does not exist |
| 409 | `ConflictException` | Duplicate or state conflict |
| 422 | `ValidationFailedException` | Input validation failure |
| 429 | `RateLimitException` | Rate limit exceeded |
| 500 | `InfrastructureException` (default) | System-level failure (not directly used by concrete exceptions) |

---

## 7. When to Throw Which Exception

### ValidationFailedException

Throw when user-supplied input fails validation rules:

```php
if (! preg_match('/^[A-Za-z0-9]{3,20}$/', $username)) {
    throw new ValidationFailedException(
        message: 'Username must be 3-20 alphanumeric characters.',
        hint: 'Use only letters and numbers, no spaces or special characters.',
        context: ['field' => 'username', 'value' => $username],
    );
}
```

### ConflictException

Throw when an operation would create a duplicate or conflicts with current state:

```php
if (Registration::where('user_id', $user->id)->exists()) {
    throw new ConflictException(
        message: 'User is already registered for this program.',
    );
}
```

### NotFoundException

Throw when a requested resource does not exist:

```php
$internship = Internship::find($id);

if (! $internship) {
    throw new NotFoundException(
        message: "Internship with ID {$id} not found.",
        context: ['internship_id' => $id],
    );
}
```

### UnauthorizedException

Throw when the authenticated user lacks permission. Typically preceded by a policy gate, but can be
used directly in Actions:

```php
if (! $user->hasRole('mentor')) {
    throw new UnauthorizedException(
        message: 'Only mentors can verify attendance records.',
        context: ['user_id' => $user->id, 'required_role' => 'mentor'],
    );
}
```

### RateLimitException

Throw when an operation exceeds rate limits:

```php
if ($attempts >= $maxAttempts) {
    throw new RateLimitException(
        message: 'Too many login attempts.',
        hint: 'Please wait 60 seconds before trying again.',
    );
}
```

### RejectedException

Throw when a business rule or domain invariant is violated. This is the most commonly thrown
exception in the codebase — use it for **every** domain rejection:

```php
if (! $registration->status->canTransitionTo(RegistrationStatus::ACTIVE)) {
    throw new RejectedException(
        'Registration cannot be activated from its current state.',
    );
}

if ($logbook->status !== LogbookStatus::SUBMITTED) {
    throw new RejectedException(
        'Only submitted logbook entries can be verified.',
    );
}
```

**Rule of thumb:** If the rejection describes "the system said no because of a business rule", use
`RejectedException`. If the rejection describes "the user sent something the system cannot
interpret", use `ValidationFailedException` or `ConflictException`.

---

## 8. Error Handling in Actions

The `HandlesActionErrors` trait (`app/Core/Support/HandlesActionErrors.php`) provides a safety net
for wrapping Action execution. It is used by `BaseAction` and can be applied to Read Actions that
need structured error handling:

```php
trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed
    {
        try {
            return $callback();
        } catch (
            RuntimeException|
            AppException|
            ModuleException|
            ValidationException|
            AuthorizationException|
            ModelNotFoundException|
            NotFoundHttpException $e
        ) {
            throw $e;
        } catch (\Throwable $e) {
            SmartLogger::error($context)
                ->withPayload([
                    'error' => $e->getMessage(),
                    'original_file' => $e->getFile(),
                    'original_line' => $e->getLine(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
        }
    }
}
```

### How It Works

1. **Known exception types** (`RuntimeException`, `AppException`, `ModuleException`, framework
   validation/authorization exceptions) are **re-thrown** as-is — they already carry the correct
   semantics, message, and status code.

2. **Unknown exceptions** (`\Throwable` catch-all) are logged via `SmartLogger` with full context
   (message, file, line) and wrapped in a generic `RuntimeException`. This prevents framework bugs
   and unexpected errors from leaking stack traces to the user or HTTP response.

### Usage

`BaseAction` applies the trait but `withErrorHandling()` is available for opt-in use in any class:

```php
class ProcessCsvUploadAction extends BaseAction
{
    public function execute(string $filePath): int
    {
        return $this->withErrorHandling(function () use ($filePath) {
            // ... processing that might throw anything ...
        }, 'csv_upload_failed');
    }
}
```

---

## 9. User-Facing vs System-Facing Exceptions

The `isUserFacing(): bool` method on `HasExceptionContext` distinguishes exceptions that should be
displayed to end users from those that should be logged internally only.

| Exception | User-Facing | Rationale |
|---|---|---|
| `ValidationFailedException` | Yes | User needs to correct their input |
| `ConflictException` | Yes | User needs to understand the conflict |
| `NotFoundException` | Yes | User needs to know the resource is missing |
| `UnauthorizedException` | Yes | User needs to know they lack permission |
| `RateLimitException` | **No** | Internal — user sees a generic 429 page |
| `RejectedException` | Yes | User needs to understand the business rule |
| `InfrastructureException` (base) | **No** | Internal — user sees a generic error page |

The `shouldReport(): bool` method controls whether the exception is logged. All exceptions report
by default. Override `shouldReport()` when an exception is expected and handled gracefully (e.g.,
a caught-and-recovered rate limit).

---

## 10. Testing Exceptions

Every exception class has a corresponding unit test in
`tests/Unit/Core/Exceptions/{ExceptionName}Test.php`. Tests follow consistent patterns:

### Testing Construction and Defaults

```php
// tests/Unit/Core/Exceptions/RejectedExceptionTest.php
test('rejected exception is throwable', function () {
    $e = new RejectedException('Application rejected');

    expect($e->getMessage())->toBe('Application rejected');
    expect($e->isUserFacing())->toBeTrue();
});
```

### Testing Context Methods

```php
// tests/Unit/Core/Exceptions/Concerns/HasExceptionContextTest.php
test('with hint stores hint and returns self', function () {
    $e = new ContextTestException('test');
    $result = $e->withHint('Some hint');

    expect($result)->toBe($e);
    expect($e->getHint())->toBe('Some hint');
});

test('with context stores context and returns self', function () {
    $e = new ContextTestException('test');
    $result = $e->withContext(['key' => 'value']);

    expect($result)->toBe($e);
    expect($e->getContext())->toBe(['key' => 'value']);
});
```

### Testing CLI Output Formatting

```php
test('to cli output includes message and hint', function () {
    $e = new ContextTestException('Something broke')->withHint('Check your config');

    $output = $e->toCliOutput();

    expect($output)->toContain('Something broke');
    expect($output)->toContain('Hint: Check your config');
});

test('to cli output sanitizes sensitive context', function () {
    $e = new ContextTestException('Error')->withContext([
        'email' => 'john@example.com',
        'password' => 'secret123',
        'user_id' => 42,
    ]);

    $output = $e->toCliOutput();

    expect($output)->toContain('email: jo***@example.com');
    expect($output)->toContain('password: ***');
    expect($output)->toContain('user_id: 42');
});
```

### Creating Mock Exceptions for Testing

Abstract exceptions are tested via anonymous mock classes:

```php
class MockAppException extends AppException
{
    public function statusCode(): int
    {
        return 400;
    }
}

class MockModuleException extends ModuleException
{
    public function statusCode(): int
    {
        return 400;
    }
}
```

---

## References

- `app/Core/Exceptions/AppException.php`
- `app/Core/Exceptions/ModuleException.php`
- `app/Core/Exceptions/ActionException.php`
- `app/Core/Exceptions/InfrastructureException.php`
- `app/Core/Exceptions/PresentationException.php`
- `app/Core/Exceptions/ValidationFailedException.php`
- `app/Core/Exceptions/ConflictException.php`
- `app/Core/Exceptions/NotFoundException.php`
- `app/Core/Exceptions/UnauthorizedException.php`
- `app/Core/Exceptions/RateLimitException.php`
- `app/Core/Exceptions/RejectedException.php`
- `app/Core/Exceptions/Concerns/HasExceptionContext.php`
- `app/Core/Support/HandlesActionErrors.php`
- `docs/architecture.md` — §Exceptions
- `docs/conventions.md` — §20 Exception Hierarchy
- `docs/adr/adr-exception-hierarchy.md` — ADR-007
