# ADR-011: Exception Hierarchy — AppException vs DomainException

## Status
Accepted

## Context
A 465-file, 24-domain application needs a consistent way to signal failures across layers.
A `UserNotFoundException` thrown from an Action and a `ValidationFailedException` thrown from
a FormRequest should be distinguishable by their purpose, not just their class name.

The default Laravel pattern — throwing `Exception` or `RuntimeException` subclasses — makes
it impossible for a catch block to distinguish "this is a domain logic failure" from "this is
a framework infrastructure failure" without inspecting the class hierarchy.

Two alternatives were considered:
1. **Single exception tree**: All application exceptions extend a single `AppException`.
   Simple, but a domain catch block (`catch (DomainException)`) would also catch
   framework-layer failures like `ValidationFailedException` if they share the same root.
2. **Parallel exception trees**: Separate `AppException` (for framework-layer failures) from
   `DomainException` (for business rule violations). Catch blocks can target either tree
   independently.

## Decision
Two parallel exception roots exist:

```
RuntimeException
├── AppException (abstract) — framework & infrastructure layer
│   ├── ActionException (abstract) → ValidationFailedException, ConflictException
│   ├── PresentationException (abstract) → NotFoundException, UnauthorizedException
│   └── InfrastructureException (abstract) → RateLimitException
└── DomainException (abstract) — business rule violations
    └── RejectedException
```

`DomainException` is intentionally NOT a child of `AppException`. This means a domain-level
catch block (`catch (DomainException $e)`) never accidentally catches framework exceptions.
Both trees use the `HasExceptionContext` trait for consistent API (`withHint()`, `withContext()`,
`toCliOutput()`).

## Consequences
- **Positive**: Catch blocks can target domain failures or framework failures independently.
  A controller can catch `DomainException` for user-facing error messages without worrying
  about catching infrastructure errors.
- **Positive**: Exception type communicates intent — `RejectedException` means "business rule
  rejected this operation," not "the database connection failed."
- **Positive**: Structured context (`withContext(['registration_id' => $id])`) is available
  on every exception — useful for logging and API error responses.
- **Negative**: Two abstract roots means developers must choose which tree to extend.
  Misclassification (throwing `AppException` for a domain failure) is possible.
- **Negative**: Exception mapping for HTTP responses must handle both trees — error handlers
  need separate branches for `AppException` and `DomainException`.

## References
- `app/Domain/Core/Exceptions/`
- `docs/architecture.md` — Exception Hierarchy section
- `docs/conventions.md` — Section 12 (Exceptions)
- `tests/Arch/ExceptionLayerArchTest.php`
