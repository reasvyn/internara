# Exception Hierarchy

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-25 |
| Technical Story | [Exception Pattern](../guides/arch/exception-pattern.md) and [Architecture Overview](../architecture.md) § Exception Hierarchy |

## Context and Problem Statement

Across 18 modules, failures across layers must be distinguishable by purpose, not just class
name. A `RejectedException` from an Action and a `ValidationFailedException` from a FormRequest
should be catchable independently — a controller catching module violations must not accidentally
catch infrastructure errors. Early variants included `ConflictException`, `NotFoundException`,
and `RateLimitException`; all were later consolidated into `RejectedException` for business
rule violations (duplicates, not-found, rate limits) to keep the module tree minimal.

**Decision Drivers:**

* Precise catch targeting — business-rule failures vs framework/infrastructure failures
* Intent communication through class name (`RejectedException` = "rule rejected this")
* Structured context for logging, debugging, and API/CLI rendering
* Minimal tree that does not force handlers to inspect class hierarchies

## Considered Options

* **Single exception tree** — all exceptions extend `AppException`.
  *Pros:* simplest. *Cons:* `catch (AppException)` mixes module violations with framework
  failures; every handler must inspect the hierarchy to discriminate.*
* **Sibling exception trees (chosen)** — separate `AppException` (framework/infrastructure)
  from `ModuleException` (business rules) as siblings under `RuntimeException`.
  *Pros:* catch blocks target either tree independently; intent is immediate from the catch
  type. *Cons:* author must choose the correct tree when creating a new exception.*

## Decision Outcome

**Chosen option: Sibling exception trees** — two parallel roots, both extending
`RuntimeException`:

```
RuntimeException
├── AppException (abstract)          ← Framework & infrastructure failures
│   ├── ActionException
│   │   └── ValidationFailedException
│   ├── InfrastructureException
│   └── PresentationException
│       └── UnauthorizedException
│
└── ModuleException (abstract)       ← Business rule violations
    └── RejectedException
```

`ModuleException` is deliberately **not** a child of `AppException`:

```php
catch (ModuleException $e) {           // Business rules only
    $this->toast()->error($e->getMessage())->send();
}
catch (InfrastructureException $e) {   // Infrastructure failures only
    Log::error('External service failed', ['exception' => $e]);
}
```

**Shared trait — HasExceptionContext** — both trees use `HasExceptionContext` providing
`withHint()` (user-facing resolution), `withContext()` (key-value debugging),
`getHint()` / `getContext()`, and `toCliOutput()`.

**Selection Guide:**

| Scenario | Exception | Tree |
|----------|-----------|------|
| Input validation failed | ValidationFailedException | AppException → ActionException |
| Permission denied | UnauthorizedException | AppException → PresentationException |
| External API timeout | InfrastructureException | AppException → InfrastructureException |
| Invalid state transition | RejectedException | ModuleException |
| Module invariant violated | RejectedException | ModuleException |
| Duplicate / not found | RejectedException | ModuleException (business rule) |

Legacy `ConflictException`, `NotFoundException`, and `RateLimitException` are superseded —
use `RejectedException` consistently.

### Positive Consequences

* Catch blocks target module or framework failures independently
* Exception class communicates intent immediately
* Structured context available on every exception for logging and responses

### Negative Consequences

* Author must choose the correct tree — misclassification requires review to catch
* HTTP error handlers branch for both trees with separate render paths

## Links

* [Exception Pattern](../guides/arch/exception-pattern.md) — hierarchy, trait, and selection guide
* [Architecture Overview](../architecture.md) — where the hierarchy sits in the layer model
* [Conventions — Exception Handling](../conventions.md) — team-wide throw/catch rules
