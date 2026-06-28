# Exception Pattern — Dual Exception Hierarchy & Error Handling

> **Last updated:** 2026-06-13
> **Changes:** initial metadata — no content changes
## Description

Dual exception hierarchy with AppException (infrastructure) and ModuleException (business rules), error handling patterns, and Livewire catch blocks.

## Dual Hierarchy Overview

Internara uses two parallel exception hierarchies, both rooted in PHP's `RuntimeException`. They are
independent siblings — the business-rule tree does **not** extend the application-flow tree.

```
RuntimeException
│
├── AppException (abstract)            ← Application, HTTP, infrastructure failures
│   │
│   ├── ActionException (abstract)     ← Request-level failures (validation, conflict)
│   ├── InfrastructureException (abstract)  ← System-level failures
│   └── PresentationException (abstract)    ← HTTP-level failures
│
└── ModuleException (abstract)         ← Business rule violations
    └── RejectedException
```

Every abstract base and concrete class is a single-file, single-responsibility class. The shared
context trait is used by both trees.

### AppException Tree

`AppException` is the abstract root for application flow, HTTP semantics, and infrastructure
exceptions. It extends `RuntimeException` and uses the shared context trait. Every concrete
exception in this tree must implement `statusCode()` — the HTTP status code it maps to.

- **ActionException branch** — request-action failures (validation errors, conflict states).
  These are user-facing.
- **InfrastructureException branch** — system-level failures (I/O errors, rate limits). These are
  **not** user-facing by default.
- **PresentationException branch** — HTTP-level presentation failures (resource not found,
  unauthorized access). These are user-facing.

### ModuleException Tree

`ModuleException` is the abstract root for business rule violations. It extends `RuntimeException`
directly — **not** `AppException`. Its sole concrete child is `RejectedException`, thrown when a
domain invariant or business rule is violated. The exception message describes what was rejected
and why. Every throw site provides the relevant details — there is no default message or hint.

---

## Why Two Trees

`ModuleException` is deliberately **not** a subclass of `AppException`. This design enables precise
catch-block targeting without class-inspection logic:

```
catch (ModuleException $e)           ← Business rule violations only
catch (InfrastructureException $e)   ← Infrastructure failures only
```

If `ModuleException` extended `AppException`, a `catch (AppException $e)` block would silently
swallow business rule violations, making it impossible to separate "the user sent bad data" from
"the database is unreachable" at the catch level.

---

## HasExceptionContext Trait

The shared trait used by **both** `AppException` and `ModuleException`. It provides five
capabilities:

### Hint

A user-facing resolution hint attached to the exception. Concrete exceptions set a sensible default
hint; callers override it when they have more specific guidance.

### Context

Arbitrary key-value metadata for debugging and logging. Context is **not** shown to end users by
default. It appears in logs and CLI output (after PII sanitization).

### CLI Output

Formats the exception for terminal display, concatenating message, hint, and sanitized context.
Sensitive values (emails, passwords, tokens) are masked before emission.

### PII Sanitization

Returns context with PII fields masked. Used internally by CLI output and available for custom
renderers.

### Default Boolean Methods

- `isUserFacing(): bool` — defaults to `true`. Overridden to `false` by infrastructure exceptions.
- `shouldReport(): bool` — defaults to `true`. All exceptions are logged by default.

---

## Error Handling in Actions

A dedicated trait provides a safety net for wrapping Action execution. It distinguishes between
known exception types (which are re-thrown as-is since they already carry correct semantics) and
unknown exceptions (which are logged with full context and wrapped in a generic exception to
prevent stack traces from leaking to users or HTTP responses).

The trait is applied to the base Action class and is available for opt-in use in any class
that needs structured error handling.

---

## User-Facing vs System-Facing Exceptions

The `isUserFacing(): bool` method on the shared context trait distinguishes exceptions that should
be displayed to end users from those that should be logged internally only.

- **User-facing** — exceptions the user needs to understand and act on (input corrections, business
  rule rejections, permission denials).
- **System-facing** — exceptions that should result in a generic error page while full details are
  logged internally (infrastructure failures, rate limits).

The `shouldReport(): bool` method controls whether the exception is logged. All exceptions report
by default. Override `shouldReport()` when an exception is expected and handled gracefully.
