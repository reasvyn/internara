# Exception Hierarchy — AppException vs ModuleException
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Status
Accepted

## Context

A 23-module, 465-file application needs a consistent way to signal failures across layers.
A `UserNotFoundException` thrown from an Action and a `ValidationFailedException` thrown from
a FormRequest should be distinguishable by their purpose, not just their class name.

The default Laravel pattern — throwing `Exception` or `RuntimeException` subclasses — makes
it impossible for a catch block to distinguish "this is a module logic failure you should
handle differently" from "this is an infrastructure failure you should report to operations"
without inspecting the class hierarchy at runtime.

Two alternatives were considered:

1. **Single exception tree**: All application exceptions extend a single `AppException`.
   Simple — but a controller trying to catch module violations (`catch (RejectedException)`)
   would also catch framework-layer failures like `ValidationFailedException` if they share
   the same root. Catch blocks cannot distinguish intent.

2. **Parallel exception trees**: Separate `AppException` (for framework/infrastructure layer)
   from `ModuleException` (for business rule violations). Catch blocks can target either tree
   independently. A controller can catch `ModuleException` for user-facing messages without
   catching infrastructure errors.

## Decision

Two parallel exception roots, both extending `RuntimeException`:

```
RuntimeException
│
├── AppException (abstract)
│   │  Framework & infrastructure layer failures.
│   │  Things a developer or operator should care about.
│   │
│   ├── ActionException (abstract)
│   │   Business operation failures at the Action layer.
│   │   ├── ValidationFailedException — input validation failed
│   │   └── ConflictException — duplicate or conflicting state
│   │
│   ├── InfrastructureException (abstract)
│   │   External system failures.
│   │   └── RateLimitException — rate limit exceeded
│   │
│   └── PresentationException (abstract)
│       HTTP-layer failures.
│       ├── NotFoundException — resource not found
│       └── UnauthorizedException — access denied
│
└── ModuleException (abstract)
    │  Business rule violations. Things a user or admin should understand.
    │  Deliberately NOT a child of AppException.
    │
    └── RejectedException — module invariant violated (e.g., invalid state transition)
```

### Why Two Roots?

`ModuleException` is intentionally NOT a child of `AppException`. This means:

```php
// A controller can catch module violations specifically
catch (ModuleException $e) {
    flash()->error($e->getMessage());
    return; // Never catches infrastructure errors
}

// Operations can catch infrastructure failures specifically
catch (InfrastructureException $e) {
    Log::error('External service failed', ['exception' => $e]);
    flash()->error('A temporary error occurred. Please try again.');
    return; // Never catches module violations
}
```

If `ModuleException` extended `AppException`, a `catch (AppException)` block would catch both
module violations and infrastructure errors, forcing every handler to inspect the class
hierarchy to distinguish them.

### HasExceptionContext Trait

Both trees use the `HasExceptionContext` trait, providing a consistent API:

| Method | Purpose | Example |
|---|---|---|
| `withHint(?string)` | User-facing hint for resolution | `"Try a different date range"` |
| `getHint()` | Retrieve the hint | For error pages or API responses |
| `withContext(array)` | Key-value debugging context | `['registration_id' => $id, 'current_status' => 'pending']` |
| `getContext()` | Retrieve debug context | For logging or error reporting |
| `toCliOutput()` | CLI-formatted error message | For artisan command error output |

### Exception Selection Guide

| Scenario | Exception to Throw | Hierarchy |
|---|---|---|
| Input validation failed | `ValidationFailedException` | AppException → ActionException |
| Duplicate record | `ConflictException` | AppException → ActionException |
| Permission denied (route/policy) | `UnauthorizedException` | AppException → PresentationException |
| Resource not found | `NotFoundException` | AppException → PresentationException |
| External API timeout | `InfrastructureException` | AppException → InfrastructureException |
| Rate limit exceeded | `RateLimitException` | AppException → InfrastructureException |
| Invalid state transition | `RejectedException` | ModuleException |
| Module invariant violated | `RejectedException` | ModuleException |
| General business rule failure | `RejectedException` | ModuleException |

## Consequences

- **Positive**: Catch blocks can target module failures or framework failures independently.
  A controller can catch `ModuleException` for user-facing error messages without worrying
  about catching infrastructure errors.
- **Positive**: Exception class communicates intent — `RejectedException` means "business rule
  rejected this operation," not "database connection failed."
- **Positive**: Structured context (`withContext(['registration_id' => $id])`) is available
  on every exception — useful for logging, debugging, and API error responses.
- **Positive**: `toCliOutput()` ensures CLI commands (artisan, tinker) display errors with
  consistent formatting including hints and context.
- **Negative**: Two abstract roots means developers must choose which tree to extend.
  Misclassification (throwing `AppException` for a module failure) is possible and requires
  code review to catch.
- **Negative**: HTTP error handlers must branch for both trees — separate `render()` paths
  for `AppException` and `ModuleException`.
- **Negative**: Adding a new exception type requires deciding which tree it belongs to —
  an extra decision point during development.

## References

- `app/Core/Exceptions/AppException.php` — abstract root for framework exceptions
- `app/Core/Exceptions/ModuleException.php` — abstract root for module exceptions
- `app/Core/Exceptions/Concerns/HasExceptionContext.php` — shared trait
- `app/Core/Exceptions/RejectedException.php` — most commonly used module exception
- `docs/architecture.md` — Exception Hierarchy section
- `docs/conventions.md` — Section 12 (Exceptions)
