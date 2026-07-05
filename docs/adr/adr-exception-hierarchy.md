# ADR-007: Exception Hierarchy

> **Last updated:** 2026-06-10 **Changes:** sync — initial metadata sync with new format

## Description

Two independent exception trees — AppException for infrastructure failures and ModuleException for
business rule violations — prevent generic catch blocks from mixing unrelated error types.

## Context

A 19-module codebase needs a consistent way to signal failures across layers. A `NotFoundException`
thrown from an Action and a `RejectedException` thrown from a FormRequest should be distinguishable
by their purpose, not just their class name. A controller should be able to catch module violations
without accidentally catching infrastructure errors.

Two alternatives were considered:

1. **Single exception tree** — all exceptions extend `AppException`. Simple, but a
   `catch (AppException)` block would catch both module violations and framework failures, forcing
   every handler to inspect the class hierarchy.

2. **Sibling exception trees** — separate `AppException` (framework/infrastructure) from
   `ModuleException` (business rules). Catch blocks can target either tree independently.

## Decision

Two parallel exception roots, both extending `RuntimeException`, as siblings:

```
RuntimeException
├── AppException (abstract)          ← Framework & infrastructure failures
│   ├── ActionException
│   │   ├── ValidationFailedException
│   │   └── ConflictException
│   ├── InfrastructureException
│   │   └── RateLimitException
│   └── PresentationException
│       ├── NotFoundException
│       └── UnauthorizedException
│
└── ModuleException (abstract)       ← Business rule violations
    └── RejectedException
```

`ModuleException` is deliberately **not** a child of `AppException`. This enables precise catch
blocks:

```php
catch (ModuleException $e) {           // Business rules only
    flash()->error($e->getMessage());
}
catch (InfrastructureException $e) {   // Infrastructure failures only
    Log::error('External service failed', ['exception' => $e]);
}
```

### HasExceptionContext Trait

Both trees share the `HasExceptionContext` trait providing a consistent API: `withHint()` for
user-facing resolution hints, `withContext()` for key-value debugging context, `getHint()` and
`getContext()` for retrieval, and `toCliOutput()` for CLI-formatted messages.

### Selection Guide

| Scenario                  | Exception                 | Tree                                   |
| ------------------------- | ------------------------- | -------------------------------------- |
| Input validation failed   | ValidationFailedException | AppException → ActionException         |
| Duplicate record          | ConflictException         | AppException → ActionException         |
| Permission denied         | UnauthorizedException     | AppException → PresentationException   |
| Resource not found        | NotFoundException         | AppException → PresentationException   |
| External API timeout      | InfrastructureException   | AppException → InfrastructureException |
| Rate limit exceeded       | RateLimitException        | AppException → InfrastructureException |
| Invalid state transition  | RejectedException         | ModuleException                        |
| Module invariant violated | RejectedException         | ModuleException                        |

## Consequences

- **Positive**: Catch blocks target module failures or framework failures independently. A
  controller catches `ModuleException` for user-facing errors without catching infrastructure
  errors.
- **Positive**: Exception class communicates intent immediately — `RejectedException` means
  "business rule rejected this," not "database connection failed."
- **Positive**: Structured context (`withContext(...)`) is available on every exception for logging,
  debugging, and API responses.
- **Negative**: Developers must choose which tree to extend when creating new exceptions —
  misclassification requires code review to catch.
- **Negative**: HTTP error handlers must branch for both trees with separate render paths.

## References

- `app/Core/Exceptions/AppException.php` — Abstract root for framework exceptions
- `app/Core/Exceptions/ModuleException.php` — Abstract root for module exceptions
- `app/Core/Exceptions/Concerns/HasExceptionContext.php` — Shared trait
- `app/Core/Exceptions/RejectedException.php` — Most commonly used module exception
- `docs/architecture.md` — Exception Hierarchy section
- `docs/conventions.md` — Exception Hierarchy section
